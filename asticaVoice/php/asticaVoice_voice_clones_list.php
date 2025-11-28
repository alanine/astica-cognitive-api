#!/usr/bin/env php
<?php
// Simple example: list your voice clones from astica Voice API (PHP)
//
// What this script does:
//   - Calls https://voice.astica.ai/api/voice_clone_list
//   - Prints a summary of your clones
//   - Saves the full JSON response to voice-clones-list.json
//
// Requirements:
//   - PHP 7+
//   - cURL extension enabled
//
// Usage:
//   php voice_clones_list_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------

$apiKey   = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$baseUrl  = 'https://voice.astica.ai';

$outputJsonPath = __DIR__ . '/voice-clones-list.json';

// ---------------------------------------------------------
// Basic checks
// ---------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

$url     = $baseUrl . '/api/voice_clone_list';
$payload = array('tkn' => $apiKey);

echo "Requesting voice clone list from $url ...\n";

// ---------------------------------------------------------
// Make HTTP request
// ---------------------------------------------------------

$ch = curl_init($url);
if ($ch === false) {
    echo "Failed to initialize cURL\n";
    exit(1);
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$body = curl_exec($ch);

if ($body === false) {
    echo "cURL error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    echo "HTTP $httpCode\n";
    echo $body . "\n";
    exit(1);
}

// ---------------------------------------------------------
// Decode and validate JSON
// ---------------------------------------------------------

$data = json_decode($body, true);
if (!is_array($data)) {
    echo "Failed to parse JSON\n";
    exit(1);
}

if (!isset($data['status']) || $data['status'] !== 'success') {
    echo "API returned non-success status:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

// ---------------------------------------------------------
// Print clones and save JSON
// ---------------------------------------------------------

$clones = isset($data['clones']) && is_array($data['clones']) ? $data['clones'] : array();

echo "Found " . count($clones) . " clones.\n";
foreach ($clones as $c) {
    $cid      = isset($c['clone_id']) ? $c['clone_id'] : null;
    $nickname = isset($c['nickname']) ? $c['nickname'] : '';
    $status   = isset($c['status'])   ? $c['status']   : null;
    $error    = isset($c['error'])    ? $c['error']    : '';

    echo " - clone_id=$cid, nickname=" . json_encode($nickname) .
         ", status=$status, error=" . json_encode($error) . "\n";
}

if (file_put_contents(
        $outputJsonPath,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ) === false) {
    echo "\nFailed to save response to: $outputJsonPath\n";
    exit(1);
}

echo "\nFull response saved to: $outputJsonPath\n";
exit(0);