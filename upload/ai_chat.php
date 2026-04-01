<?php
/*
 ***************************************************************
 | ClipBucket AI Chat (basic full feature page)
 ****************************************************************
*/

define('THIS_PAGE', 'ai_chat');
define('PARENT_PAGE', 'home');

require 'includes/config.inc.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$apiKey = getenv('OPENAI_API_KEY');
$defaultModel = getenv('OPENAI_MODEL');
if (!$defaultModel) {
    $defaultModel = 'gpt-4o-mini';
}

if (!isset($_SESSION['ai_chat_history']) || !is_array($_SESSION['ai_chat_history'])) {
    $_SESSION['ai_chat_history'] = array();
}

if (isset($_POST['clear_chat'])) {
    $_SESSION['ai_chat_history'] = array();
}

if (isset($_POST['send_chat'])) {
    $prompt = trim(post('prompt'));

    if (empty($prompt)) {
        e('Please enter a message.', 'w');
    } elseif (empty($apiKey)) {
        e('AI chat is not configured. Set OPENAI_API_KEY in server environment.', 'w');
    } else {
        $maxTurns = 10;
        $history = $_SESSION['ai_chat_history'];

        $messages = array(
            array('role' => 'system', 'content' => 'You are a helpful assistant for a ClipBucket video community.')
        );

        $sliceCount = $maxTurns * 2;
        if (count($history) > $sliceCount) {
            $history = array_slice($history, -1 * $sliceCount);
        }

        foreach ($history as $item) {
            $messages[] = array(
                'role' => $item['role'],
                'content' => $item['content']
            );
        }

        $messages[] = array('role' => 'user', 'content' => $prompt);

        $payload = json_encode(array(
            'model' => $defaultModel,
            'messages' => $messages,
            'temperature' => 0.7
        ));

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false || !empty($curlError)) {
            e('AI request failed: ' . $curlError, 'w');
        } else {
            $response = json_decode($rawResponse, true);
            $reply = $response['choices'][0]['message']['content'];

            if ($statusCode >= 400) {
                $errorMessage = $response['error']['message'];
                if (empty($errorMessage)) {
                    $errorMessage = 'Unknown API error.';
                }
                e('AI API error (' . $statusCode . '): ' . $errorMessage, 'w');
            } elseif (empty($reply)) {
                e('AI returned an empty response.', 'w');
            } else {
                $_SESSION['ai_chat_history'][] = array(
                    'role' => 'user',
                    'content' => $prompt,
                    'created_at' => now()
                );
                $_SESSION['ai_chat_history'][] = array(
                    'role' => 'assistant',
                    'content' => $reply,
                    'created_at' => now()
                );
                e('Message sent.', 'm');
            }
        }
    }
}

assign('ai_model', $defaultModel);
assign('ai_chat_history', $_SESSION['ai_chat_history']);
assign('openai_api_ready', !empty($apiKey));

subtitle('AI Chat');
template_files('ai_chat.html');
display_it();
