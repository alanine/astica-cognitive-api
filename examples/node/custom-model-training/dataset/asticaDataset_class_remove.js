const axios = require('axios');

const astica_endpoint = 'https://train.astica.ai/model/class_remove';
const astica_api_key = 'API KEY HERE'; //visit https://astica.ai
const astica_class_id = 0; //class_id to remove

const astica_requestData = {
    'tkn': astica_api_key,
    'class_id': astica_class_id,
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
        console.log('Class has been removed');
    }
})
.catch((error) => {
    console.error('Unable to remove class');
    console.error('Error:', error.toString());
});