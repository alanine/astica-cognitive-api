import requests
import json
import base64
import os


# API configurations
asticaAPI_key = 'YOUR API KEY'  # visit https://astica.ai
asticaAPI_timeout = 10 # in seconds.
asticaAPI_endpoint = 'https://voice.astica.ai/speak'
asticaAPI_modelVersion = '1.0_full'

asticaAPI_voiceid = 0; # see list of voice id: https://astica.ai/voice/documentation/
asticaAPI_input = 'hello, how are you doing today?' # text to be spoken
asticaAPI_lang = 'en-US' # language code

asticaAPI_outputFile = 'output.wav' #save audio file of speech
asticaAPI_outputPlayback = False #pip install sounddevice numpy
# Define payload dictionary
asticaAPI_payload = {
    'tkn': asticaAPI_key,
    'modelVersion': asticaAPI_modelVersion,
    'input': asticaAPI_input,
    'voice': asticaAPI_voiceid,
    'lang': asticaAPI_lang,
}



def asticaAPI(endpoint, payload, timeout):
    response = requests.post(endpoint, data=json.dumps(payload), timeout=timeout, headers={ 'Content-Type': 'application/json', })
    if response.status_code == 200:
        return response.json()
    else:
        return {'status': 'error', 'error': 'Failed to connect to the API.'}



# call API function and store result
asticaAPI_result = asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout)

# print API output
print('\nastica API Output:')
print(json.dumps(asticaAPI_result, indent=4))
print('=================')
# Handle asticaAPI response
if 'status' in asticaAPI_result:
    # Output Error if exists
    if asticaAPI_result['status'] == 'error':
        print('Output:\n', asticaAPI_result['error'])
    # Output Success if exists
    if asticaAPI_result['status'] == 'success':
        print("Success")
        
        #handle wav buffer
        wavData = bytes(asticaAPI_result['wavBuffer']['data'])
        
        #save wav file
        with open(asticaAPI_outputFile, 'wb') as f:
            f.write(wavData)
            
        if asticaAPI_outputPlayback: 
            #or play wav file
            import sounddevice as sd #pip install sounddevice
            import numpy as np
            wav_array = np.frombuffer(wavData, dtype=np.int16)
            # assuming audio data array contains 44100 samples per second
            fs = 16000  
            # play sound
            sd.play(wav_array, fs)
            # use this during sound playback, otherwise the sound will stop immediately
            sd.wait()    
        
else:
    print('Invalid response')
