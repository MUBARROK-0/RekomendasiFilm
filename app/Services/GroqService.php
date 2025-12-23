<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;
    protected $debug;
    protected $lastError;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = config('services.groq.api_key');
        $this->baseUrl = config('services.groq.base_url');
        $this->debug = config('services.groq.debug', false);
    }

    /**
     * Ask Groq to recommend a movie for the given free-text (mood/intent).
     * The model is asked to reply in a strict JSON format for easy parsing.
     */
    public function recommendFromMood(string $text): ?array
    {
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            $this->lastError = 'Groq configuration missing';
            return $this->localRecommendFallback($text);
        }

        // Stronger prompt: require valid JSON only, use null for missing values, and return {} if unable
        $prompt = "You are a strict movie recommender. Given a short user text describing a mood or request, return exactly one VALID JSON object and NOTHING ELSE. The object MUST contain these keys: title (string), overview (string), poster_url (string|null), release_year (number|null), rating (number|null between 0 and 10|null), detail_url (string|null), reason (string). Use null for any missing values. If you cannot answer, return an empty object {}. Do NOT include any surrounding markdown or commentary.\n\nUser request: " . addslashes($text);

        $payload = [
            'input' => $prompt,
            'model' => 'openai/gpt-oss-20b',
            'temperature' => 0.0,
            'max_output_tokens' => 800
        ];

        $url = rtrim($this->baseUrl, '/') . '/responses';

        try {
            if ($this->debug) {
                Log::debug('Groq recommend request', ['url' => $url, 'input' => mb_substr($prompt, 0, 1000)]);
            }

            $response = $this->client->post($url, [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 10,
            ]);

            $body = $response->getBody()->getContents();

            if ($this->debug) {
                Log::debug('Groq recommend response', ['status' => $response->getStatusCode(), 'body' => mb_substr($body, 0, 4000)]);
            }

            $data = json_decode($body, true);

            // Try common formats: output_text, output[0].content[0].text, or top-level
            $textOut = $data['output_text'] ?? null;

            if (!$textOut && isset($data['output']) && is_array($data['output'])) {
                // try to concatenate text pieces
                $parts = [];
                foreach ($data['output'] as $o) {
                    if (is_array($o) && isset($o['content']) && is_array($o['content'])) {
                        foreach ($o['content'] as $c) {
                            if (is_string($c)) $parts[] = $c;
                            elseif (is_array($c) && isset($c['text'])) $parts[] = $c['text'];
                        }
                    } elseif (is_string($o)) {
                        $parts[] = $o;
                    }
                }
                $textOut = trim(implode("\n", $parts));
            }

            if (!$textOut && isset($data['choices'][0]['text'])) {
                $textOut = $data['choices'][0]['text'];
            }

            if (!$textOut) {
                // fallback to raw body
                $textOut = $body;
            }

            // Attempt to parse JSON from model output
            $json = null;
            $decoded = json_decode($textOut, true);
            if (is_array($decoded)) {
                $json = $decoded;
            } else {
                // Try to extract JSON substring
                if (preg_match('/\{.*\}/sU', $textOut, $m)) {
                    $maybe = $m[0];
                    $decoded2 = json_decode($maybe, true);
                    if (is_array($decoded2)) $json = $decoded2;
                }
            }

            // If parsing failed, try a second request asking Groq to extract the JSON from its output
            if (!is_array($json)) {
                Log::warning('Groq response not parseable, attempting second-pass extraction', ['snippet' => mb_substr($textOut, 0, 500)]);

                try {
                    $extractPrompt = "Extract and return ONLY the JSON object present in the following text (no commentary). If there is no JSON, return {}. Text:\n" . $textOut;
                    $extractPayload = [
                        'input' => $extractPrompt,
                        'model' => 'openai/gpt-oss-20b',
                        'temperature' => 0.0,
                        'max_output_tokens' => 400
                    ];

                    if ($this->debug) Log::debug('Groq extraction request', ['input' => mb_substr($extractPrompt, 0, 1000)]);

                    $resp2 = $this->client->post($url, [
                        'json' => $extractPayload,
                        'headers' => [
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json'
                        ],
                        'timeout' => 8,
                    ]);

                    $body2 = $resp2->getBody()->getContents();
                    if ($this->debug) Log::debug('Groq extraction response', ['status' => $resp2->getStatusCode(), 'body' => mb_substr($body2, 0, 2000)]);

                    $out2 = json_decode($body2, true);
                    $textOut2 = $out2['output_text'] ?? null;
                    if (!$textOut2 && isset($out2['output'])) {
                        $parts = [];
                        foreach ($out2['output'] as $o) {
                            if (is_array($o) && isset($o['content']) && is_array($o['content'])) {
                                foreach ($o['content'] as $c) {
                                    if (is_string($c)) $parts[] = $c;
                                    elseif (is_array($c) && isset($c['text'])) $parts[] = $c['text'];
                                }
                            } elseif (is_string($o)) {
                                $parts[] = $o;
                            }
                        }
                        $textOut2 = trim(implode("\n", $parts));
                    }

                    if (!$textOut2 && isset($out2['choices'][0]['text'])) $textOut2 = $out2['choices'][0]['text'] ?? null;
                    $maybeJson = null;
                    if ($textOut2) {
                        $dec = json_decode($textOut2, true);
                        if (is_array($dec)) $maybeJson = $dec;
                        elseif (preg_match('/\{.*\}/sU', $textOut2, $mm)) {
                            $dec2 = json_decode($mm[0], true);
                            if (is_array($dec2)) $maybeJson = $dec2;
                        }
                    }

                    if (is_array($maybeJson)) {
                        $json = $maybeJson;
                    }
                } catch (\Throwable $ex) {
                    Log::warning('Groq extraction attempt failed', ['message' => $ex->getMessage()]);
                }
            }

            if (is_array($json)) {
                // Normalize keys
                $movie = [
                    'title' => $json['title'] ?? ($json['name'] ?? null),
                    'overview' => $json['overview'] ?? ($json['description'] ?? null),
                    'poster_url' => $json['poster_url'] ?? ($json['image'] ?? null),
                    'release_year' => isset($json['release_year']) ? (int)$json['release_year'] : ($json['year'] ?? null),
                    'rating' => isset($json['rating']) ? (float)$json['rating'] : null,
                    'detail_url' => $json['detail_url'] ?? $json['url'] ?? null,
                    'reason' => $json['reason'] ?? null,
                    'raw_text' => $textOut,
                ];

                return array_filter($movie, function ($v) { return $v !== null && $v !== ''; });
            }

            // If we couldn't parse structured output, store as lastError and return null
            $this->lastError = 'Unable to parse Groq response: ' . mb_substr($textOut, 0, 300);
            Log::warning('Groq returned unparsable response', ['body' => mb_substr($textOut, 0, 1200)]);

            return null;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::error('Groq recommend failed', ['message' => $this->lastError]);
            return $this->localRecommendFallback($text);
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?? null;
    }

    private function localRecommendFallback(string $text): ?array
    {
        // Very small curated fallback using keyword heuristics
        $lower = mb_strtolower($text);
        if (mb_strpos($lower, 'romantis') !== false || mb_strpos($lower, 'cinta') !== false) {
            return ['title' => 'Pride & Prejudice', 'overview' => 'Kisah cinta klasik berdasarkan novel Jane Austen.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Pride+%26+Prejudice', 'release_year' => 2005, 'rating' => 7.8, 'reason' => 'Mood romantis'];
        }
        if (mb_strpos($lower, 'horor') !== false || mb_strpos($lower, 'menakutkan') !== false) {
            return ['title' => 'The Witch', 'overview' => 'Horor atmosferik pada masa awal kolonial.', 'poster_url' => 'https://via.placeholder.com/300x450?text=The+Witch', 'release_year' => 2015, 'rating' => 6.8, 'reason' => 'Mood horor'];
        }

        // default happy
        return ['title' => 'Amélie', 'overview' => 'Seorang pelayan kafe menyebarkan kebaikan melalui aksi kecil.', 'poster_url' => 'https://via.placeholder.com/300x450?text=Amelie', 'release_year' => 2001, 'rating' => 8.3, 'reason' => 'Default fallback'];
    }
}
