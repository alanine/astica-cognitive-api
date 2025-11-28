#!/usr/bin/env python3
"""
WebSocket streaming TTS example for astica Voice API (Python)

- Connects to wss://voice.astica.ai/ws/api
- Sends a TTS request (type="tts") with stream=true
- Receives:
    - tts_ack
    - tts_audio (chunk_b64, format = "pcm_s16le" or "wav")
    - tts_audio_end
    - tts_complete

This script:
  - Streams audio over WebSocket as it arrives.
  - Plays it live via sounddevice (low-latency) if enabled.
  - Saves:
      - WAV audio to:  audio-stream-ws.wav
      - JSON (ack + complete) to: audio-stream-ws.json

Format mapping:
  - format="pcm_s16le": expressive (GPU) and neural (Azure) — raw 16‑bit mono PCM.
  - format="wav": programmable (OpenAI) — WAV file bytes.

Setup:
    pip install -r requirements.txt

Usage:
    python tts_stream_ws.py
"""

import base64
import json
import sys
import uuid
import wave
from pathlib import Path

import websocket

try:
    import sounddevice as sd
except ImportError:
    sd = None


# ---------------------------------------------------------------------------
# Configuration (edit these)
# ---------------------------------------------------------------------------

ASTICA_API_KEY = "YOUR_API_KEY_HERE"  # obtain free api key -> https://astica.ai/api-keys/
WS_ENDPOINT = "wss://voice.astica.ai/ws/api"

VOICE = "expressive_sarah"
# Other examples:
#   "prog_avery"      - OpenAI / programmable voice
#   "neural_jennifer" - Azure / neural voice
# -> view all voices: https://astica.ai/voice/text-to-speech/

TEXT = "Hello from astica! This is an example in Python."

PLAY_AUDIO_LIVE = True  # Play audio in real time as it streams

OUTPUT_WAV_PATH = Path("audio-stream-ws.wav")
OUTPUT_JSON_PATH = Path("audio-stream-ws.json")


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

    request_id = str(uuid.uuid4())

    # Shared state between callbacks and main
    state = {
        "request_id": request_id,
        "ack": None,
        "complete": None,
        "error": None,
        "audio_format": None,   # "pcm_s16le" or "wav"
        "sample_rate": None,
        "num_channels": 1,
        "pcm_bytes": bytearray(),  # used for pcm_s16le -> WAV
        "wav_bytes": bytearray(),  # used for format="wav"
        "wav_header_buf": bytearray(),
        "wav_header_parsed": False,
        "play_audio": play_audio,
        "stream": None,          # sounddevice stream
    }

    def cleanup_audio_stream():
        s = state.get("stream")
        if s is not None:
            try:
                s.stop()
                s.close()
            except Exception:
                pass
        state["stream"] = None

    def on_open(ws):
        print("WebSocket open. Sending TTS request...")
        payload = {
            "type": "tts",
            "tkn": ASTICA_API_KEY,
            "text": TEXT,
            "voice": VOICE,
            "stream": True,
            "timestamps": False,
            "request_id": request_id,
        }
        ws.send(json.dumps(payload))

    def on_message(ws, message):
        try:
            data = json.loads(message)
        except Exception as e:
            print(f"[WS] Failed to parse JSON: {e}")
            return

        msg_type = data.get("type")
        if msg_type == "tts_ack":
            state["ack"] = data
            print("Received tts_ack:")
            print(json.dumps(data, indent=2))
            return

        if msg_type == "tts_error":
            state["error"] = data
            print("Received tts_error:")
            print(json.dumps(data, indent=2))
            ws.close()
            return

        if msg_type == "tts_audio":
            handle_tts_audio(data)
            return

        if msg_type == "tts_audio_end":
            print("Received tts_audio_end.")
            return

        if msg_type == "tts_complete":
            state["complete"] = data
            print("Received tts_complete:")
            print(json.dumps(data, indent=2))
            cleanup_audio_stream()
            ws.close()
            return

    def handle_tts_audio(data: dict):
        chunk_b64 = data.get("chunk_b64")
        if not chunk_b64:
            return

        try:
            chunk = base64.b64decode(chunk_b64)
        except Exception as e:
            print(f"[WS] Failed to decode audio chunk: {e}")
            return

        fmt = data.get("format") or state["audio_format"]

        if state["audio_format"] is None:
            state["audio_format"] = fmt or "pcm_s16le"

        if state["audio_format"] == "pcm_s16le":
            handle_pcm_chunk(chunk, data)
        elif state["audio_format"] == "wav":
            handle_wav_chunk(chunk)
        else:
            print(f"[WS] Unknown audio format: {state['audio_format']}")

    def handle_pcm_chunk(chunk: bytes, data: dict):
        # Expressive (GPU) and neural (Azure) path: raw PCM s16le, 24 kHz mono (by default)
        state["pcm_bytes"].extend(chunk)

        if not state["play_audio"]:
            return

        if state["sample_rate"] is None:
            sr = data.get("sample_rate") or 24000
            state["sample_rate"] = int(sr)

        if state["stream"] is None:
            try:
                s = sd.RawOutputStream(
                    samplerate=state["sample_rate"],
                    channels=1,
                    dtype="int16",
                    blocksize=0,
                    latency="low",
                )
                s.start()
                state["stream"] = s
            except Exception as e:
                print(f"Could not open audio device for playback: {e}")
                print("Continuing without live playback.")
                state["play_audio"] = False
                return

        if state["stream"] is not None:
            state["stream"].write(chunk)

    def handle_wav_chunk(chunk: bytes):
        # Programmable (OpenAI) path: streaming WAV bytes
        state["wav_bytes"].extend(chunk)

        if not state["play_audio"]:
            return

        if not state["wav_header_parsed"]:
            state["wav_header_buf"].extend(chunk)
            if len(state["wav_header_buf"]) < 44:
                return

            header = state["wav_header_buf"][0:44]

            sample_rate = int.from_bytes(header[24:28], "little")
            num_channels = int.from_bytes(header[22:24], "little")
            bits_per_sample = int.from_bytes(header[34:36], "little")

            state["sample_rate"] = sample_rate
            state["num_channels"] = num_channels

            if bits_per_sample != 16:
                print(f"Unexpected bits_per_sample={bits_per_sample}, playback may fail.")
            if num_channels != 1:
                print(f"Unexpected channels={num_channels}, playback may fail.")

            first_pcm = state["wav_header_buf"][44:]

            try:
                s = sd.RawOutputStream(
                    samplerate=sample_rate,
                    channels=num_channels,
                    dtype="int16",
                    blocksize=0,
                    latency="low",
                )
                s.start()
                state["stream"] = s
                if first_pcm:
                    s.write(first_pcm)
            except Exception as e:
                print(f"Could not open audio device for playback: {e}")
                print("Continuing without live playback.")
                state["play_audio"] = False
                state["stream"] = None

            state["wav_header_parsed"] = True
        else:
            if state["stream"] is not None:
                state["stream"].write(chunk)

    def on_error(ws, error):
        print(f"[WS error] {error}")
        state["error"] = {"error": str(error)}
        cleanup_audio_stream()

    def on_close(ws, close_status_code, close_msg):
        print(f"WebSocket closed: code={close_status_code}, msg={close_msg}")
        cleanup_audio_stream()

    print("Connecting to WebSocket endpoint...")
    print(f"  {WS_ENDPOINT}")
    print(f"  Voice: {VOICE}")
    print(f"  Text:  {TEXT!r}")
    print()

    ws_app = websocket.WebSocketApp(
        WS_ENDPOINT,
        on_open=on_open,
        on_message=on_message,
        on_error=on_error,
        on_close=on_close,
    )

    ws_app.run_forever()

    # --------------------------------------------------------------
    # After WS finishes, save audio + JSON summary
    # --------------------------------------------------------------
    if state["audio_format"] == "pcm_s16le" and state["pcm_bytes"]:
        sr = state["sample_rate"] or 24000
        with wave.open(str(OUTPUT_WAV_PATH), "wb") as wf:
            wf.setnchannels(1)
            wf.setsampwidth(2)
            wf.setframerate(sr)
            wf.writeframes(state["pcm_bytes"])
        print(f"Saved PCM-based WAV to: {OUTPUT_WAV_PATH.resolve()}")

    elif state["audio_format"] == "wav" and state["wav_bytes"]:
        OUTPUT_WAV_PATH.write_bytes(state["wav_bytes"])
        print(f"Saved WAV to: {OUTPUT_WAV_PATH.resolve()}")

    else:
        print("No audio bytes collected; nothing to save.")

    summary = {
        "request_id": state["request_id"],
        "voice": VOICE,
        "text": TEXT,
        "audio_format": state["audio_format"],
        "sample_rate": state["sample_rate"],
        "ack": state["ack"],
        "complete": state["complete"],
        "error": state["error"],
    }

    OUTPUT_JSON_PATH.write_text(json.dumps(summary, indent=2), encoding="utf-8")
    print(f"Saved JSON summary to: {OUTPUT_JSON_PATH.resolve()}")

    if state["error"] and not state["complete"]:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())