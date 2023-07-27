import requests
import json

astica_endpoint = 'https://train.astica.ai/model/model_remove'
astica_api_key = 'API KEY HERE' #visit https://astica.ai
astica_model_id = 0; #model_id to remove
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
        print("Model has been removed")        
        
except requests.exceptions.RequestException as e:
    print("Unable to remove model")
    print("Error:", str(e))