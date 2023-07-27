const axios = require('axios');

const astica_endpoint = 'https://train.astica.ai/model/sample_remove';
const astica_api_key = 'API KEY HERE'; // visit https://astica.ai
const astica_sample_id = 0; // sample_id to remove
const astica_sample_uid = ''; // (optional) Matches to user-provided string at time of sample upload

let astica_requestData = {
    'tkn': astica_api_key,
    'sample_id': astica_sample_id,
    'sample_uid': astica_sample_uid 
};

const options = {
  headers: { 'Content-Type': 'application/json' }
};

axios.post(astica_endpoint, astica_requestData, options)
  .then((response) => {
    console.log(response.data);
    // handle response
    if(response.data['status'] !== 'OK'){
      console.log("api request failed");
      console.log(response.data["error"]);
    } else {
      console.log("Sample has been removed");     
    }
  }).catch((error) => {
    console.log("Unable to remove sample");
    console.log("Error:", error.toString());
});