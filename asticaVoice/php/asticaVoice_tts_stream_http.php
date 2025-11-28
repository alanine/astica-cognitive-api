#!/usr/bin/env php
<?php
// Simple HTTP streaming TTS example for astica Voice API (PHP)
//
// What this script does:
//   - Sends text to https://voice.astica.ai/api/tts with stream=true
//   - For expressive GPU voices ("expressive_sarah"): receives audio/pcm (16‑bit PCM)
//   - For neural/programmatic voices: receives audio/wav
//   - Streams audio as it arrives using cURL callbacks
//   - Optionally plays it live with ffplay (if installed)
//   - Saves:
//       * WAV audio to:  audio-stream-http.wav
//       * JSON summary to: audio-stream-http.json
//
// Requirements:
//   - PHP 7+
//   - cURL extension enabled
//   - Optional for live playback:
//       * ffplay (from ffmpeg) available in PATH
//
// Usage:
//   php tts_stream_http_simple.php

error_reporting(E_ALL);
ini_set('display_errors', '1');

// ---------------------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------------------

$apiKey   = 'YOUR_API_KEY_HERE'; // get a free key: https://astica.ai/api-keys/
$url      = 'https://voice.astica.ai/api/tts';

// Default voice and text
$voice = 'expressive_sarah';
// Other examples:
//   'prog_avery'      - OpenAI / programmable voice
//   'neural_jennifer' - Azure / neural voice
$text  = 'Hello from astica.ai! This is an HTTP streaming TTS example in PHP.';

$httpTimeout     = 60;          // HTTP timeout in seconds
$playAudioLive   = true;        // requires ffplay in PATH

$wavPath   = __DIR__ . '/audio-stream-http.wav';
$jsonPath  = __DIR__ . '/audio-stream-http.json';
$tmpPcmPath = __DIR__ . '/_tmp_stream_audio.pcm'; // temp raw PCM (for expressive)

// ---------------------------------------------------------------------
// Basic checks
// ---------------------------------------------------------------------

if ($apiKey === 'YOUR_API_KEY_HERE') {
    echo "Please set \$apiKey at the top of this file.\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Helper: build minimal WAV header from raw PCM
// ---------------------------------------------------------------------

function buildWavHeader($pcmLen, $sampleRate, $numChannels = 1, $bitsPerSample = 16)
{
    $blockAlign = (int)(($numChannels * $bitsPerSample) / 8);
    $byteRate   = $sampleRate * $blockAlign;
    $dataLen    = $pcmLen;
    $riffSize   = 36 + $dataLen;

    return
        'RIFF' .
        pack('V', $riffSize) .
        'WAVE' .
        'fmt ' .
        pack('V', 16) .                // header size
        pack('v', 1) .                 // PCM format
        pack('v', $numChannels) .
        pack('V', $sampleRate) .
        pack('V', $byteRate) .
        pack('v', $blockAlign) .
        pack('v', $bitsPerSample) .
        'data' .
        pack('V', $dataLen);
}

// ---------------------------------------------------------------------
// Build request payload
// ---------------------------------------------------------------------

$payload = array(
    'tkn'        => $apiKey,
    'text'       => $text,
    'voice'      => $voice,
    'stream'     => true,
    'timestamps' => false,
);

echo "Calling astica Voice TTS (HTTP streaming)...\n";
echo "  Endpoint: $url\n";
echo "  Voice:    $voice\n";
echo "  Text:     " . json_encode($text) . "\n\n";

// ---------------------------------------------------------------------
// Make HTTP request with streaming callbacks
// ---------------------------------------------------------------------

$ch = curl_init($url);
if ($ch === false) {
    echo "Failed to initialize cURL\n";
    exit(1);
}

$contentType  = null;
$isPcm        = false;
$isWav        = false;
$tmpPcmHandle = null;
$wavHandle    = null;
$ffplayHandle = null;

// Capture Content-Type from response headers
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$contentType) {
    $len = strlen($header);
    if (stripos($header, 'Content-Type:') === 0) {
        $contentType = trim(substr($header, strlen('Content-Type:')));
    }
    return $len;
});

// Stream body chunks as they arrive
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) use (
    &$contentType, &$isPcm, &$isWav,
    &$tmpPcmHandle, &$wavHandle, &$ffplayHandle,
    $tmpPcmPath, $wavPath, $playAudioLive
) {
    $chunkLen = strlen($data);
    if ($chunkLen === 0) {
        return 0;
    }

    // Decide format based on Content-Type, once it is known
    if ($contentType !== null && !$isPcm && !$isWav) {
        $lc = strtolower($contentType);
        if (strpos($lc, 'audio/pcm') !== false) {
            $isPcm = true;
        } elseif (strpos($lc, 'audio/wav') !== false) {
            $isWav = true;
        }
    }

    // Expressive voice: audio/pcm
    if ($isPcm) {
        if ($tmpPcmHandle === null) {
            $tmpPcmHandle = fopen($tmpPcmPath, 'wb');
        }
        fwrite($tmpPcmHandle, $data);

        if ($playAudioLive && $ffplayHandle === null) {
            // ffplay for raw PCM, 24 kHz, mono, s16le
            $cmd = 'ffplay -autoexit -nodisp -f s16le -ar 24000 -ac 1 - 2>/dev/null';
            $ffplayHandle = @popen($cmd, 'w');
            if ($ffplayHandle === false) {
                $ffplayHandle = null;
                fwrite(STDERR, "Could not start ffplay for live playback (audio/pcm).\n");
            }
        }
        if ($ffplayHandle !== null) {
            fwrite($ffplayHandle, $data);
        }
    }
    // Neural / programmable voice: audio/wav
    elseif ($isWav) {
        if ($wavHandle === null) {
            $wavHandle = fopen($wavPath, 'wb');
        }
        fwrite($wavHandle, $data);

        if ($playAudioLive && $ffplayHandle === null) {
            $cmd = 'ffplay -autoexit -nodisp - 2>/dev/null';
            $ffplayHandle = @popen($cmd, 'w');
            if ($ffplayHandle === false) {
                $ffplayHandle = null;
                fwrite(STDERR, "Could not start ffplay for live playback (audio/wav).\n");
            }
        }
        if ($ffplayHandle !== null) {
            fwrite($ffplayHandle, $data);
        }
    }

    return $chunkLen;
});

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
curl_setopt($ch, CURLOPT_TIMEOUT, $httpTimeout);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // we handle streaming via callbacks
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$ok        = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = $ok === false ? curl_error($ch) : null;

curl_close($ch);

// Close any open handles
if ($ffplayHandle !== null) {
    pclose($ffplayHandle);
}
if ($tmpPcmHandle !== null) {
    fclose($tmpPcmHandle);
}
if ($wavHandle !== null) {
    fclose($wavHandle);
}

// Check for cURL error
if ($ok === false) {
    echo "cURL error: $curlError\n";
    exit(1);
}

// HTTP status check
if ($httpCode < 200 || $httpCode >= 300) {
    echo "HTTP $httpCode while streaming audio.\n";
    exit(1);
}

// ---------------------------------------------------------------------
// Finalize audio file and write summary JSON
// ---------------------------------------------------------------------

$audioFormat = null;
$sampleRate  = null;

if ($isPcm) {
    $audioFormat = 'pcm_s16le';
    $sampleRate  = 24000;

    $pcmSize = is_file($tmpPcmPath) ? filesize($tmpPcmPath) : 0;
    if ($pcmSize === false || $pcmSize <= 0) {
        echo "No PCM data received.\n";
        exit(1);
    } else {
        $pcm    = file_get_contents($tmpPcmPath);
        $header = buildWavHeader($pcmSize, $sampleRate, 1, 16);
        file_put_contents($wavPath, $header . $pcm);
        @unlink($tmpPcmPath);
    }
} elseif ($isWav) {
    $audioFormat = 'wav';
    // $wavPath was written during streaming
} else {
    echo "Unexpected Content-Type; expected audio/pcm or audio/wav.\n";
    exit(1);
}

$summary = array(
    'endpoint'     => $url,
    'voice'        => $voice,
    'text'         => $text,
    'stream'       => true,
    'content_type' => $contentType,
    'audio_format' => $audioFormat,
    'sample_rate'  => $sampleRate,
    'note'         => 'HTTP streaming does not include billing metadata (cost_units). '
                    . 'Use non-streaming (/api/tts with stream=false) or WebSockets (/ws/api) '
                    . 'if you need per-request billing info.',
);

file_put_contents(
    $jsonPath,
    json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

// ---------------------------------------------------------------------
// Done
// ---------------------------------------------------------------------

echo "HTTP streaming TTS completed.\n";
echo "  Content-Type: $contentType\n";
echo "  Audio format: $audioFormat\n";
echo "  Sample rate:  " . ($sampleRate !== null ? $sampleRate : 'N/A') . "\n\n";
echo "Saved audio to: $wavPath\n";
echo "Saved JSON  to: $jsonPath\n";

exit(0);