<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiMeterReader
{
    /**
     * Ask Gemini to read an analogue meter value from an image.
     * Returns a suggested numeric reading only — never auto-commits.
     *
     * @throws RuntimeException when API key missing, call fails, or no number found
     */
    public function suggestFromImage(string $absoluteImagePath): float
    {
        $apiKey = config('services.gemini.api_key');
        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        if (! is_readable($absoluteImagePath)) {
            throw new RuntimeException('Meter photo could not be read.');
        }

        $mime = mime_content_type($absoluteImagePath) ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($absoluteImagePath));
        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $prompt = <<<'PROMPT'
You are reading an analogue utility gas meter dial/display from a photo.
Return ONLY the current meter reading as a decimal number in cubic meters (m³).
Do not include units, words, or explanation.
If the reading is unclear, return your best estimate as a number only.
Examples of valid replies: 46.28 or 103.84 or 9.72
PROMPT;

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $model
        );

        try {
            $response = Http::timeout(60)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, [
                    'contents' => [[
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mime,
                                    'data' => $base64,
                                ],
                            ],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 32,
                    ],
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Gemini API request failed: '.$e->getMessage(), 0, $e);
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $suggestion = $this->extractNumber((string) $text);

        if ($suggestion === null) {
            throw new RuntimeException('Gemini did not return a readable meter number. Reply was: '.trim((string) $text));
        }

        return $suggestion;
    }

    public function extractNumber(string $text): ?float
    {
        if (preg_match('/(\d+(?:\.\d+)?)/', $text, $matches)) {
            return round((float) $matches[1], 2);
        }

        return null;
    }

    public function isConfigured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }
}
