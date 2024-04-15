const axios = require('axios'); //npm install axios
const fs = require('fs'); // Only needed for Input Method 2

if (1 == 1) {    
    //Input Method 1: https URL of a jpg/png image (faster)
    var astica_input = 'https://astica.ai/example/asticaVision_sample.jpg';
} else {    
    //Input Method 2: base64 encoded string of a local image 
    //Note:  typically slower than method 1 due to client connection speed
    var path_to_local_file = 'image.jpg';
    var astica_input = fs.readFileSync(path_to_local_file).toString('base64');
}

const requestData = {
  tkn: 'API KEY HERE', // visit https://astica.ai
  modelVersion: '2.5_full', //1.0_full, 2.0_full, 2.1_full or 2.5_full
  input: astica_input, //base64 string or https url
  visionParams: 'gpt, describe, describe_all, tags, faces', //comma separated, leave blank for all. See below for more
  gpt_prompt: '', // only used if visionParams includes "gpt" or "gpt_detailed"
  prompt_length: 95, // number of words in GPT response
  objects_custom_kw: '' // only used if visionParams includes "objects_custom" (v2.5_full or higher)
};

/*        
    '2.5_full' supported visionParams: https://astica.ai/vision/documentation/#parameters
        describe
        describe_all
        gpt (or) gpt_detailed 
        text_read
        objects
        objects_custom
        objects_color
        categories
        moderate
        tags
        color
        faces
        celebrities
        landmarks
        brands        
*/

axios({
    method: 'post',
    url: 'https://vision.astica.ai/describe',
    data: requestData,
    headers: {
        'Content-Type': 'application/json',
    },
}).then((response) => {
    console.log(JSON.stringify(response.data));
}).catch((error) => {
    console.log(error);
});
