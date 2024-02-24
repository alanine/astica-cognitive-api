import requests
import json

# API configurations
asticaAPI_key = 'YOUR API KEY'  # visit https://astica.ai
asticaAPI_timeout = 60 # in seconds.

#See Input Documentation:  https://astica.ai/design/documentation/#inputs

asticaAPI_endpoint = 'https://design.astica.ai/generate_image'
asticaAPI_modelVersion = '2.0_full'
asticaAPI_prompt = 'close-up photography of older gentleman standing in the rain at night, in a street lit by lamps'
asticaAPI_prompt_negative = ''
asticaAPI_generate_quality = 'faster' #high, standard, fast, faster
asticaAPI_generate_lossless = 0 #0 = Default JPG, 1 = lossless uncompressed PNG
asticaAPI_seed = 0 #0 will randomize the seed for every generation
asticaAPI_moderate = 1
asticaAPI_low_priority = 0 #0 = realtime, 1 = low_priority (lower cost)

# Prepare payload
asticaAPI_payload = {
    'tkn': asticaAPI_key,
    'modelVersion': asticaAPI_modelVersion,
    'prompt': asticaAPI_prompt,
    'prompt_negative': asticaAPI_prompt_negative,
    'generate_quality': asticaAPI_generate_quality,
    'generate_lossless': asticaAPI_generate_lossless,
    'seed': asticaAPI_seed,
    'moderate': asticaAPI_moderate,
    'low_priority': asticaAPI_low_priority,
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
# Handle asticaAPI response
if 'status' in asticaAPI_result:
    # Output Error if exists
    if asticaAPI_result['status'] == 'error':
        print('Error:', asticaAPI_result['error'])
    # Output Success if exists
    elif asticaAPI_result['status'] == 'success':
        if 'resultURI' in asticaAPI_result:
            print('===============')
            print('Low Priority URI: ', asticaAPI_result['resultURI'], '\nQuery this URL to obtain the output of your results')
            print('===============')
        else:
            print('===============')
            print('Generated Image:', asticaAPI_result['output'])
            print('===============')
    print('\nastica API Output:')
    print(json.dumps(asticaAPI_result, indent=4))
    print('=================')
           
else:
    print('Invalid response')