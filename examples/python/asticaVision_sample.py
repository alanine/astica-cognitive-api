import requests
import json
import base64
import os
def get_image_base64_encoding(image_path: str) -> str:
    with open(image_path, 'rb') as file:
        image_data = file.read()
    image_extension = os.path.splitext(image_path)[1]
    return base64.b64encode(image_data).decode('utf-8')
    
# API configurations
asticaAPI_key = 'YOUR API KEY'  # visit https://astica.ai
asticaAPI_timeout = 25 # in seconds. "gpt" or "gpt_detailed" require increased timeouts
asticaAPI_endpoint = 'https://vision.astica.ai/describe'
asticaAPI_modelVersion = '2.5_full' # 1.0_full, 2.0_full, 2.1_full or 2.5_full

if 1 == 1:
    asticaAPI_input = 'https://astica.ai/example/asticaVision_sample.jpg' # use https image input (faster)
else:
    asticaAPI_input = get_image_base64_encoding('image.jpg')  # use base64 image input (slower)

# vision parameters:  https://astica.ai/vision/documentation/#parameters
asticaAPI_visionParams = 'gpt,describe,objects,faces'  # comma separated, defaults to "all". 
'''          
    '2.5_full' supported visionParams: https://astica.ai/vision/documentation/#parameters
        describe
        describe_all
        gpt (or) gpt_detailed 
        text_read
        objects
        objects_custom
        objects_color
        categories
        moderate
        tags
        color
        faces
        celebrities
        landmarks
        brands        
'''

#optional inputs:
asticaAPI_gpt_prompt = '' # only used if visionParams includes "gpt" or "gpt_detailed"
asticaAPI_prompt_length = 90 # number of words in GPT response

asticaAPI_objects_custom_kw = '' # only used if visionParams includes "objects_custom" (v2.5_full or higher)

# Define payload dictionary
asticaAPI_payload = {
    'tkn': asticaAPI_key,
    'modelVersion': asticaAPI_modelVersion,
    'visionParams': asticaAPI_visionParams,
    'input': asticaAPI_input,
    'gpt_prompt': asticaAPI_gpt_prompt,
    'prompt_length': asticaAPI_prompt_length,
    'objects_custom_kw': asticaAPI_objects_custom_kw
}

def asticaAPI(endpoint, payload, timeout):
    response = requests.post(endpoint, data=json.dumps(payload), timeout=timeout, headers={ 'Content-Type': 'application/json', })
    if response.status_code == 200:
        return response.json()
    else:
        return {'status': 'error', 'error': 'Failed to connect to the API.'}


# call API function 
asticaAPI_result = asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout)

# print API output
print('\nastica API Output:')
print(json.dumps(asticaAPI_result, indent=4))
print('=================')
print('=================')
# Handle asticaAPI response
if 'status' in asticaAPI_result:
    # Output Error if exists
    if asticaAPI_result['status'] == 'error':
        print('Output:\n', asticaAPI_result['error'])
    # Output Success if exists
    if asticaAPI_result['status'] == 'success':
        if 'caption_GPTS' in asticaAPI_result and asticaAPI_result['caption_GPTS'] != '':
            print('=================')
            print('GPT Caption:', asticaAPI_result['caption_GPTS'])
        if 'caption' in asticaAPI_result and asticaAPI_result['caption']['text'] != '':
            print('=================')
            print('Caption:', asticaAPI_result['caption']['text'])
        if 'CaptionDetailed' in asticaAPI_result and asticaAPI_result['CaptionDetailed']['text'] != '':
            print('=================')
            print('CaptionDetailed:', asticaAPI_result['CaptionDetailed']['text'])
        if 'objects' in asticaAPI_result:
            print('=================')
            print('Objects:', asticaAPI_result['objects'])
else:
    print('Invalid response')
