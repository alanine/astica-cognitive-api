import requests
import json

astica_endpoint = 'https://train.astica.ai/model/view'
astica_api_key = 'API KEY HERE' #visit https://astica.ai
astica_model_id = 10; #model_id to view
'''
    You can retrieve your model_id:
        Using astica dashboard: https://astica.ai/account/models/ 
        Using API: https://train.astica.ai/dataset/list
'''

astica_requestData = {
    'tkn': astica_api_key,
    'model_id': astica_model_id
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
        print("==") 
        print("title: "+ data['dataset']['title']) 
        print("total_size: "+str(data['dataset']['total_size'])+" MB") 
        print("numClass: "+str(data['dataset']['numClass']))
        print("numSample: "+str(data['dataset']['numSample']))
        if data['dataset']['trained'] == 1:             
            print("trained: trained") 
            print("model_version: "+data['dataset']['model_version']) 
            print("date_trained: "+str(data['dataset']['date_trained'])) 
        else:
            print("trained: untrained") 
        print("date_created: "+str(data['dataset']['date_created'])) 
        print("type: "+str(data['dataset']['type'])) 
        print("purpose: "+data['dataset']['purpose']) 
        print("tags: "+data['dataset']['tags'])         
        '''
        #traverse output
        for model_class in data['dataset']['class_list']:
                print(model_class);
            for model_sample in model_class['samples']:
                print(model_sample);
        '''
except requests.exceptions.RequestException as e:
    print("Unable to view model data")
    print("Error:", str(e))
