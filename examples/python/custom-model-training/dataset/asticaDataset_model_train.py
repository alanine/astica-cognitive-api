import requests
import json

astica_endpoint = 'https://train.astica.ai/model/model_train'
astica_api_key = 'API KEY HERE' #visit https://astica.ai
astica_model_id = 10; #model_id to be trained
'''
    You can retrieve your model_id:
        Using astica dashboard: https://astica.ai/account/models/ 
        Using API: https://train.astica.ai/dataset/list
'''

astica_requestData = {
    'tkn': astica_api_key,
    'model_id': astica_model_id,
}
try:
    response = requests.post(astica_endpoint, data=json.dumps(astica_requestData), headers={'Content-Type': 'application/json'})
    response.raise_for_status()  # Raise an exception if the request was unsuccessful
    data = json.loads(response.text)    
    #raw output
    print(data)
    #handle response
    if data['status'] != 'OK':
        print("api request failed")
        print(data["error"])
    else:
        print("model training started: allow up to 5 minutes.")
        
        
except requests.exceptions.RequestException as e:
    print("Unable to train model")
    print("Error:", str(e))