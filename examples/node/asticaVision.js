const fetch = require('node-fetch'); //npm install node-fetch@2
const https = require('https');

const requestData = {
  tkn: 'YOUR API KEY', //visit https://astica.ai
  modelVersion: '2.1_full', //1.0_full, 2.0_full, or 2.1_full
  input: 'https://astica.ai/example/asticaVision_sample.jpg',
  visionParams: 'description,tags',
};

/*
    visionParams list:
    
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

const agent = new https.Agent({ rejectUnauthorized: false });
fetch('https://vision.astica.ai/describe', {
  method: 'POST',
  body: JSON.stringify(requestData),
  headers: {
    'Content-Type': 'application/json',
  },
  agent: agent,
})
.then((response) => response.json())
.then((data) => {
    console.log(data);
})
.catch((error) => {
    console.log(error);
});