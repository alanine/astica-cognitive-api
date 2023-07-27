const axios = require('axios'); //npm install axios
const fs = require('fs'); // Only needed for Input Method 2
//Input Method 1: https URL of a jpg/png image (faster)
var astica_input = 'https://astica.ai/example/asticaVision_sample.jpg';

/*
    //Input Method 2: base64 encoded string of a local image (slower)  
    var path_to_local_file = 'image.jpg';
    var image_data = fs.readFileSync(path_to_local_file);
    var image_extension = path_to_local_file.split('.').pop();
    //For now, let's make sure to prepend appropriately with: "data:image/extension_here;base64" 
    var astica_input = `data:image/${image_extension};base64,${image_data.toString('base64')}`;
*/


const requestData = {
  tkn: '26d7e90d0b2090b30b921ls1',  // //visit https://astica.ai
  modelVersion: '2.1_full',         ////1.0_full, 2.0_full, or 2.1_full
  input: astica_input,
  visionParams: 'description,tags', //comma separated, see below
};

/*    
    '1.0_full' supported options:
        description
        objects
        categories
        moderate
        tags
        brands
        color
        faces
        celebrities
        landmarks
        gpt new (Slow - be patient)
        gpt_detailed new (Much Slower)
        
    '2.0_full' supported options:
        description
        objects
        tags
        describe_all new
        text_read new
        gpt new (Slow - be patient)
        gpt_detailed new (Much Slower)
        
     '2.0_full' supported options:
        Supports all options 
        
*/


axios({
    method: 'post',
    url: 'https://vision.astica.ai/describe',
    data: requestData,
    headers: {
        'Content-Type': 'application/json',
    },
}).then((response) => {
    console.log(response.data);
}).catch((error) => {
    console.log(error);
});