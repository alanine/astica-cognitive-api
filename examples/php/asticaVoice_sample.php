<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 10; // API request timeout in seconds

    $asticaAPI_endpoint = 'https://voice.astica.ai/speak';
    $asticaAPI_modelVersion = '1.0_full';  //1.0_full or 2.0_full  

    $asticaAPI_voiceid = 0; //See: https://astica.ai/voice/documentation/
    $asticaAPI_input = 'hello, how are you doing today?'; //text to be spoken    
    
    $asticaAPI_outputFile = 'output.jpg';
    
    // Define payload array 
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'input' => $asticaAPI_input,
        'voice' => $asticaAPI_voiceid
    ];
    
    // Call API function and store result
    $result = asticaAPI($asticaAPI_endpoint, $asticaAPI_payload, $asticaAPI_timeout);

    // decode audio buffer to binary
    $wavData = implode(array_map('chr', $result['wavBuffer']['data']));
    
    // Write the contents to a file
    file_put_contents($asticaAPI_outputFile, $wavData);
        
 
 
 
 
 
 
    //////////////////////////
    ////////////////Raw Output
    //////////////////////////
    echo '<pre><h2>Raw Output:</h2>'; print_r($result); echo '</pre>';
    
    // Define API function
    function asticaAPI($endpoint, $payload, $timeout = 15) {
        $ch = curl_init();
        $payload = json_encode($payload);
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($payload),
                'Accept: application/json'
            ]
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);        
        $result = json_decode($response, true);
        //if(!isset($result['status'])) {
       //     $result = json_decode(json_decode($response), true);            
        //}
        return $result;
    }
?>