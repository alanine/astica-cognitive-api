const axios = require('axios');


const asticaAPI_key = 'YOUR API KEY';
const asticaAPI_timeout = 25;

const asticaAPI_endpoint = 'https://listen.astica.ai/transcribe';
const asticaAPI_modelVersion = '1.0_full';
const asticaAPI_doStream = 0;
const asticaAPI_low_priority = 0;
const asticaAPI_input = 'https://astica.ai/example/asticaListen_sample.wav';

const asticaAPI_payload = {
    tkn: asticaAPI_key,
    modelVersion: asticaAPI_modelVersion,
    input: asticaAPI_input,
    doStream: asticaAPI_doStream,
    low_priority: asticaAPI_low_priority
};

(async () => {
    
    // Submit request
    const result = await asticaAPI(asticaAPI_endpoint, asticaAPI_payload, asticaAPI_timeout);    
    console.log('astica API Output:\n', JSON.stringify(result, null, 4));
    
    // Handle response
    if (result.status === 'error') {
        console.log('Error:', result.error);
    } else if (result.status === 'success') {
        if (result.resultURI) {
            console.log('Output URI:', result.resultURI, '\nQuery this URL to obtain the output of your results');
        } else {
            console.log('Output:', result.text);
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