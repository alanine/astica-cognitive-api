#!/usr/bin/env node
/**
 * WebSocket streaming TTS example for astica Voice API (Node.js)
 *
 * - Connects to wss://voice.astica.ai/ws/api
 * - Sends a TTS request (type="tts") with stream=true
 * - Receives:
 *    - tts_ack
 *    - tts_audio (chunk_b64, format="pcm_s16le" or "wav")
 *    - tts_audio_end
 *    - tts_complete
 *
 * format mapping:
 *   - "pcm_s16le": expressive (GPU) and neural (Azure) — raw 16‑bit mono PCM.
 *   - "wav":       programmable (OpenAI) — WAV file bytes.
 *
 * This script:
 *   - Streams audio over WS and optionally plays it live (via `speaker`).
 *   - Saves final audio to: audio-stream-ws.wav
 *   - Saves a JSON summary (ack + complete + error) to: audio-stream-ws.json
 *
 * Requirements:
 *   - Node.js 18+
 *   - npm install ws speaker
 *
 * Usage:
 *   node tts_stream_ws.js
 */

"use strict";

const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const WebSocket = require("ws");

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
const WS_ENDPOINT = "wss://voice.astica.ai/ws/api";

const VOICE = "expressive_sarah";
// Other examples:
//   "prog_avery"      - OpenAI / programmable voice
//   "neural_jennifer" - Azure / neural voice
// -> view all voices: https://astica.ai/voice/text-to-speech/

const TEXT = "Hello from astica! This is an example in Node.";

const PLAY_AUDIO_LIVE = true;

// Output files
const OUTPUT_WAV_PATH = path.resolve("audio-stream-ws.wav");
const OUTPUT_JSON_PATH = path.resolve("audio-stream-ws.json");


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

  const requestId = crypto.randomUUID();

  const state = {
    requestId,
    ack: null,
    complete: null,
    error: null,
    audioFormat: null,      // "pcm_s16le" or "wav"
    sampleRate: null,
    numChannels: 1,
    pcmChunks: [],          // for pcm_s16le -> WAV
    wavChunks: [],          // for wav
    wavHeaderBuf: Buffer.alloc(0),
    wavHeaderParsed: false,
    speaker: null
  };

  console.log("Connecting to WebSocket endpoint...");
  console.log(`  ${WS_ENDPOINT}`);
  console.log(`  Voice: ${VOICE}`);
  console.log(`  Text:  ${JSON.stringify(TEXT)}`);
  console.log();

  const ws = new WebSocket(WS_ENDPOINT);

  ws.on("open", () => {
    console.log("WebSocket open. Sending TTS request...");
    const payload = {
      type: "tts",
      tkn: ASTICA_API_KEY,
      text: TEXT,
      voice: VOICE,
      stream: true,
      timestamps: false,
      request_id: requestId
    };
    ws.send(JSON.stringify(payload));
  });

  ws.on("message", (data) => {
    let msg;
    try {
      msg = JSON.parse(data.toString("utf8"));
    } catch (e) {
      console.warn("[WS] Failed to parse JSON:", e.message || e);
      return;
    }

    const type = msg.type;

    if (type === "tts_ack") {
      state.ack = msg;
      console.log("Received tts_ack:");
      console.log(JSON.stringify(msg, null, 2));
      return;
    }

    if (type === "tts_error") {
      state.error = msg;
      console.log("Received tts_error:");
      console.log(JSON.stringify(msg, null, 2));
      ws.close();
      return;
    }

    if (type === "tts_audio") {
      handleTtsAudio(msg, state, canPlayLive);
      return;
    }

    if (type === "tts_audio_end") {
      console.log("Received tts_audio_end.");
      return;
    }

    if (type === "tts_complete") {
      state.complete = msg;
      console.log("Received tts_complete:");
      console.log(JSON.stringify(msg, null, 2));
      endSpeaker(state);
      ws.close();
      return;
    }
  });

  ws.on("error", (err) => {
    console.error("[WS error]", err.message || err);
    state.error = state.error || { error: String(err) };
    endSpeaker(state);
  });

  ws.on("close", (code, reason) => {
    console.log(`WebSocket closed: code=${code}, reason=${reason}`);
    endSpeaker(state);

    // After close, write audio + JSON summary
    writeOutputs(state);
  });
}

function endSpeaker(state) {
  if (state.speaker) {
    try {
      state.speaker.end();
    } catch {
      // ignore
    }
    state.speaker = null;
  }
}

function handleTtsAudio(msg, state, canPlayLive) {
  const chunkB64 = msg.chunk_b64;
  if (!chunkB64) return;

  let chunk;
  try {
    chunk = Buffer.from(chunkB64, "base64");
  } catch (e) {
    console.warn("[WS] Failed to decode audio chunk:", e.message || e);
    return;
  }

  const fmt = msg.format || state.audioFormat || "pcm_s16le";

  if (!state.audioFormat) {
    state.audioFormat = fmt;
  }

  if (state.audioFormat === "pcm_s16le") {
    handlePcmChunk(chunk, msg, state, canPlayLive);
  } else if (state.audioFormat === "wav") {
    handleWavChunk(chunk, state, canPlayLive);
  } else {
    console.warn("[WS] Unknown audio format:", state.audioFormat);
  }
}

function handlePcmChunk(chunk, msg, state, canPlayLive) {
  state.pcmChunks.push(chunk);

  if (!canPlayLive) {
    return;
  }

  if (!state.sampleRate) {
    state.sampleRate = msg.sample_rate || 24000;
  }

  if (!state.speaker) {
    try {
      state.speaker = new Speaker({
        channels: 1,
        bitDepth: 16,
        sampleRate: state.sampleRate
      });
    } catch (e) {
      console.warn("Could not open audio device for playback:", e.message || e);
      console.warn("Continuing without live playback.");
      state.speaker = null;
      return;
    }
  }

  if (state.speaker) {
    state.speaker.write(chunk);
  }
}

function handleWavChunk(chunk, state, canPlayLive) {
  state.wavChunks.push(chunk);

  if (!canPlayLive) {
    return;
  }

  if (!state.wavHeaderParsed) {
    state.wavHeaderBuf = Buffer.concat([state.wavHeaderBuf, chunk]);
    if (state.wavHeaderBuf.length < 44) {
      return;
    }

    const header = state.wavHeaderBuf.slice(0, 44);
    const sampleRate = header.readUInt32LE(24);
    const numChannels = header.readUInt16LE(22);
    const bitsPerSample = header.readUInt16LE(34);
    const firstPcm = state.wavHeaderBuf.slice(44);

    state.sampleRate = sampleRate;
    state.numChannels = numChannels;

    try {
      state.speaker = new Speaker({
        channels: numChannels,
        bitDepth: bitsPerSample,
        sampleRate
      });
      if (firstPcm.length) {
        state.speaker.write(firstPcm);
      }
    } catch (e) {
      console.warn("Could not open audio device for playback:", e.message || e);
      console.warn("Continuing without live playback.");
      state.speaker = null;
    }

    state.wavHeaderParsed = true;
  } else if (state.speaker) {
    state.speaker.write(chunk);
  }
}

function writeOutputs(state) {
  const { audioFormat, sampleRate, numChannels } = state;
  const wavPath = OUTPUT_WAV_PATH;
  const jsonPath = OUTPUT_JSON_PATH;

  if (audioFormat === "pcm_s16le" && state.pcmChunks.length) {
    const pcmBuf = Buffer.concat(state.pcmChunks);
    const sr = sampleRate || 24000;
    const header = buildWavHeader(pcmBuf.length, sr, 1, 16);
    const wavBuf = Buffer.concat([header, pcmBuf]);
    fs.writeFileSync(wavPath, wavBuf);
    console.log(`Saved PCM-based WAV to: ${wavPath}`);
  } else if (audioFormat === "wav" && state.wavChunks.length) {
    const wavBuf = Buffer.concat(state.wavChunks);
    fs.writeFileSync(wavPath, wavBuf);
    console.log(`Saved WAV to: ${wavPath}`);
  } else {
    console.log("No audio bytes collected; nothing to save.");
  }

  const summary = {
    request_id: state.requestId,
    voice: VOICE,
    text: TEXT,
    audio_format: audioFormat,
    sample_rate: sampleRate,
    num_channels: numChannels,
    ack: state.ack,
    complete: state.complete,
    error: state.error
  };

  fs.writeFileSync(jsonPath, JSON.stringify(summary, null, 2), "utf8");
  console.log(`Saved JSON summary to: ${jsonPath}`);

  if (state.error && !state.complete) {
    process.exitCode = 1;
  }
}


if (require.main === module) {
  main().catch((err) => {
    console.error("Unexpected error:", err);
    process.exitCode = 1;
  });
}