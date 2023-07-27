import requests
import json

astica_endpoint = 'https://train.astica.ai/model/list'
astica_api_key = 'API KEY HERE' #visit https://astica.ai

astica_requestData = {
    'tkn': astica_api_key
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
        for model in data['dataset_list']:
            print("======================================Model:") 
            print("title: "+ model['title']) 
            print("total_size: "+str(model['total_size'])+" MB") 
            print("numClass: "+str(model['numClass']))
            print("numSample: "+str(model['numSample']))
            if model['trained'] == 1:             
                print("trained: trained") 
                print("model_version: "+model['model_version']) 
                print("date_trained: "+str(model['date_trained'])) 
            else:
                print("trained: untrained") 
            print("date_created: "+str(model['date_created'])) 
            print("type: "+str(model['type'])) 
            print("purpose: "+model['purpose']) 
            print("tags: "+model['tags'])      
        
except requests.exceptions.RequestException as e:
    print("Unable to remove model")
    print("Error:", str(e))