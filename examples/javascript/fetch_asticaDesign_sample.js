var asticaAPI_endpoint = 'https://design.astica.ai/generate_image';
var asticaAPI_payload = {
    tkn: 'YOUR API KEY',  //visit https://astica.ai
    modelVersion: '2.0_full',
    prompt: 'close-up photography of older gentleman standing in the rain at night, in a street lit by lamps',
    prompt_negative: '',
    generate_quality: 'faster', //high, standard, fast, faster
    generate_lossless: 0, //0 = Default JPG, 1 = lossless uncompressed PNG
    seed: 0, //0 will randomize the seed for every generation
    moderate: 1,  
    low_priority: 0 //0 = realtime, 1 = low_priority (lower cost)
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
    console.log("asticaDesign Image Generation Results")
    console.log(data)
    if (data.resultURI) {
        console.log('===============');
        console.log('Low Priority URI:', data.resultURI, '\nQuery this URL to obtain the output of your results');
        console.log('===============');
    } else {
        console.log('===============');
        console.log('Generated Image:', data.output);
        console.log('===============');
    }
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});