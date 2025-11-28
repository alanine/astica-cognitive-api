#!/usr/bin/env python3
"""
Create a user-specific voice clone via astica Voice API.

- Calls multipart/form-data POST https://voice.astica.ai/api/voice_clone
- Sends:
    - tkn       : API key
    - nickname  : label for your clone
    - audio     : a small demo WAV file generated in-memory

This will queue a clone job. The clone may take some time to be fully trained.
Use voice_clones_list.py to inspect status.

Saves response JSON to voice-clone-create.json.

Setup:
    pip install -r requirements.txt

Usage:
    python voice_clone_create.py
"""

import io
import json
import math
import struct
import wave
from pathlib import Path

import requests


ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"

CLONE_NICKNAME = "Python Demo Clone"
OUTPUT_JSON_PATH = Path("voice-clone-create.json")



def generate_demo_wav(
    duration_sec: float = 2.0,
    sample_rate: int = 16000,
    freq_hz: float = 440.0,
) -> bytes:
    """
    Generate a simple sine wave WAV file in memory.

    This is only for demo purposes; in real usage you would upload
    real speech audio from a file or microphone.
    """
    num_samples = int(duration_sec * sample_rate)
    amplitude = 0.3  # relative amplitude (0..1)

    buffer = io.BytesIO()
    with wave.open(buffer, "wb") as wf:
        wf.setnchannels(1)
        wf.setsampwidth(2)  # 16-bit
        wf.setframerate(sample_rate)

        for i in range(num_samples):
            t = i / sample_rate
            sample_val = amplitude * math.sin(2.0 * math.pi * freq_hz * t)
            # Convert to 16-bit signed integer
            int_val = int(max(-1.0, min(1.0, sample_val)) * 32767)
            wf.writeframes(struct.pack("<h", int_val))

    return buffer.getvalue()



def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    url = f"{ASTICA_API_ENDPOINT}/api/voice_clone"

    print(f"Creating a voice clone via {url} ...")

    audio_bytes = generate_demo_wav(duration_sec=2.0, sample_rate=16000, freq_hz=220.0)

    files = {
        "audio": ("demo_clone.wav", audio_bytes, "audio/wav"),
    }
    data = {
        "tkn": ASTICA_API_KEY,
        "nickname": CLONE_NICKNAME,
    }

    try:
        resp = requests.post(url, data=data, files=files, timeout=60)
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

    print("Clone creation response:")
    print(json.dumps(body, indent=2))

    OUTPUT_JSON_PATH.write_text(json.dumps(body, indent=2), encoding="utf-8")
    print()
    print(f"Response saved to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
