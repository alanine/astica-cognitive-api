<?php

// Set your API key here
$apiKey = 'YOUR_API_KEY_HERE'; // get one at https://astica.ai/api-keys/

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

$url     = 'https://voice.astica.ai/api/voice_list';
$payload = json_encode(['tkn' => $apiKey]);

echo "Requesting voice list from $url ...\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

if ($response === false) {
    echo "cURL error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "HTTP error: $httpCode\n";
    echo $response . "\n";
    exit(1);
}

$data = json_decode($response, true);

if (!is_array($data) || !isset($data['status']) || $data['status'] !== 'success') {
    echo "Unexpected API response:\n";
    echo $response . "\n";
    exit(1);
}

$voices = isset($data['voices']) && is_array($data['voices']) ? $data['voices'] : [];

echo "Received " . count($voices) . " voices.\n";

foreach (array_slice($voices, 0, 10) as $v) {
    $alias       = isset($v['alias']) ? $v['alias'] : '';
    $displayName = isset($v['display_name']) ? $v['display_name'] : '';
    $label       = isset($v['label']) ? $v['label'] : '';
    echo " - $alias | $displayName | $label\n";
}

// Save full JSON response to a file
$filePath = __DIR__ . '/voice-list.json';
file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\nFull response saved to: $filePath\n";