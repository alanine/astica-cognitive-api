var asticaAPI_endpoint = 'https://vision.astica.ai/describe';
var asticaAPI_payload = {
    tkn: 'YOUR API KEY',  //visit https://astica.ai
    modelVersion: '2.1_full', //1.0_full, 2.0_full, or 2.1_full 
    input: 'https://www.astica.org/inputs/analyze_3.jpg', //https url or base64 (data:image/png;base64,...)
    visionParams: '' //comma separated options; leave blank for all. See https://astica.ai/vision/documentation/#parameters
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
    console.log("astica Vision AI Results")
    console.log(data)
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});