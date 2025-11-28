#!/usr/bin/env node
/**
 * Fetch public expressive voice list from astica Voice API (Node.js).
 *
 * - Calls POST https://voice.astica.ai/api/voice_list
 * - Requires a valid API key.
 * - Prints a summary and saves full JSON to voice-list.json.
 *
 * Requirements:
 *   - Node.js 18+ (global fetch)
 *
 * Usage:
 *   node voice_list.js
 */

"use strict";

const fs = require("fs");
const path = require("path");

const ASTICA_API_KEY = "YOUR_API_KEY_HERE"; // obtain free api key -> https://astica.ai/api-keys/
const ASTICA_API_ENDPOINT = "https://voice.astica.ai";

const OUTPUT_JSON_PATH = path.resolve("voice-list.json");

async function main() {
  if (!ASTICA_API_KEY || ASTICA_API_KEY === "YOUR_API_KEY_HERE") {
    console.error("Please set ASTICA_API_KEY at the top of this file.");
    process.exitCode = 1;
    return;
  }

  const url = `${ASTICA_API_ENDPOINT}/api/voice_list`;
  const payload = { tkn: ASTICA_API_KEY };

  console.log(`Requesting voice list from ${url} ...`);

  let resp;
  try {
    resp = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });
  } catch (e) {
    console.error("[Network/HTTP error]", e.message || e);
    process.exitCode = 1;
    return;
  }

  if (!resp.ok) {
    let bodyText = "";
    let body = null;
    try {
      bodyText = await resp.text();
      body = JSON.parse(bodyText);
    } catch {
      // ignore
    }

    console.error(`HTTP ${resp.status}`);
    if (body) {
      console.error(JSON.stringify(body, null, 2));
    } else if (bodyText) {
      console.error(bodyText);
    }
    process.exitCode = 1;
    return;
  }

  let data;
  try {
    data = await resp.json();
  } catch (e) {
    console.error("Failed to parse JSON:", e.message || e);
    process.exitCode = 1;
    return;
  }

  if (data.status !== "success") {
    console.error("API returned non-success status:");
    console.error(JSON.stringify(data, null, 2));
    process.exitCode = 1;
    return;
  }

  const voices = data.voices || [];
  console.log(`Received ${voices.length} voices.`);
  for (const v of voices.slice(0, 10)) {
    const alias = v.alias || "";
    const displayName = v.display_name || "";
    const label = v.label || "";
    console.log(` - ${alias} | ${displayName} | ${label}`);
    }

  fs.writeFileSync(OUTPUT_JSON_PATH, JSON.stringify(data, null, 2), "utf8");
  console.log();
  console.log(`Full response saved to: ${OUTPUT_JSON_PATH}`);
}

if (require.main === module) {
  main().catch((err) => {
    console.error("Unexpected error:", err);
    process.exitCode = 1;
  });
}
