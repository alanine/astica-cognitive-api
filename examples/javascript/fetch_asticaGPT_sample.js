var asticaAPI_endpoint = 'https://nlp.astica.ai/generate';
var asticaAPI_payload = {
    tkn: 'YOUR API KEY',  //visit https://astica.ai
    modelVersion: 'GPT-S2', //GPT-S model version
    input: 'Write a sentence about the iphone 12:', // GPT-S prompt
    instruction: '', //optional; additional prompt and context priming.
    think_pass: 1, //INT; number of passes
    temperature: 0.7, //creativity of response
    top_p: 0.35, //diversity and predictability of response
    token_limit: 55, //Length of response
    stop_sequence: '', //Comma separated. 'AI:,Human:'
    stream_output: 0, //See javascript SDK for real-time output
    low_priority: 0
};

fetch(asticaAPI_endpoint, {
    method: 'post',
    body: JSON.stringify(asticaAPI_payload),
    headers: {
        'Content-Type': 'application/json'
    },
    mode: 'cors'
})
.then(response => response.json()) // convert to json
.then(data => { //print data to console
    console.log("astica GPT-S Output")
    console.log(data)
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});