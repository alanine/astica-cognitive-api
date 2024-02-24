const axios = require('axios');

var asticaAPI_timeout = 120;
var asticaAPI_key = 'YOUR API KEY'; // Put your API key here

// See Input Documentation:  https://astica.ai/design/documentation/#inputs

var asticaAPI_endpoint = 'https://design.astica.ai/generate_image';
var asticaAPI_modelVersion = '2.0_full';
var asticaAPI_prompt = 'close-up photography of older gentleman standing in the rain at night, in a street lit by lamps'; 
var asticaAPI_prompt_negative = '';
var asticaAPI_moderate = 1; //1 = Moderate Generation; 0 = Allow NSFW
var asticaAPI_generate_quality = 'faster';//high, standard, fast, faster
var asticaAPI_generate_lossless = 0; //0 = Default JPG, 1 = lossless uncompressed PNG
var asticaAPI_seed = 0; //0 will randomize the seed for every generation
var asticaAPI_low_priority = 0;


//Prepare payload
var asticaAPI_payload = {
    tkn: asticaAPI_key,
    modelVersion: asticaAPI_modelVersion,
    prompt: asticaAPI_prompt,
    prompt_negative: asticaAPI_prompt_negative,
    moderate: asticaAPI_moderate,
    generate_quality: asticaAPI_generate_quality,
    generate_lossless: asticaAPI_generate_lossless,
    seed: asticaAPI_seed,
    low_priority: asticaAPI_low_priority,
};

//Demo function
(async () => {    
    // Submit request    
    console.log("Generating Image..")
    const result = await asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout);    
    console.log('astica API Output:\n', JSON.stringify(result, null, 4));
    // Handle response
    if (result.status === 'error') {
        console.log('Error:', result.error);
    } else if (result.status === 'success') {
        if (result.resultURI) {
            console.log('Output URI:', result.resultURI, '\nQuery this URL to obtain the output of your results');
        } else {
            console.log('===============');
            console.log('Generated Image:', result.output);
            console.log('===============');
        }
    } else { console.log('Invalid response'); }
    
    
})();


// Required astica api function
async function asticaAPI(endpoint, payload, timeout) {
    try {
        const response = await axios.post(endpoint, payload, {
            headers: { 'Content-Type': 'application/json' },
            timeout: timeout * 1000, // timeout in ms
        });

        if (response.status === 200) {
            return response.data;
        }
    } catch (error) {
        return { status: 'error', error: 'Failed to connect to the API.' };
    }
}