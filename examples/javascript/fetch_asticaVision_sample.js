var asticaAPI_endpoint = 'https://vision.astica.ai/describe';
var asticaAPI_payload = {
    tkn: 'API KEY HERE',  //visit https://astica.ai
    modelVersion: '2.5_full', //1.0_full, 2.0_full, 2.1_full or 2.5_full
    input: 'https://www.astica.org/inputs/analyze_3.jpg', //https url or base64 encoded string
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
    console.log(JSON.stringify(data))
    /*
        handle individual data points:
        console.log("Caption:", data.caption.text)
    */
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});
