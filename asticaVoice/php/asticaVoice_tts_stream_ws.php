#!/usr/bin/env php
<?php
// Simple WebSocket streaming TTS example for astica Voice API (PHP)
//
// What this script does:
//   - Connects to wss://voice.astica.ai/ws/api
//   - Sends a TTS request (type="tts", stream=true)
//   - Receives audio chunks over WebSocket
//   - Optionally plays audio live via ffplay
//   - Saves final audio to: audio-stream-ws.wav
//   - Saves a JSON summary (ack + complete + error) to: audio-stream-ws.json
//
// Requirements:
//   - PHP 7+
//   - composer install (textalk/websocket)
//   - vendor/autoload.php present
//   - Optional for live playback:
//       * ffplay available in PATH
//
// Usage:
//   php tts_stream_ws_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

use WebSocket\Client;

// ---------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------

$apiKey      = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$wsEndpoint  = 'wss://voice.astica.ai/ws/api';

$voice = 'expressive_sarah';
// Other examples:
//   'prog_avery'      - OpenAI / programmable voice
//   'neural_jennifer' - Azure / neural voice

$text          = 'Hello from astica.ai! This is a WebSocket streaming TTS example in PHP.';
$playAudioLive = true; // requires ffplay in PATH

$outputWavPath  = __DIR__ . '/audio-stream-ws.wav';
$outputJsonPath = __DIR__ . '/audio-stream-ws.json';

// ---------------------------------------------------------
// Helper: build a WAV header for raw PCM data
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

// ---------------------------------------------------------
// Audio chunk handlers
// ---------------------------------------------------------

function handle_tts_audio($data, &$state)
{
    $chunkB64 = isset($data['chunk_b64']) ? $data['chunk_b64'] : null;
    if (!$chunkB64) {
        return;
    }

    $chunk = base64_decode($chunkB64, true);
    if ($chunk === false) {
        fwrite(STDERR, "[WS] Failed to decode audio chunk.\n");
        return;
    }

    $fmt = isset($data['format'])
        ? $data['format']
        : (isset($state['audio_format']) ? $state['audio_format'] : 'pcm_s16le');

    if ($state['audio_format'] === null) {
        $state['audio_format'] = $fmt;
    }

    if ($state['audio_format'] === 'pcm_s16le') {
        handle_pcm_chunk($chunk, $data, $state);
    } elseif ($state['audio_format'] === 'wav') {
        handle_wav_chunk($chunk, $state);
    }
}

function handle_pcm_chunk($chunk, $data, &$state)
{
    global $playAudioLive;

    $state['pcm_data'] .= $chunk;

    if (!$playAudioLive) {
        return;
    }

    if ($state['sample_rate'] === null) {
        $state['sample_rate'] = isset($data['sample_rate']) ? (int)$data['sample_rate'] : 24000;
    }

    if ($state['ffplay_handle'] === null) {
        $cmd = 'ffplay -autoexit -nodisp -f s16le -ar ' . $state['sample_rate'] . ' -ac 1 - 2>/dev/null';
        $handle = @popen($cmd, 'w');
        if ($handle === false) {
            $state['ffplay_handle'] = null;
            fwrite(STDERR, "Could not start ffplay for live playback (pcm_s16le).\n");
        } else {
            $state['ffplay_handle'] = $handle;
        }
    }

    if ($state['ffplay_handle'] !== null) {
        fwrite($state['ffplay_handle'], $chunk);
    }
}

function handle_wav_chunk($chunk, &$state)
{
    global $playAudioLive;

    $state['wav_data'] .= $chunk;

    if (!$playAudioLive) {
        return;
    }

    // Wait until we have at least a full WAV header
    if (!$state['wav_header_parsed']) {
        $state['wav_header_buf'] .= $chunk;
        if (strlen($state['wav_header_buf']) < 44) {
            return;
        }

        $header = substr($state['wav_header_buf'], 0, 44);
        $sr     = unpack('V', substr($header, 24, 4))[1];
        $ch     = unpack('v', substr($header, 22, 2))[1];

        $state['sample_rate']  = $sr;
        $state['num_channels'] = $ch;

        $cmd = 'ffplay -autoexit -nodisp - 2>/dev/null';
        $handle = @popen($cmd, 'w');
        if ($handle === false) {
            $state['ffplay_handle'] = null;
            fwrite(STDERR, "Could not start ffplay for live playback (wav).\n");
        } else {
            $state['ffplay_handle'] = $handle;
            // Send whatever we have (header + first chunk) to ffplay
            fwrite($state['ffplay_handle'], $state['wav_header_buf']);
        }

        $state['wav_header_parsed'] = true;
    } else {
        if ($state['ffplay_handle'] !== null) {
            fwrite($state['ffplay_handle'], $chunk);
        }
    }
}

// ---------------------------------------------------------
// Main logic
// ---------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

$requestId = bin2hex(random_bytes(16));

$state = array(
    'request_id'        => $requestId,
    'ack'               => null,
    'complete'          => null,
    'error'             => null,
    'audio_format'      => null,  // "pcm_s16le" or "wav"
    'sample_rate'       => null,
    'num_channels'      => 1,
    'pcm_data'          => '',
    'wav_data'          => '',
    'wav_header_buf'    => '',
    'wav_header_parsed' => false,
    'ffplay_handle'     => null,
);

echo "Connecting to WebSocket endpoint...\n";
echo "  $wsEndpoint\n";
echo "  Voice: $voice\n";
echo "  Text:  " . json_encode($text) . "\n\n";

// Connect
try {
    $client = new Client($wsEndpoint);
} catch (\Exception $e) {
    fwrite(STDERR, "[WS connect error] " . $e->getMessage() . "\n");
    exit(1);
}

// Send TTS request
$requestPayload = array(
    'type'       => 'tts',
    'tkn'        => $apiKey,
    'text'       => $text,
    'voice'      => $voice,
    'stream'     => true,
    'timestamps' => false,
    'request_id' => $requestId,
);

$client->send(json_encode($requestPayload, JSON_UNESCAPED_SLASHES));

// Receive loop
while (true) {
    try {
        $message = $client->receive();
    } catch (\Exception $e) {
        fwrite(STDERR, "[WS error] " . $e->getMessage() . "\n");
        if ($state['ffplay_handle'] !== null) {
            pclose($state['ffplay_handle']);
        }
        break;
    }

    if ($message === null || $message === '') {
        // Connection closed or empty message
        break;
    }

    $data = json_decode($message, true);
    if (!is_array($data)) {
        continue;
    }

    $type = isset($data['type']) ? $data['type'] : null;

    if ($type === 'tts_ack') {
        $state['ack'] = $data;
        echo "Received tts_ack:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        continue;
    }

    if ($type === 'tts_error') {
        $state['error'] = $data;
        echo "Received tts_error:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        break;
    }

    if ($type === 'tts_audio') {
        handle_tts_audio($data, $state);
        continue;
    }

    if ($type === 'tts_audio_end') {
        echo "Received tts_audio_end.\n";
        continue;
    }

    if ($type === 'tts_complete') {
        $state['complete'] = $data;
        echo "Received tts_complete:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        break;
    }
}

// Close ffplay if running
if ($state['ffplay_handle'] !== null) {
    pclose($state['ffplay_handle']);
    $state['ffplay_handle'] = null;
}

// Save audio to WAV file
if ($state['audio_format'] === 'pcm_s16le' && $state['pcm_data'] !== '') {
    $sr     = $state['sample_rate'] !== null ? $state['sample_rate'] : 24000;
    $pcmLen = strlen($state['pcm_data']);
    $header = build_wav_header($pcmLen, $sr, 1, 16);
    file_put_contents($outputWavPath, $header . $state['pcm_data']);
    echo "Saved PCM-based WAV to: $outputWavPath\n";
} elseif ($state['audio_format'] === 'wav' && $state['wav_data'] !== '') {
    file_put_contents($outputWavPath, $state['wav_data']);
    echo "Saved WAV to: $outputWavPath\n";
} else {
    echo "No audio bytes collected; nothing to save.\n";
}

// Save summary JSON
$summary = array(
    'request_id'   => $state['request_id'],
    'voice'        => $voice,
    'text'         => $text,
    'audio_format' => $state['audio_format'],
    'sample_rate'  => $state['sample_rate'],
    'num_channels' => $state['num_channels'],
    'ack'          => $state['ack'],
    'complete'     => $state['complete'],
    'error'        => $state['error'],
);

file_put_contents(
    $outputJsonPath,
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);
echo "Saved JSON summary to: $outputJsonPath\n";

if ($state['error'] !== null && $state['complete'] === null) {
    exit(1);
}

exit(0);