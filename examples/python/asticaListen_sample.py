import requests
import json

def asticaAPI(endpoint, payload, timeout):
    response = requests.post(endpoint, data=json.dumps(payload), timeout=timeout, headers={ 'Content-Type': 'application/json', })
    if response.status_code == 200:
        return response.json()
    else:
        return {'status': 'error', 'error': 'Failed to connect to the API.'}

asticaAPI_key = 'YOUR API KEY' # visit https://astica.ai
asticaAPI_timeout = 25 # seconds

asticaAPI_endpoint = 'https://listen.astica.ai/transcribe'
asticaAPI_modelVersion = '1.0_full'

asticaAPI_doStream = 0 # Determines whether to display responses in real-time.
asticaAPI_low_priority = 0 # Lower costs by receiving a URL to query for results. 

asticaAPI_input = 'https://astica.ai/example/asticaListen_sample.wav'

# Define payload dictionary
asticaAPI_payload = {
    'tkn': asticaAPI_key,
    'modelVersion': asticaAPI_modelVersion,
    'input': asticaAPI_input,
    'doStream': asticaAPI_doStream,
    'low_priority': asticaAPI_low_priority
}


# Call API function and store result
asticaAPI_result = asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout)

print('<hr><b>astica API Output:</b><br>')
print(json.dumps(asticaAPI_result, indent=4))

#Handle asticaAPI response
if 'status' in asticaAPI_result:
    # Output Error if exists    
    if asticaAPI_result['status'] == 'error':
        print('Output:', asticaAPI_result['error'])
    # Output Success if exists
    if asticaAPI_result['status'] == 'success':
        if 'resultURI' in asticaAPI_result:
            print('Output URI:', asticaAPI_result['resultURI'])
            print('Query this URL to obtain the output of your results')
        else:
            print('Output:', asticaAPI_result['text'])
else:
    print('Invalid response')
