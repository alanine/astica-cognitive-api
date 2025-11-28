#!/usr/bin/env node
/**
 * Non-streaming TTS example for astica Voice API (Node.js)
 *
 * - Calls HTTPS POST https://voice.astica.ai/api/tts
 * - Uses expressive GPU voice "expressive_sarah"
 * - Saves:
 *     - WAV audio to:  audio-nonstream.wav
 *     - Full JSON to:  audio-non-stream.json
 *
 * Requirements:
 *   - Node.js 18+ (for built-in global fetch)
 *
 * Usage:
 *   node tts_nonstream.js
 */

const fs = require("fs");
const path = require("path");


// ---------------------------------------------------------------------------
// Configuration (edit these)
// ---------------------------------------------------------------------------

const ASTICA_API_KEY = "YOUR_API_KEY_HERE"; // obtain free api key -> https://astica.ai/api-keys/
const ASTICA_API_ENDPOINT = "https://voice.astica.ai"; // base URL for the API

// Default voice:
const VOICE = "expressive_sarah";
// Other examples:
//   "prog_avery"      - OpenAI / programmable voice
//   "neural_jennifer" - Azure / neural voice
// -> view all voices: https://astica.ai/voice/text-to-speech/

// Text to synthesize
const TEXT = "Hello from astica! This is an example in Node.";

// Request options
const INCLUDE_TIMESTAMPS = true;
const HTTP_TIMEOUT_MS = 30_000;

// Output files
const OUTPUT_WAV_PATH = path.resolve("audio-nonstream.wav");
const OUTPUT_JSON_PATH = path.resolve("audio-non-stream.json");


// ---------------------------------------------------------------------------
// Helper: fetch with timeout
// ---------------------------------------------------------------------------

/**
 * Simple timeout wrapper around fetch, using AbortController.
 */
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
// Helper: call /api/tts (non-streaming) and return parsed JSON
// ---------------------------------------------------------------------------

async function callTtsNonstream({
  apiKey,
  text,
  voice,
  includeTimestamps = true,
  timeoutMs = HTTP_TIMEOUT_MS
}) {
  const url = `${ASTICA_API_ENDPOINT}/api/tts`;

  const payload = {
    tkn: apiKey,
    text,
    voice,
    stream: false,            // non-streaming
    timestamps: includeTimestamps
  };

  const resp = await fetchWithTimeout(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify(payload)
  }, timeoutMs);

  if (!resp.ok) {
    let bodyText = "";
    let errJson = null;
    try {
      bodyText = await resp.text();
      errJson = JSON.parse(bodyText);
    } catch {
      // ignore parse errors
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

    const err = new Error(msg);
    err.status = resp.status;
    err.body = errJson || bodyText;
    throw err;
  }

  let data;
  try {
    data = await resp.json();
  } catch (e) {
    const text = await resp.text().catch(() => "");
    const err = new Error(`Failed to parse JSON response: ${e.message || e}`);
    err.rawBody = text;
    throw err;
  }

  return data;
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

  console.log("Calling astica Voice TTS (non-streaming)...");
  console.log(`  Endpoint: ${ASTICA_API_ENDPOINT}/api/tts`);
  console.log(`  Voice:    ${VOICE}`);
  console.log(`  Text:     ${JSON.stringify(TEXT)}`);
  console.log();

  let data;
  try {
    data = await callTtsNonstream({
      apiKey: ASTICA_API_KEY,
      text: TEXT,
      voice: VOICE,
      includeTimestamps: INCLUDE_TIMESTAMPS,
      timeoutMs: HTTP_TIMEOUT_MS
    });
  } catch (e) {
    console.error("[Network/HTTP error]");
    console.error(e.message || e);
    if (e.body) {
      console.error("Server response:");
      console.error(JSON.stringify(e.body, null, 2));
    }
    process.exitCode = 1;
    return;
  }

  const status = data.status;
  if (status !== "success") {
    console.error(`API returned non-success status: ${JSON.stringify(status)}`);
    console.error("Full response:");
    console.error(JSON.stringify(data, null, 2));
    process.exitCode = 1;
    return;
  }

  const audioB64 = data.audio_b64;
  const audioFormat = data.audio_format || "wav";
  const costUnits = data.cost_units;
  const usedVoice = data.voice;

  if (!audioB64) {
    console.error("Response did not contain audio_b64; full response:");
    console.error(JSON.stringify(data, null, 2));
    process.exitCode = 1;
    return;
  }

  let audioBuffer;
  try {
    audioBuffer = Buffer.from(audioB64, "base64");
  } catch (e) {
    console.error(`Failed to decode audio_b64: ${e.message || e}`);
    process.exitCode = 1;
    return;
  }

  try {
    fs.writeFileSync(OUTPUT_WAV_PATH, audioBuffer);
    fs.writeFileSync(OUTPUT_JSON_PATH, JSON.stringify(data, null, 2), "utf8");
  } catch (e) {
    console.error(`Failed to write output files: ${e.message || e}`);
    process.exitCode = 1;
    return;
  }

  console.log("TTS request completed successfully.");
  console.log(`  Resolved voice: ${usedVoice}`);
  console.log(`  Units billed:   ${costUnits}`);
  console.log(`  Audio format:   ${audioFormat}`);
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
