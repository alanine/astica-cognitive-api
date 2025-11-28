#!/usr/bin/env python3
"""
Fetch public expressive voice list from astica Voice API.

- Calls POST https://voice.astica.ai/api/voice_list
- Requires a valid API key.
- Prints basic info and saves the full JSON to voice-list.json.

Setup:
    pip install -r requirements.txt

Usage:
    python voice_list.py
"""

import json
from pathlib import Path

import requests


ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"

OUTPUT_JSON_PATH = Path("voice-list.json")



def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    url = f"{ASTICA_API_ENDPOINT}/api/voice_list"
    payload = {"tkn": ASTICA_API_KEY}

    print(f"Requesting voice list from {url} ...")

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

    voices = data.get("voices") or []
    print(f"Received {len(voices)} voices.")

    # Show first few
    for v in voices[:10]:
        alias = v.get("alias") or ""
        display_name = v.get("display_name") or ""
        label = v.get("label") or ""
        print(f" - {alias} | {display_name} | {label}")

    OUTPUT_JSON_PATH.write_text(json.dumps(data, indent=2), encoding="utf-8")
    print()
    print(f"Full response saved to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
