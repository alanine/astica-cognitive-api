var axios = require('axios');
var fs = require('fs');

var asticaAPI_key = 'YOUR API KEY'; // visit https://astica.ai
var asticaAPI_timeout = 10; // in seconds.

var asticaAPI_endpoint = 'https://voice.astica.ai/speak';
var asticaAPI_modelVersion = '1.0_full';

var asticaAPI_voiceid = 0; // see list of voice id: https://astica.ai/voice/documentation/
var asticaAPI_input = 'hello, how are you doing today?'; // text to be spoken
var asticaAPI_lang = 'en-US'; // language code

var asticaAPI_outputFile = 'output.wav' //save audio file of speech

// Define payload dictionary
var asticaAPI_payload = {
  tkn: asticaAPI_key,
  modelVersion: asticaAPI_modelVersion,
  input: asticaAPI_input,
  voice: asticaAPI_voiceid,
  lang: asticaAPI_lang,
};

axios.post(asticaAPI_endpoint, asticaAPI_payload , {
  headers: {
    'Content-Type': 'application/json'
  },
  timeout: asticaAPI_timeout * 1000,
}).then(function (response) {
  if (response.status == 200) {
    
    console.log('astica API Output:');
    console.log(JSON.stringify(response.data, null, 2));
    console.log('=================');
    
    //Handle asticaAPI response
    if (response.data.status == "error") {
      console.log('Output:\n',response.data.error);
    } else if (response.data.status == "success") {
      console.log("Success");
      
      var wavBuffer = new Buffer.from(response.data.wavBuffer.data);
      
      // Write the file on disk
      fs.writeFile(asticaAPI_outputFile, wavBuffer, function(err) {
        if (err) console.log('Error while saving file.');
      });
      
    } else {
      console.log(response.data);
    }
  } else {
    console.log('Failed to connect to the API.');
  }
}).catch(function (error) {
  console.log('Failed to connect to the API. Error: ', error.message);
});