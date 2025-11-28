#!/usr/bin/env node
/**
 * HTTP streaming TTS example for astica Voice API (Node.js)
 *
 * - Calls HTTPS POST https://voice.astica.ai/api/tts with stream=true
 * - For expressive GPU voices ("expressive_sarah"), server returns raw PCM (audio/pcm).
 * - For neural/programmatic voices, server returns WAV (audio/wav).
 *
 * This script:
 *   - Streams audio as it arrives and (optionally) plays it live using `speaker`.
 *   - Saves final audio to:  audio-stream-http.wav
 *   - Saves a small JSON summary to: audio-stream-http.json
 *
 * Requirements:
 *   - Node.js 18+ (global fetch)
 *   - Optional: `npm install speaker` for live playback
 *
 * Usage:
 *   node tts_stream_http.js
 */

"use strict";

const fs = require("fs");
const path = require("path");

// Optional dependency for live playback
let Speaker = null;
try {
  // eslint-disable-next-line global-require
  Speaker = require("speaker");
} catch {
  Speaker = null;
  console.log("WARNING: Node cannot play audio. Please 'npm install speaker'");
}


// ---------------------------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------------------------

const ASTICA_API_KEY = "YOUR_API_KEY_HERE"; // obtain free api key -> https://astica.ai/api-keys/
const ASTICA_API_ENDPOINT = "https://voice.astica.ai";

const VOICE = "expressive_sarah";
// Other examples:
//   "prog_avery"      - OpenAI / programmable voice
//   "neural_jennifer" - Azure / neural voice
// -> view all voices: https://astica.ai/voice/text-to-speech/

const TEXT = "Hello from astica! This is an example in Node.";

// Stream options
const PLAY_AUDIO_LIVE = true;   // Try to play audio as it streams (if `npm install speaker` is available)
const HTTP_TIMEOUT_MS = 60_000;

// Output files
const OUTPUT_WAV_PATH = path.resolve("audio-stream-http.wav");
const OUTPUT_JSON_PATH = path.resolve("audio-stream-http.json");


// ---------------------------------------------------------------------------
// Helper: fetch with timeout
// ---------------------------------------------------------------------------

async function fetchWithTimeout(url, options = {}, timeoutMs = 30000) {
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const resp = await fetch(url, { ...options, signal: controller.signal });
    return resp;
  } finally {
    clearTimeout(id);
  }
}


// ---------------------------------------------------------------------------
// Helper: build WAV header for raw PCM (16‑bit mono)
// ---------------------------------------------------------------------------

function buildWavHeader(pcmByteLength, sampleRate, numChannels = 1, bitsPerSample = 16) {
  const header = Buffer.alloc(44);
  const blockAlign = (numChannels * bitsPerSample) / 8;
  const byteRate = sampleRate * blockAlign;
  const dataLen = pcmByteLength;
  const riffSize = 36 + dataLen;

  header.write("RIFF", 0);
  header.writeUInt32LE(riffSize, 4);
  header.write("WAVE", 8);
  header.write("fmt ", 12);
  header.writeUInt32LE(16, 16); // PCM header size
  header.writeUInt16LE(1, 20);  // PCM format
  header.writeUInt16LE(numChannels, 22);
  header.writeUInt32LE(sampleRate, 24);
  header.writeUInt32LE(byteRate, 28);
  header.writeUInt16LE(blockAlign, 32);
  header.writeUInt16LE(bitsPerSample, 34);
  header.write("data", 36);
  header.writeUInt32LE(dataLen, 40);

  return header;
}


// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

async function main() {
  if (!ASTICA_API_KEY || ASTICA_API_KEY === "YOUR_API_KEY_HERE") {
    console.error("Please set ASTICA_API_KEY at the top of this file.");
    process.exitCode = 1;
    return;
  }

  const canPlayLive = PLAY_AUDIO_LIVE && !!Speaker;

  if (PLAY_AUDIO_LIVE && !Speaker) {
    console.log("`speaker` module not installed; live playback disabled.");
    console.log("Install with: npm install speaker");
  }

  const url = `${ASTICA_API_ENDPOINT}/api/tts`;
  const payload = {
    tkn: ASTICA_API_KEY,
    text: TEXT,
    voice: VOICE,
    stream: true,
    timestamps: false
  };

  console.log("Calling astica Voice TTS (HTTP streaming)...");
  console.log(`  Endpoint: ${url}`);
  console.log(`  Voice:    ${VOICE}`);
  console.log(`  Text:     ${JSON.stringify(TEXT)}`);
  console.log();

  let resp;
  try {
    resp = await fetchWithTimeout(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    }, HTTP_TIMEOUT_MS);
  } catch (e) {
    console.error("[Network/HTTP error]", e.message || e);
    process.exitCode = 1;
    return;
  }

  if (!resp.ok) {
    let bodyText = "";
    let errJson = null;
    try {
      bodyText = await resp.text();
      errJson = JSON.parse(bodyText);
    } catch {
      // ignore
    }

    let msg = `HTTP ${resp.status}`;
    if (errJson && typeof errJson === "object") {
      const serverErr = errJson.error || errJson.status;
      if (serverErr) {
        msg += ` – server error: ${serverErr}`;
      }
    } else if (bodyText) {
      msg += ` – body: ${bodyText}`;
    }
    console.error(msg);
    process.exitCode = 1;
    return;
  }

  const contentType = (resp.headers.get("content-type") || "").toLowerCase();
  console.log(`Server Content-Type: ${contentType}`);

  let audioFormat = null;
  let sampleRate = null;

  if (!resp.body) {
    console.error("No response body received (no streaming).");
    process.exitCode = 1;
    return;
  }

  // Expressive GPU (audio/pcm)
  if (contentType.includes("audio/pcm")) {
    audioFormat = "pcm_s16le";
    sampleRate = 24000; // default GPU expressive

    const pcmChunks = [];
    let speaker = null;

    if (canPlayLive) {
      try {
        speaker = new Speaker({
          channels: 1,
          bitDepth: 16,
          sampleRate
        });
      } catch (e) {
        console.warn("Could not open audio device for playback:", e.message || e);
        console.warn("Continuing without live playback.");
        speaker = null;
      }
    }

    const reader = resp.body.getReader();
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      if (!value || !value.length) continue;

      const chunk = Buffer.from(value);
      pcmChunks.push(chunk);

      if (speaker) {
        speaker.write(chunk);
      }
    }

    if (speaker) {
      speaker.end();
    }

    const pcmBuf = Buffer.concat(pcmChunks);
    const header = buildWavHeader(pcmBuf.length, sampleRate, 1, 16);
    const wavBuf = Buffer.concat([header, pcmBuf]);
    fs.writeFileSync(OUTPUT_WAV_PATH, wavBuf);
  }
  // Neural/OpenAI: audio/wav
  else if (contentType.includes("audio/wav")) {
    audioFormat = "wav";

    const outStream = fs.createWriteStream(OUTPUT_WAV_PATH);
    let headerBuf = Buffer.alloc(0);
    let headerParsed = false;
    let speaker = null;

    const reader = resp.body.getReader();
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      if (!value || !value.length) continue;

      const chunk = Buffer.from(value);
      outStream.write(chunk);

      if (!canPlayLive) {
        continue;
      }

      if (!headerParsed) {
        headerBuf = Buffer.concat([headerBuf, chunk]);
        if (headerBuf.length < 44) {
          continue;
        }

        const header = headerBuf.slice(0, 44);
        sampleRate = header.readUInt32LE(24);  // offset 24
        const numChannels = header.readUInt16LE(22);
        const bitsPerSample = header.readUInt16LE(34);
        const firstPcm = headerBuf.slice(44);

        try {
          speaker = new Speaker({
            channels: numChannels,
            bitDepth: bitsPerSample,
            sampleRate
          });
          if (firstPcm.length) {
            speaker.write(firstPcm);
          }
        } catch (e) {
          console.warn("Could not open audio device for playback:", e.message || e);
          console.warn("Continuing without live playback.");
          speaker = null;
        }

        headerParsed = true;
      } else if (speaker) {
        speaker.write(chunk);
      }
    }

    outStream.end();
    if (speaker) {
      speaker.end();
    }
  } else {
    console.error("Unexpected Content-Type; expected audio/pcm or audio/wav.");
    // Drain body
    await resp.arrayBuffer().catch(() => {});
    process.exitCode = 1;
    return;
  }

  // Summary JSON
  const summary = {
    endpoint: url,
    voice: VOICE,
    text: TEXT,
    stream: true,
    content_type: contentType,
    audio_format: audioFormat,
    sample_rate: sampleRate,
    note:
      "Streaming HTTP does not return billing metadata (cost_units). " +
      "Use non-streaming (/api/tts with stream=false) or WebSockets (/ws/api) " +
      "if you need per-request billing info."
  };

  fs.writeFileSync(OUTPUT_JSON_PATH, JSON.stringify(summary, null, 2), "utf8");

  console.log();
  console.log("HTTP streaming TTS completed.");
  console.log(`  Audio format: ${audioFormat}`);
  console.log(`  Sample rate:  ${sampleRate}`);
  console.log();
  console.log(`Saved audio to: ${OUTPUT_WAV_PATH}`);
  console.log(`Saved JSON  to: ${OUTPUT_JSON_PATH}`);
}


if (require.main === module) {
  main().catch((err) => {
    console.error("Unexpected error:", err);
    process.exitCode = 1;
  });
}
