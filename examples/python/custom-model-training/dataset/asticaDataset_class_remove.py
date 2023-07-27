import requests
import json

astica_endpoint = 'https://train.astica.ai/model/class_remove'
astica_api_key = 'API KEY HERE' #visit https://astica.ai
astica_class_id = 0; #class_id to remove

astica_requestData = {
    'tkn': astica_api_key,
    'class_id': astica_class_id,
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
        print("Class has been removed")        
        
except requests.exceptions.RequestException as e:
    print("Unable to remove class")
    print("Error:", str(e))