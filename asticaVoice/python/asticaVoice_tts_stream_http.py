#!/usr/bin/env python3
"""
HTTP streaming TTS example for astica Voice API (Python)

- Calls HTTPS POST https://voice.astica.ai/api/tts with stream=true
- For expressive GPU voices (e.g. "expressive_sarah"), the server returns
  raw 16‑bit PCM audio (audio/pcm), ideal for low-latency playback.
- For neural/programmatic voices, the server returns WAV (audio/wav).

This script:
  - Streams audio over HTTP as it arrives.
  - Optionally plays it live via sounddevice (low-latency).
  - Saves:
      - WAV audio to:  audio-stream-http.wav
      - Summary JSON to: audio-stream-http.json

Setup:
    pip install -r requirements.txt

Usage:
    python tts_stream_http.py
"""

import json
import sys
import wave
from pathlib import Path

import requests

try:
    import sounddevice as sd
except ImportError:
    sd = None


# ---------------------------------------------------------------------------
# Configuration (edit these)
# ---------------------------------------------------------------------------

ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
ASTICA_API_ENDPOINT = "https://voice.astica.ai"

VOICE = "expressive_sarah"
# Other examples:
#   "prog_avery"      - OpenAI / programmable voice
#   "neural_jennifer" - Azure / neural voice
# -> view all voices: https://astica.ai/voice/text-to-speech/

TEXT = "Hello from astica! This is an example in Python."

# Stream options
PLAY_AUDIO_LIVE = True  # Attempts low-latency playback while streaming
HTTP_TIMEOUT_SECONDS = 60

# Output files
OUTPUT_WAV_PATH = Path("audio-stream-http.wav")
OUTPUT_JSON_PATH = Path("audio-stream-http.json")


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> int:
    if not ASTICA_API_KEY or ASTICA_API_KEY == "YOUR_API_KEY_HERE":
        print("Please set ASTICA_API_KEY at the top of this file.")
        return 1

    if PLAY_AUDIO_LIVE and sd is None:
        print("sounddevice is not installed; live playback disabled.")
        print("Install with: pip install sounddevice")
        play_audio = False
    else:
        play_audio = PLAY_AUDIO_LIVE

    url = f"{ASTICA_API_ENDPOINT}/api/tts"
    payload = {
        "tkn": ASTICA_API_KEY,
        "text": TEXT,
        "voice": VOICE,
        "stream": True,
        "timestamps": False,
    }

    print("Calling astica Voice TTS (HTTP streaming)...")
    print(f"  Endpoint: {url}")
    print(f"  Voice:    {VOICE}")
    print(f"  Text:     {TEXT!r}")
    print()

    try:
        resp = requests.post(url, json=payload, stream=True, timeout=HTTP_TIMEOUT_SECONDS)
    except requests.RequestException as e:
        print(f"[Network/HTTP error] {e}")
        return 1

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
        print(msg)
        return 1

    content_type = (resp.headers.get("Content-Type") or "").lower()
    print(f"Server Content-Type: {content_type}")

    audio_format = None
    sample_rate = None

    # -------------------- Expressive (audio/pcm) ------------------------
    if "audio/pcm" in content_type:
        audio_format = "pcm_s16le"
        sample_rate = 24000  # GPU expressive default; server uses 24 kHz mono

        pcm_data = bytearray()
        stream = None

        if play_audio:
            try:
                stream = sd.RawOutputStream(
                    samplerate=sample_rate,
                    channels=1,
                    dtype="int16",
                    blocksize=0,
                    latency="low",
                )
                stream.start()
            except Exception as e:
                print(f"Could not open audio device for playback: {e}")
                print("Continuing without live playback.")
                stream = None

        try:
            for chunk in resp.iter_content(chunk_size=4096):
                if not chunk:
                    continue
                pcm_data.extend(chunk)
                if stream is not None:
                    stream.write(chunk)
        finally:
            if stream is not None:
                stream.stop()
                stream.close()

        # Save as WAV (wrap raw PCM with header)
        with wave.open(str(OUTPUT_WAV_PATH), "wb") as wf:
            wf.setnchannels(1)
            wf.setsampwidth(2)  # 16-bit
            wf.setframerate(sample_rate)
            wf.writeframes(pcm_data)

    # -------------------- Neural / Programmable (audio/wav) -------------
    elif "audio/wav" in content_type:
        audio_format = "wav"
        header_buf = bytearray()
        header_parsed = False
        stream = None

        # Save raw bytes directly as WAV
        with open(OUTPUT_WAV_PATH, "wb") as f:
            try:
                for chunk in resp.iter_content(chunk_size=4096):
                    if not chunk:
                        continue
                    f.write(chunk)

                    if not play_audio:
                        continue

                    if not header_parsed:
                        header_buf.extend(chunk)
                        if len(header_buf) < 44:
                            continue

                        # Parse WAV header from first 44 bytes
                        header = header_buf[:44]
                        # Sample rate is at offset 24 (4 bytes, little-endian)
                        sample_rate = int.from_bytes(header[24:28], "little")
                        num_channels = int.from_bytes(header[22:24], "little")
                        bits_per_sample = int.from_bytes(header[34:36], "little")

                        if bits_per_sample != 16:
                            print(f"Unexpected bits_per_sample={bits_per_sample}, playback may fail.")
                        if num_channels != 1:
                            print(f"Unexpected channels={num_channels}, playback may fail.")

                        first_pcm = header_buf[44:]

                        try:
                            stream = sd.RawOutputStream(
                                samplerate=sample_rate,
                                channels=num_channels,
                                dtype="int16",
                                blocksize=0,
                                latency="low",
                            )
                            stream.start()
                            if first_pcm:
                                stream.write(first_pcm)
                        except Exception as e:
                            print(f"Could not open audio device for playback: {e}")
                            print("Continuing without live playback.")
                            stream = None

                        header_parsed = True
                    else:
                        if stream is not None:
                            stream.write(chunk)
            finally:
                if stream is not None:
                    stream.stop()
                    stream.close()
    else:
        print("Unexpected Content-Type; not audio/pcm or audio/wav.")
        # Still drain the response to avoid connection issues
        _ = resp.content
        return 1

    # ------------------------------------------------------------------
    # Save simple summary JSON
    # ------------------------------------------------------------------
    summary = {
        "endpoint": url,
        "voice": VOICE,
        "text": TEXT,
        "stream": True,
        "content_type": content_type,
        "audio_format": audio_format,
        "sample_rate": sample_rate,
        "note": (
            "Streaming HTTP does not return billing metadata (cost_units). "
            "Use non-streaming (/api/tts with stream=false) or WebSockets (/ws/api) "
            "if you need per-request billing info."
        ),
    }

    OUTPUT_JSON_PATH.write_text(json.dumps(summary, indent=2), encoding="utf-8")

    print()
    print("HTTP streaming TTS completed.")
    print(f"  Audio format: {audio_format}")
    print(f"  Sample rate:  {sample_rate}")
    print()
    print(f"Saved audio to: {OUTPUT_WAV_PATH.resolve()}")
    print(f"Saved JSON  to: {OUTPUT_JSON_PATH.resolve()}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
