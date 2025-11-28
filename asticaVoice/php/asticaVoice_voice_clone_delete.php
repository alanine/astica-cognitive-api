#!/usr/bin/env php
<?php
// Simple script to delete a user-specific voice clone via astica Voice API (PHP)
//
// What this script does:
//   - Calls: POST https://voice.astica.ai/api/voice_clones/:id/delete
//   - Body: { "tkn": "<API_KEY>" }
//   - NOTE: ":id" is the internal clone row ID (NOT clone_id).
//   - Saves the response JSON to: voice-clone-delete.json
//
// Requirements:
//   - PHP 7+
//   - cURL extension enabled
//
// Usage:
//   1) Set $apiKey and $cloneRowId below.
//   2) php voice_clone_delete_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------------------

$apiKey     = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$apiBaseUrl = 'https://voice.astica.ai';

// Internal DB row ID to delete (NOT clone_id)
$cloneRowId = 1;

$outputJsonPath = __DIR__ . '/voice-clone-delete.json';

// ---------------------------------------------------------------------
// Basic checks
// ---------------------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

if (!$cloneRowId || !is_numeric($cloneRowId)) {
    echo "Please set \$cloneRowId to the numeric clone row ID you want to delete.\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Build request
// ---------------------------------------------------------------------

$url     = $apiBaseUrl . '/api/voice_clones/' . $cloneRowId . '/delete';
$payload = array('tkn' => $apiKey);

echo "Deleting clone row ID=" . $cloneRowId . " via $url ...\n";

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

// ---------------------------------------------------------------------
// Execute request
// ---------------------------------------------------------------------

$body = curl_exec($ch);
if ($body === false) {
    echo "cURL error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Try to decode JSON; if not JSON, keep raw body
$decoded = json_decode($body, true);
if (!is_array($decoded)) {
    $decoded = array('raw' => $body);
}

// ---------------------------------------------------------------------
// Check HTTP status and show result
// ---------------------------------------------------------------------

if ($httpCode < 200 || $httpCode >= 300) {
    echo "HTTP $httpCode\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

echo "Delete response:\n";
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Save response JSON to file
file_put_contents(
    $outputJsonPath,
    json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\nResponse saved to: " . $outputJsonPath . "\n";

exit(0);