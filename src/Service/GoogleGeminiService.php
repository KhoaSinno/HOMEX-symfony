<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleGeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function askQuestion(string $question, array $context = []): array
    {
        if (!is_string($this->apiKey)) {
            throw new \InvalidArgumentException('API key must be a string, got ' . gettype($this->apiKey));
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
        $url .= '?key=' . $this->apiKey;

        $contentParts = [['text' => $question]];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $valueString = implode(', ', array_map(function ($item) {
                    if (is_object($item)) {
                        if (method_exists($item, 'getFullname')) {
                            return $item->getFullname();
                        }
                        if (method_exists($item, '__toString')) {
                            return (string) $item;
                        }
                        return get_class($item);
                    }
                    return (string) $item;
                }, $value));
            } else {
                $valueString = (string) $value;
            }
            $contentParts[] = ['text' => "$key: $valueString"];
        }

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => $contentParts,
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 500,
                    'temperature' => 0.7,
                ],
            ],
        ]);

        return $response->toArray();
    }
}
