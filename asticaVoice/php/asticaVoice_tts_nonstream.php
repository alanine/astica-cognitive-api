#!/usr/bin/env php
<?php
// Simple non‑streaming TTS example for astica Voice API (PHP)
//
// What this script does:
//   - Sends text to https://voice.astica.ai/api/tts
//   - Uses the expressive voice "expressive_sarah"
//   - Saves:
//       * WAV audio to:  audio-nonstream.wav
//       * Full JSON to:  audio-non-stream.json
//
// Requirements:
//   - PHP 7+
//   - cURL extension enabled
//
// Usage:
//   php tts_nonstream_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------------------

$apiKey   = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$endpoint = 'https://voice.astica.ai/api/tts';

// Default voice and text
$voice = 'expressive_sarah';
// Other examples:
//   'prog_avery'      - OpenAI / programmable voice
//   'neural_jennifer' - Azure / neural voice
$text  = 'Hello from astica.ai! This is a non-streaming expressive TTS example in PHP.';

$includeTimestamps = true;     // true/false
$timeoutSeconds    = 30;       // HTTP timeout in seconds

$wavPath  = __DIR__ . '/audio-nonstream.wav';
$jsonPath = __DIR__ . '/audio-non-stream.json';

// ---------------------------------------------------------------------
// Basic checks
// ---------------------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Build request payload
// ---------------------------------------------------------------------

$payload = array(
    'tkn'        => $apiKey,
    'text'       => $text,
    'voice'      => $voice,
    'stream'     => false,           // non-streaming
    'timestamps' => $includeTimestamps,
);

echo "Calling astica Voice TTS (non-streaming)...\n";
echo "  Endpoint: $endpoint\n";
echo "  Voice:    $voice\n";
echo "  Text:     " . json_encode($text) . "\n\n";

// ---------------------------------------------------------------------
// Make HTTP request
// ---------------------------------------------------------------------

$ch = curl_init($endpoint);
if ($ch === false) {
    echo "Failed to initialize cURL\n";
    exit(1);
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);

$responseBody = curl_exec($ch);

if ($responseBody === false) {
    echo "cURL error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    echo "HTTP error: $httpCode\n";
    echo $responseBody . "\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Decode and validate JSON
// ---------------------------------------------------------------------

$data = json_decode($responseBody, true);
if (!is_array($data)) {
    echo "Failed to decode JSON response:\n";
    echo $responseBody . "\n";
    exit(1);
}

if (!isset($data['status']) || $data['status'] !== 'success') {
    echo "API returned non-success status:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Extract audio and save files
// ---------------------------------------------------------------------

$audioB64    = isset($data['audio_b64'])    ? $data['audio_b64']    : null;
$audioFormat = isset($data['audio_format']) ? $data['audio_format'] : 'wav';
$costUnits   = isset($data['cost_units'])   ? $data['cost_units']   : null;
$usedVoice   = isset($data['voice'])        ? $data['voice']        : null;

if (!$audioB64) {
    echo "Response did not contain audio_b64; full response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

$audioBytes = base64_decode($audioB64, true);
if ($audioBytes === false) {
    echo "Failed to decode audio_b64\n";
    exit(1);
}

if (file_put_contents($wavPath, $audioBytes) === false) {
    echo "Failed to write audio file: $wavPath\n";
    exit(1);
}

if (file_put_contents(
        $jsonPath,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ) === false) {
    echo "Failed to write JSON file: $jsonPath\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Done
// ---------------------------------------------------------------------

echo "TTS request completed successfully.\n";
echo "  Resolved voice: " . $usedVoice . "\n";
echo "  Units billed:   " . $costUnits . "\n";
echo "  Audio format:   " . $audioFormat . "\n\n";
echo "Saved audio to: $wavPath\n";
echo "Saved JSON  to: $jsonPath\n";

exit(0);