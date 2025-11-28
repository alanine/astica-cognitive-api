#!/usr/bin/env python3
"""
Delete a user-specific voice clone via astica Voice API.

- Calls POST https://voice.astica.ai/api/voice_clones/:id/delete
- Body: { "tkn": "<API_KEY>" }

IMPORTANT:
  The ":id" here refers to the internal clone row ID (not clone_id).
  Depending on your deployment, you may expose this ID in your clone list
  or manage deletions from the astica dashboard.

Saves response JSON to voice-clone-delete.json.

Setup:
    pip install -r requirements.txt

Usage:
    1) Set CLONE_ROW_ID to the numeric clone row ID you want to delete.
    2) python voice_clone_delete.py
"""

import json
from pathlib import Path

import requests


ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"

# NOTE: This is the internal DB row ID, not the "clone_id" returned from list.
CLONE_ROW_ID = 1

OUTPUT_JSON_PATH = Path("voice-clone-delete.json")



def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    if not CLONE_ROW_ID:
        print("Please set CLONE_ROW_ID to the numeric clone row ID you want to delete.")
        return 1

    url = f"{ASTICA_API_ENDPOINT}/api/voice_clones/{CLONE_ROW_ID}/delete"
    payload = {"tkn": ASTICA_API_KEY}

    print(f"Deleting clone row ID={CLONE_ROW_ID} via {url} ...")

    try:
        resp = requests.post(url, json=payload, timeout=30)
    except requests.RequestException as e:
        print(f"[Network/HTTP error] {e}")
        return 1

    try:
        body = resp.json()
    except Exception:
        body = {"raw": resp.text}

    if not resp.ok:
        print(f"HTTP {resp.status_code}")
        print(json.dumps(body, indent=2))
        return 1

    print("Delete response:")
    print(json.dumps(body, indent=2))

    OUTPUT_JSON_PATH.write_text(json.dumps(body, indent=2), encoding="utf-8")
    print()
    print(f"Response saved to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
