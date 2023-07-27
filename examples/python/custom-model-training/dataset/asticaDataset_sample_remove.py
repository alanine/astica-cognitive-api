import requests
import json

astica_endpoint = 'https://train.astica.ai/model/sample_remove'
astica_api_key = 'API KEY HERE' #visit https://astica.ai
astica_sample_id = 0; #sample_id to remove
astica_sample_uid = ''; #(optional) Matches to user-provided string at time of sample upload




astica_requestData = {
    'tkn': astica_api_key,
    'sample_id': astica_sample_id,
    'sample_uid': astica_sample_uid 
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
        print("Sample has been removed")        
        
except requests.exceptions.RequestException as e:
    print("Unable to remove sample")
    print("Error:", str(e))