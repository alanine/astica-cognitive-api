var asticaAPI_endpoint = 'https://listen.astica.ai/transcribe';
var asticaAPI_payload = {
    tkn: 'YOUR API KEY',  //visit https://astica.ai
    modelVersion: '2.0_full', //1.0_full, or 2.0_full
    input: 'https://astica.ai/example/asticaListen_sample.wav', //https url or base64 (data:audio/mp3;base64,...)
    transcribe_mode: 0, //0 = basic, 1 = include speech transcript, 2 = include speaker identification
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
    console.log("astica Hearing AI Results")
    console.log(data)
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});