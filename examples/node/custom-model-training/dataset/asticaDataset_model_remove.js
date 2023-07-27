const axios = require('axios');

const astica_config = {
  astica_endpoint: 'https://train.astica.ai/model/model_remove',
  astica_api_key: 'API KEY HERE', // visit https://astica.ai
  astica_model_id: 0, // model_id to remove
};
/* 
    You can retrieve your model_id:
      Using astica dashboard: https://astica.ai/account/models/ 
      Using API: https://train.astica.ai/dataset/list
*/


const astica_requestData = {
  tkn: astica_config.astica_api_key,
  model_id: astica_config.astica_model_id,
};

axios.post(astica_config.astica_endpoint, astica_requestData, { headers: { 'Content-Type': 'application/json' } })
  .then((res) => {
    const data = res.data;
    console.log(data);
    if (data.status !== 'OK') {
      console.log('API request failed');
      console.log(data.error);
    } else {
      console.log('Model has been removed');
    }
  })
  .catch((err) => {
    console.log('Unable to remove model');
    console.log('Error:', err.message);
  });