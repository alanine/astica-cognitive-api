const axios = require('axios');

const astica_endpoint = 'https://train.astica.ai/model/view';
const astica_api_key = 'API KEY HERE'; //visit https://astica.ai
const astica_model_id = 10; //model_id to view

const astica_requestData = {
    tkn: astica_api_key,
    model_id: astica_model_id
};
/* 
    You can retrieve your model_id:
      Using astica dashboard: https://astica.ai/account/models/ 
      Using API: https://train.astica.ai/dataset/list
*/
axios.post(astica_endpoint, astica_requestData, {headers: {'Content-Type': 'application/json'}})
    .then(response => {
        const data = response.data;
        console.log(data);
        if(data.status !== 'OK'){
            console.log("api request failed");
            console.log(data.error);
        } else {
            console.log("==");
            console.log("title: " + data.dataset.title);
            console.log("total_size: " + data.dataset.total_size + " MB");
            console.log("numClass: " + data.dataset.numClass);
            console.log("numSample: " + data.dataset.numSample);
            console.log("trained: " + (data.dataset.trained === 1 ? 'trained' : 'untrained'));
            if (data.dataset.trained === 1) {
                console.log("model_version: " + data.dataset.model_version);
                console.log("date_trained: " + data.dataset.date_trained);
            }
            console.log("date_created: " + data.dataset.date_created);
            console.log("type: " + data.dataset.type);
            console.log("purpose: " + data.dataset.purpose);
            console.log("tags: " + data.dataset.tags);
        }
    })
    .catch(error => {
        console.log("Unable to view model data");
        console.log("Error:", error.message);
    });