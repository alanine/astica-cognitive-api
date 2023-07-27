const axios = require('axios')
const fs = require('fs'); // Only needed for Input Method 2

const astica_api_endpoint = 'https://detect.astica.ai/detect'
const astica_api_key = 'API KEY HERE' //visit https://astica.ai
const astica_model_id = '1433' //unique id of your model dataset
const astica_model_version = '20230710_212503' //required specify model revision


//Input Method 1: https URL of a jpg/png image (faster)
var astica_input = 'https://astica.ai/example/asticaVision_sample.jpg';

//Input Method 2: base64 encoded string of a local image (slower)  
/*
    var path_to_local_file = 'image.jpg';
    var image_data = fs.readFileSync(path_to_local_file);
    var image_extension = path_to_local_file.split('.').pop();
    //For now, let's make sure to prepend appropriately with: "data:image/extension_here;base64" 
    var astica_input = `data:image/${image_extension};base64,${image_data.toString('base64')}`;
*/

const astica_requestData = {
    tkn: astica_api_key,
    model_id: astica_model_id,
    model_version: astica_model_version,
    input: astica_input,
}

axios.post(astica_api_endpoint, astica_requestData, {
  headers: {'Content-Type': 'application/json'}
})
.then((response) => {
  const data = response.data;
  console.log(data)

  if (data.status !== 'OK') {
    console.log("api request failed")
    console.log(data.error)
  } else {
    console.log("=======")
    console.log("Status: "+data.status)
    console.log("output: "+data.output)
    console.log("confidence: "+data.confidence)
  }
})
.catch((error) => {
  console.error("Unable to view model data")
  console.error("Error:", error)
})