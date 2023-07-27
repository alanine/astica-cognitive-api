import os
import requests
import json
import base64
import random

astica_api_key = 'API KEY HERE';    #visit https://astica.ai
astica_model_id = 10;               #model_id to update
astica_class_title = 'Dog'          #class will be created if it does not exist.
astica_upload_path = 'image.jpg'    # File path or Folder path (PNG or JPEG)


def asticaTrain_directory_upload(model_id, class_title, directory_path):
    for root, _, files in os.walk(directory_path):
        for file in files:
            file_path = os.path.join(root, file)
            asticaTrain_file_prepare(file_path)
            
def asticaTrain_file_upload(model_id, class_title,file_path):
    with open(file_path, 'rb') as file:
        encoded_file = base64.b64encode(file.read()).decode('utf-8')            
    #You can refer to uploaded samples this way.
    sample_uid = "{}_{}".format(os.path.basename(file_path), str(random.randint(1, 100000)))    
    #Send training data to api
    asticaTrain_upload(model_id, class_title,encoded_file,sample_uid)

def asticaTrain_upload(model_id, class_title, encoded_file, sample_uid):
    global astica_api_key
    astica_endpoint = 'https://train.astica.ai/model/upload'
    astica_requestData = {
        'tkn': astica_api_key,
        'model_id': model_id,
        'model_class': class_title,
        'input': encoded_file,
        'uid': sample_uid,
    }    
    try:
        response = requests.post(astica_endpoint, data=json.dumps(astica_requestData), headers={'Content-Type': 'application/json'})
        response.raise_for_status()  # Raise an exception if the request was unsuccessful

        data = json.loads(response.text)
        print(data)

        if data['status'] != 'OK':
            print("api request failed")
            print(data["error"])
        else:
            # Handle the success response
            print("Upload successful")

    except requests.exceptions.RequestException as e:
        print("Error:", str(e))

if os.path.isdir(astica_upload_path):
    asticaTrain_directory_upload(astica_model_id, astica_class_title, astica_upload_path)
else:
    asticaTrain_file_upload(astica_model_id, astica_class_title, astica_upload_path)
   
    
