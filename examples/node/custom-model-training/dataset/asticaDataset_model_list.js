const axios = require('axios');

const astica_endpoint = 'https://train.astica.ai/model/list';
const astica_api_key = '26d7e90d0b2090b30b921ls1'; //visit https://astica.ai

const astica_requestData = {
    'tkn': astica_api_key
 };

axios.post(astica_endpoint, astica_requestData, { headers: { 'Content-Type': 'application/json' } })
.then((response) => {
    const data = response.data;

    // raw output
    console.log(data);

    // handle response
    if (data.status !== 'OK') {
        console.error('API request failed');
        console.error(data.error);
    } else {
        for (let model of data['dataset_list']) {
            console.log("======================================Model:");
            console.log("title: " + model['title']);
            console.log("total_size: " + model['total_size'] + " MB");
            console.log("numClass: " + model['numClass']);
            console.log("numSample: " + model['numSample']);

            if (model['trained'] == 1) {
                console.log("trained: Yes");
                console.log("model_version: " + model['model_version']);
                console.log("date_trained: " + model['date_trained']);
            } else {
                console.log("trained: No");
            }
            console.log("date_created: " + model['date_created']);
            console.log("type: " + model['type']);
            console.log("purpose: " + model['purpose']);
            console.log("tags: " + model['tags']);
        }
    }
})
.catch((error) => {
    console.error('Unable to fetch models');
    console.error('Error:', error.toString());
});
