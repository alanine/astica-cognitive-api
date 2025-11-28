#!/usr/bin/env node
/**
 * Delete a user-specific voice clone via astica Voice API (Node.js).
 *
 * - Calls POST https://voice.astica.ai/api/voice_clones/:id/delete
 * - Body: { "tkn": "<API_KEY>" }
 *
 * NOTE:
 *   The ":id" here refers to the internal clone row ID (not clone_id).
 *   You can obtain it from your own backend or other admin interfaces.
 *
 * Saves response to: voice-clone-delete.json
 *
 * Requirements:
 *   - Node.js 18+
 *
 * Usage:
 *   1) Set CLONE_ROW_ID below.
 *   2) node voice_clone_delete.js
 */

"use strict";

const fs = require("fs");
const path = require("path");

const ASTICA_API_KEY = "YOUR_API_KEY_HERE"; // obtain free api key -> https://astica.ai/api-keys/
const ASTICA_API_ENDPOINT = "https://voice.astica.ai";

// Internal DB row ID to delete (not clone_id):
const CLONE_ROW_ID = 1;

const OUTPUT_JSON_PATH = path.resolve("voice-clone-delete.json");

async function main() {
  if (!ASTICA_API_KEY || ASTICA_API_KEY === "YOUR_API_KEY_HERE") {
    console.error("Please set ASTICA_API_KEY at the top of this file.");
    process.exitCode = 1;
    return;
  }

  if (!CLONE_ROW_ID) {
    console.error("Please set CLONE_ROW_ID to the numeric clone row ID you want to delete.");
    process.exitCode = 1;
    return;
  }

  const url = `${ASTICA_API_ENDPOINT}/api/voice_clones/${CLONE_ROW_ID}/delete`;
  const payload = { tkn: ASTICA_API_KEY };

  console.log(`Deleting clone row ID=${CLONE_ROW_ID} via ${url} ...`);

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

  console.log("Delete response:");
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
