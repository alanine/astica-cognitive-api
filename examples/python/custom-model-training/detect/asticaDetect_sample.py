import requests
import json
import base64
import os

astica_api_endpoint = 'https://detect.astica.ai/detect'
astica_api_key = '' #visit https://astica.ai

astica_model_id = '1433' #unique id of your model dataset
astica_model_version = '20230710_212503' #required specify model revision
'''
    You can retrieve your model_id:
        Using astica dashboard: https://astica.ai/account/models/ 
        Using API: https://train.astica.ai/dataset/list
'''

#Input Method 1: https URL of a jpg/png image (faster)
astica_input = 'https://astica.ai/example/asticaVision_sample.jpg' 

'''
#Input Method 2: base64 encoded string of a local image (slower)
path_to_local_file = 'image.jpg';
with open(path_to_local_file, 'rb') as file:
    image_data = file.read()
image_extension = os.path.splitext(path_to_local_file)[1]
#For now, let's make sure to prepend appropriately with: "data:image/extension_here;base64" 
astica_input = f"data:image/{image_extension[1:]};base64,{base64.b64encode(image_data).decode('utf-8')}"
'''

# Define payload dictionary
astica_requestData = {
    'tkn': astica_api_key,
    'model_id': astica_model_id,
    'model_version': astica_model_version,
    'input': astica_input,
}


try:
    response = requests.post(astica_api_endpoint, data=json.dumps(astica_requestData), timeout=25, headers={'Content-Type': 'application/json'})
    response.raise_for_status()  # Raise an exception if the request was unsuccessful
    data = json.loads(response.text)
    
    #raw output
    print(data)

    #handle response
    if data['status'] != 'OK':
        print("api request failed")
        print(data["error"])
    else:
        print("=======")
        print("Status: "+data["status"])
        print("output: "+data["output"])
        print("confidence: "+str(data["confidence"]))

except requests.exceptions.RequestException as e:
    print("Unable to view model data")
    print("Error:", str(e))

