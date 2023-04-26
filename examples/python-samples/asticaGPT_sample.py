import requests
import json

def asticaAPI(endpoint, payload, timeout):
    response = requests.post(endpoint, data=payload, timeout=timeout, verify = False)
    if response.status_code == 200:
        return response.json()
    else:
        return {'status': 'error', 'error': 'Failed to connect to the API.'}

asticaAPI_key = 'YOUR API KEY' # visit https://astica.org
asticaAPI_key = '8FFDF928-3CCB-4964-A3FF-130CAD42344D' # visit https://astica.org
asticaAPI_timeout = 25 # seconds
asticaAPI_endpoint = 'https://www.astica.org:9161/gpt/generate'
asticaAPI_modelVersion = 'GPT-S2' # engine to use
asticaAPI_think_pass = 1 # INT; number of passes
asticaAPI_temperature = 0.7 # creativity of response
asticaAPI_top_p = 0.35 # diversity and predictability of response
asticaAPI_token_limit = 55 # length of response
asticaAPI_stop_sequence = '' # comma-separated; 'AI:,Human:'
asticaAPI_stream_output = 0 # (0 or 1); not available yet; determines whether to display responses in real-time
asticaAPI_low_priority = 0 # (0 or 1); lower costs by receiving a URL to query for results
asticaAPI_instruction = '' # optional; additional context priming
asticaAPI_input = 'Write a sentence describing the iPhone 12:'

print('Using POST')
# Define payload dictionary
asticaAPI_payload = {
    'tkn': asticaAPI_key,
    'modelVersion': asticaAPI_modelVersion,
    'instruction': asticaAPI_instruction,
    'input': asticaAPI_input,
    'think_pass': asticaAPI_think_pass,
    'temperature': asticaAPI_temperature,
    'top_p': asticaAPI_top_p,
    'token_limit': asticaAPI_token_limit,
    'stop_sequence': asticaAPI_stop_sequence,
    'stream_output': asticaAPI_stream_output,
    'low_priority': asticaAPI_low_priority
}
# Call API function and store result
asticaAPI_result = asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout)

print('\nastica API Output:')
print(json.dumps(asticaAPI_result, indent=4))
print('=================')
print('=================')

# Handle asticaAPI response
if 'status' in asticaAPI_result:
    # Output Error if exists    
    if asticaAPI_result['status'] == 'error':        
        print('=================')
        print('Output:\n', asticaAPI_result['error'])
    # Output Success if exists
    if asticaAPI_result['status'] == 'success':
        if 'resultURI' in asticaAPI_result:
            print('Output URI:\n', asticaAPI_result['resultURI'])
            print('\nNote: Query this URL to obtain the output of your results:')
            print(asticaAPI_result['resultURI'])
        else:
            print('Output:\n', asticaAPI_result['output'])
else:
    print('Invalid response')