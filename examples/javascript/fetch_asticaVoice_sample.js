var asticaAPI_endpoint = 'https://voice.astica.ai/speak';
var asticaAPI_payload = {
    tkn: 'YOUR API KEY',  //visit https://astica.ai
    modelVersion: '1.0_full',
    input: 'This is my default voice. You can select from countless others.', //provide the text to be spoken
    voice: '', //voice_id; see: https://astica.ai/voice/documentation/
    lang: 'en-US' // language code
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
    console.log("astica Voice AI Results")
    console.log(data)
    if(typeof data.wavBuffer != 'undefined') {        
        asticaVoice_speakPlayback(data.wavBuffer.data);
    }
}) 
.catch(error => {  // catch any errors
    console.log('Error:', error) 
});



//Sample code for playback of .wav audio buffer
function asticaVoice_speakPlayback(arr) {
    function init() {
        if (!window.AudioContext) {
            if (!window.webkitAudioContext) {
                alert("Your browser does not support any AudioContext and cannot play back this audio.");
                return;
            }
                window.AudioContext = window.webkitAudioContext;
            }
        context = new AudioContext();
    }
    function playByteArray(byteArray) {
        var arrayBuffer = new ArrayBuffer(byteArray.length);
        var bufferView = new Uint8Array(arrayBuffer);
        for (i = 0; i < byteArray.length; i++) {
          bufferView[i] = byteArray[i];
        }
        context.decodeAudioData(arrayBuffer, function(buffer) {
            buf = buffer;
            play();
        });
    }
    function play() {
        var source = context.createBufferSource();
        source.buffer = buf;
        source.connect(context.destination);
        source.start(0);
    }
    init();
    playByteArray(arr);
};