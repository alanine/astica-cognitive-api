let axios = require('axios'); //npm install axios


//Prepare the request
let asticaAPI_key = 'YOUR API KEY';
let asticaAPI_endpoint = 'https://nlp.astica.ai/generate';
let asticaAPI_payload = {
    tkn: asticaAPI_key,
    modelVersion: 'GPT-S2',
    instruction: '',
    input: 'Write a sentence describing the iPhone 12:',
    think_pass: 1,
    temperature: 0.7,
    top_p: 0.35,
    token_limit: 55,
    stop_sequence: '',
    stream_output: 0,
    low_priority: 0,
};



//Submit the request
(async () => {
    console.log('Processing request..');
    const result = await handleAPI(asticaAPI_endpoint, asticaAPI_payload);
    console.log(result.error ? `Error: ${result.error}` : `Output: ${result.output || result.resultURI}`);
})();



//Required function
async function handleAPI(endpoint, payload) {
    try {
        const response = await axios.post(endpoint, payload, {
            headers: { 'Content-Type': 'application/json' },
            timeout: 25000,
        });

        if ('status' in response.data) {
            if (response.data.status === 'error') {
                return { error: response.data.error };
            } else if (response.data.status === 'success') {
                if ('resultURI' in response.data) {
                    return { resultURI: response.data.resultURI };
                } else {
                    return { output: response.data.output };
                }
            }
        }
    } catch (error) {
        return { error: 'Failed to connect to the API.' };
    }
    return { error: 'Invalid response.' };
}    