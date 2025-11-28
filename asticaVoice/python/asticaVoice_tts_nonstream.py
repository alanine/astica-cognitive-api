#!/usr/bin/env python3
"""
Non-streaming TTS example for astica Voice API (Python)

- Calls HTTPS POST https://voice.astica.ai/api/tts
- Uses expressive GPU voice "expressive_sarah"
- Saves:
    - WAV audio to:  audio-nonstream.wav
    - Full JSON to:  audio-non-stream.json

Setup:
    pip install -r requirements.txt

Usage:
    python tts_nonstream.py
"""

import base64
import json
import sys
from pathlib import Path

import requests


# ---------------------------------------------------------------------------
# Configuration (edit these)
# ---------------------------------------------------------------------------

ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"  # base URL for the API

# Default voice:
VOICE = "expressive_sarah"
# Other examples:
#   "prog_avery"      - OpenAI / programmable voice
#   "neural_jennifer" - Azure / neural voice
# -> view all voices: https://astica.ai/voice/text-to-speech/

# Text to synthesize
TEXT = "Hello from astica! This is an example in Python."

# Request options
INCLUDE_TIMESTAMPS = True
HTTP_TIMEOUT_SECONDS = 30

# Output files
OUTPUT_WAV_PATH = Path("audio-nonstream.wav")
OUTPUT_JSON_PATH = Path("audio-non-stream.json")


# ---------------------------------------------------------------------------
# Helper: call /api/tts (non-streaming) and return parsed JSON
# ---------------------------------------------------------------------------

def call_tts_nonstream(
    api_key: str,
    text: str,
    voice: str,
    include_timestamps: bool = True,
    timeout: int = 30,
) -> dict:
    """
    Call the astica Voice /api/tts endpoint in non-streaming mode.

    Returns:
        Parsed JSON response as a Python dict.

    Raises:
        requests.RequestException on network/HTTP issues.
        ValueError on invalid/malformed JSON.
    """
    url = f"{ASTICA_API_ENDPOINT}/api/tts"

    payload = {
        "tkn": api_key,
        "text": text,
        "voice": voice,
        "stream": False,              # non-streaming
        "timestamps": include_timestamps,
    }

    resp = requests.post(url, json=payload, timeout=timeout)

    # If HTTP is not 2xx, raise with server-provided error body if present
    if not resp.ok:
        try:
            err_json = resp.json()
        except Exception:
            err_json = None

        msg = f"HTTP {resp.status_code}"
        if err_json and isinstance(err_json, dict):
            server_err = err_json.get("error") or err_json.get("status")
            if server_err:
                msg += f" – server error: {server_err}"
        raise requests.HTTPError(msg, response=resp)

    try:
        data = resp.json()
    except Exception as e:
        raise ValueError(f"Failed to parse JSON response: {e}") from e

    return data


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    print("Calling astica Voice TTS (non-streaming)...")
    print(f"  Endpoint: {ASTICA_API_ENDPOINT}/api/tts")
    print(f"  Voice:    {VOICE}")
    print(f"  Text:     {TEXT!r}")
    print()

    try:
        data = call_tts_nonstream(
            api_key=ASTICA_API_KEY,
            text=TEXT,
            voice=VOICE,
            include_timestamps=INCLUDE_TIMESTAMPS,
            timeout=HTTP_TIMEOUT_SECONDS,
        )
    except requests.RequestException as e:
        print(f"[Network/HTTP error] {e}")
        return 1
    except ValueError as e:
        print(f"[Parse error] {e}")
        return 1

    # Basic sanity check on API-level status
    status = data.get("status")
    if status != "success":
        print(f"API returned non-success status: {status!r}")
        print("Full response:")
        print(json.dumps(data, indent=2))
        return 1

    audio_b64 = data.get("audio_b64")
    audio_format = data.get("audio_format", "wav")
    cost_units = data.get("cost_units")
    used_voice = data.get("voice")

    if not audio_b64:
        print("Response did not contain audio_b64; full response:")
        print(json.dumps(data, indent=2))
        return 1

    # Decode audio and save WAV
    try:
        audio_bytes = base64.b64decode(audio_b64)
    except Exception as e:
        print(f"Failed to decode audio_b64: {e}")
        return 1

    OUTPUT_WAV_PATH.write_bytes(audio_bytes)
    OUTPUT_JSON_PATH.write_text(json.dumps(data, indent=2), encoding="utf-8")

    print("TTS request completed successfully.")
    print(f"  Resolved voice: {used_voice}")
    print(f"  Units billed:   {cost_units}")
    print(f"  Audio format:   {audio_format}")
    print()
    print(f"Saved audio to: {OUTPUT_WAV_PATH.resolve()}")
    print(f"Saved JSON  to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
