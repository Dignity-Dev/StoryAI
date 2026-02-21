<?php
// qwen_api.php

class QwenAPI {
    private $apiKey;
    private $model;
    private $endpoint = 'https://dashscope-intl.aliyuncs.com/api/v1/services/aigc/text-generation/generation';

    public function __construct($apiKey, $model = 'qwen-turbo') {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function generateStory($prompt) {
        $data = [
            'model' => $this->model,
            'input' => [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a creative story writer. Write engaging short stories.'
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Write a short story based on this prompt: ' . $prompt
                    ]
                ]
            ],
            'parameters' => [
                'temperature' => 0.7,
                'max_tokens' => 500,
                'result_format' => 'message'
            ]
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL Error: ' . $error];
        }

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            
            // Extract story from Qwen response structure
            if (isset($result['output']['choices'][0]['message']['content'])) {
                $story = $result['output']['choices'][0]['message']['content'];
                $tokens = $result['usage']['total_tokens'] ?? 0;
                
                return [
                    'success' => true,
                    'story' => $story,
                    'tokens' => $tokens,
                    'model' => $this->model
                ];
            } else {
                return ['success' => false, 'error' => 'Unexpected response format'];
            }
        } else {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['message'] ?? 'Unknown API Error';
            return ['success' => false, 'error' => "HTTP {$httpCode}: {$errorMsg}"];
        }
    }
}
?>