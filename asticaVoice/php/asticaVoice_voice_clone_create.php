#!/usr/bin/env php
<?php
// Simple example: create a voice clone via astica Voice API (PHP)
//
// What this script does:
//   - Creates a small demo WAV file on disk
//   - Sends it to https://voice.astica.ai/api/voice_clone
//   - Saves the JSON response to voice-clone-create.json
//
// Requirements:
//   - PHP 7+
//   - cURL extension enabled
//
// Usage:
//   php voice_clone_create_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------

$apiKey   = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$baseUrl  = 'https://voice.astica.ai';

$cloneNickname   = 'PHP Demo Clone';
$outputJsonPath  = __DIR__ . '/voice-clone-create.json';
$demoWavPath     = __DIR__ . '/_demo_clone.wav';

// ---------------------------------------------------------
// WAV helpers: make a tiny sine-wave demo file
// ---------------------------------------------------------

function build_wav_header($pcmLen, $sampleRate, $numChannels = 1, $bitsPerSample = 16)
{
    $header = str_repeat("\0", 44);
    $buffer = fopen('php://memory', 'r+b');
    fwrite($buffer, $header);
    rewind($buffer);

    $blockAlign = (int)(($numChannels * $bitsPerSample) / 8);
    $byteRate   = $sampleRate * $blockAlign;
    $dataLen    = $pcmLen;
    $riffSize   = 36 + $dataLen;

    fwrite($buffer, 'RIFF');
    fwrite($buffer, pack('V', $riffSize));
    fwrite($buffer, 'WAVE');
    fwrite($buffer, 'fmt ');
    fwrite($buffer, pack('V', 16));          // fmt chunk size
    fwrite($buffer, pack('v', 1));           // audio format (PCM)
    fwrite($buffer, pack('v', $numChannels));
    fwrite($buffer, pack('V', $sampleRate));
    fwrite($buffer, pack('V', $byteRate));
    fwrite($buffer, pack('v', $blockAlign));
    fwrite($buffer, pack('v', $bitsPerSample));
    fwrite($buffer, 'data');
    fwrite($buffer, pack('V', $dataLen));

    rewind($buffer);
    $out = stream_get_contents($buffer);
    fclose($buffer);
    return $out;
}

function generate_demo_wav($path, $durationSec = 2.0, $sampleRate = 16000, $freqHz = 220.0)
{
    $numSamples = (int)floor($durationSec * $sampleRate);
    $amplitude  = 0.3;
    $pcm        = fopen('php://memory', 'r+b');

    for ($i = 0; $i < $numSamples; $i++) {
        $t         = $i / $sampleRate;
        $sampleVal = $amplitude * sin(2 * M_PI * $freqHz * $t);
        $clamped   = max(-1.0, min(1.0, $sampleVal));
        $intVal    = (int)round($clamped * 32767);
        fwrite($pcm, pack('v', $intVal & 0xFFFF)); // 16-bit PCM, little-endian
    }

    $pcmData = stream_get_contents($pcm, -1, 0);
    fclose($pcm);

    $pcmLen = strlen($pcmData);
    $header = build_wav_header($pcmLen, $sampleRate, 1, 16);

    file_put_contents($path, $header . $pcmData);
}

// ---------------------------------------------------------
// Main logic
// ---------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

$url = $baseUrl . '/api/voice_clone';
echo "Creating a voice clone via $url ...\n";

// Create a small WAV file to upload
generate_demo_wav($demoWavPath, 2.0, 16000, 220.0);

// Prepare cURL request
$ch = curl_init($url);
if ($ch === false) {
    echo "Failed to initialize cURL\n";
    @unlink($demoWavPath);
    exit(1);
}

$postFields = array(
    'tkn'      => $apiKey,
    'nickname' => $cloneNickname,
    'audio'    => curl_file_create($demoWavPath, 'audio/wav', 'demo_clone.wav'),
);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$body = curl_exec($ch);

if ($body === false) {
    echo "cURL error: " . curl_error($ch) . "\n";
    curl_close($ch);
    @unlink($demoWavPath);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($demoWavPath);

// Decode response
$decoded = json_decode($body, true);
if (!is_array($decoded)) {
    $decoded = array('raw' => $body);
}

// Check HTTP status
if ($httpCode < 200 || $httpCode >= 300) {
    echo "HTTP $httpCode\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(1);
}

// Print and save result
echo "Clone creation response:\n";
echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if (file_put_contents(
        $outputJsonPath,
        json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ) === false) {
    echo "\nFailed to save response to: $outputJsonPath\n";
    exit(1);
}

echo "\nResponse saved to: $outputJsonPath\n";
exit(0);