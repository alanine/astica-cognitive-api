const axios = require('axios');

const astica_endpoint = 'https://train.astica.ai/model/model_train';
const astica_api_key = 'API KEY HERE'; // visit https://astica.ai
const astica_model_id = 0; //model_id to be trained

let astica_requestData = {
  'tkn': astica_api_key,
  'model_id': astica_model_id,
};

axios({
  method: 'post',
  url: astica_endpoint,
  headers: {
    'Content-Type': 'application/json'
  },
  data: JSON.stringify(astica_requestData)
})
.then(function (response) {
  // handle success
  const data = response.data;
  
  // raw output
  console.log(data);

  // handle response
  if(data.status != 'OK'){
    console.log("api request failed");
    console.log(data.error);
  } else {
    console.log("model training started: allow up to 5 minutes.");
  }
})
.catch(function (error) {
  // handle error
  console.log("Unable to train model");
  console.log("Error:", error.message);
});