#!/usr/bin/env python3
"""
List your user-specific voice clones from astica Voice API.

- Calls POST https://voice.astica.ai/api/voice_clone_list
- Returns clones you created via /api/voice_clone.
- Prints a summary and saves full JSON to voice-clones-list.json.

Setup:
    pip install -r requirements.txt

Usage:
    python voice_clones_list.py
"""

import json
from pathlib import Path

import requests


ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"

OUTPUT_JSON_PATH = Path("voice-clones-list.json")



def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    url = f"{ASTICA_API_ENDPOINT}/api/voice_clone_list"
    payload = {"tkn": ASTICA_API_KEY}

    print(f"Requesting voice clone list from {url} ...")

    try:
        resp = requests.post(url, json=payload, timeout=30)
    except requests.RequestException as e:
        print(f"[Network/HTTP error] {e}")
        return 1

    if not resp.ok:
        try:
            body = resp.json()
        except Exception:
            body = None
        print(f"HTTP {resp.status_code}")
        if body:
            print(json.dumps(body, indent=2))
        return 1

    try:
        data = resp.json()
    except Exception as e:
        print(f"Failed to parse JSON: {e}")
        return 1

    if data.get("status") != "success":
        print("API returned non-success status:")
        print(json.dumps(data, indent=2))
        return 1

    clones = data.get("clones") or []
    print(f"Found {len(clones)} clones.")

    for c in clones:
        cid = c.get("clone_id")
        nickname = c.get("nickname") or ""
        status = c.get("status")
        error = c.get("error") or ""
        print(f" - clone_id={cid}, nickname={nickname!r}, status={status}, error={error!r}")

    OUTPUT_JSON_PATH.write_text(json.dumps(data, indent=2), encoding="utf-8")
    print()
    print(f"Full response saved to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
