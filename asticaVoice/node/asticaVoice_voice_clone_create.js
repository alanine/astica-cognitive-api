#!/usr/bin/env node
/**
 * Create a user-specific voice clone via astica Voice API (Node.js).
 *
 * - Calls multipart/form-data POST https://voice.astica.ai/api/voice_clone
 * - Sends:
 *     - tkn       : API key
 *     - nickname  : label for your clone
 *     - audio     : a short demo WAV generated in-memory
 *
 * The clone will be queued for processing. Use voice_clones_list.js
 * to check status.
 *
 * Saves response to: voice-clone-create.json
 *
 * Requirements:
 *   - Node.js 18+ (global fetch, FormData, Blob)
 *
 * Usage:
 *   node voice_clone_create.js
 */

"use strict";

const fs = require("fs");
const path = require("path");

const ASTICA_API_KEY = "YOUR_API_KEY_HERE"; // obtain free api key -> https://astica.ai/api-keys/
const ASTICA_API_ENDPOINT = "https://voice.astica.ai";

const CLONE_NICKNAME = "Node.js Demo Clone";
const OUTPUT_JSON_PATH = path.resolve("voice-clone-create.json");


// ---------------------------------------------------------------------------
// Generate a simple sine-wave WAV buffer (demo only)
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

function generateDemoWavBuffer(durationSec = 2.0, sampleRate = 16000, freqHz = 220.0) {
  const numSamples = Math.floor(durationSec * sampleRate);
  const amplitude = 0.3;
  const pcm = Buffer.alloc(numSamples * 2); // 16-bit mono

  for (let i = 0; i < numSamples; i++) {
    const t = i / sampleRate;
    const sampleVal = amplitude * Math.sin(2 * Math.PI * freqHz * t);
    const clamped = Math.max(-1, Math.min(1, sampleVal));
    const intVal = Math.round(clamped * 32767);
    pcm.writeInt16LE(intVal, i * 2);
  }

  const header = buildWavHeader(pcm.length, sampleRate, 1, 16);
  return Buffer.concat([header, pcm]);
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

  const url = `${ASTICA_API_ENDPOINT}/api/voice_clone`;
  console.log(`Creating a voice clone via ${url} ...`);

  const wavBuffer = generateDemoWavBuffer(2.0, 16000, 220.0);

  // Use Node 18+ FormData + Blob
  const form = new FormData();
  form.append("tkn", ASTICA_API_KEY);
  form.append("nickname", CLONE_NICKNAME);
  form.append("audio", new Blob([wavBuffer], { type: "audio/wav" }), "demo_clone.wav");

  let resp;
  try {
    resp = await fetch(url, {
      method: "POST",
      body: form
    });
  } catch (e) {
    console.error("[Network/HTTP error]", e.message || e);
    process.exitCode = 1;
    return;
  }

  let bodyText;
  let body;
  try {
    bodyText = await resp.text();
    body = JSON.parse(bodyText);
  } catch {
    body = bodyText || "";
  }

  if (!resp.ok) {
    console.error(`HTTP ${resp.status}`);
    console.error(typeof body === "string" ? body : JSON.stringify(body, null, 2));
    process.exitCode = 1;
    return;
  }

  console.log("Clone creation response:");
  console.log(JSON.stringify(body, null, 2));

  fs.writeFileSync(OUTPUT_JSON_PATH, JSON.stringify(body, null, 2), "utf8");
  console.log();
  console.log(`Response saved to: ${OUTPUT_JSON_PATH}`);
}

if (require.main === module) {
  main().catch((err) => {
    console.error("Unexpected error:", err);
    process.exitCode = 1;
  });
}
