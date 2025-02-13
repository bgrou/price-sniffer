<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService {

    /**
     * @throws ConnectionException
     */
    public function request($header): \Illuminate\Http\JsonResponse
    {
        $url = config('services.llm.baseUrl');

        // Define the request payload
        $payload = [
            'model' => 'sonar-pro',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that maps CSV headers to specific field names.
                    Always respond just with a JSON object where keys are "EAN", "Description", "Stock", and "Price",
                    and values are their respective column positions (index, int) (starting from 0). The keys on the headers might
                    not be the same as the keys but also synonyms or something with the same meaning.
                    Empty header field should not be used.
                    If a field is not found, set its value to null.'
                ],
                [
                    'role' => 'user',
                    'content' => 'Headers: ' . implode(',', $header),
                ]
            ]
        ];

        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.llm.key'),
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);

        if ($response->failed()) {
            return response()->json([
                'error' => true,
                'message' => $response->body()
            ], $response->status());
        }

        $responseData = $response->json();

        if (isset($responseData['choices'][0]['message']['content'])) {
            $content = $responseData['choices'][0]['message']['content'];
            $cleanedContent = trim(str_replace('`', '', $content));
            $cleanedContent = trim(str_replace('json', '', $cleanedContent));
            $jsonObject = json_decode($cleanedContent, true);
            return response()->json($jsonObject);
        }

        return response()->json([
            'error' => true,
            'message' => 'Invalid response format: Missing content.'
        ]);
    }
}
