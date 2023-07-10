<?php
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 25; // seconds

    $asticaAPI_endpoint = 'https://listen.astica.ai/transcribe';
    $asticaAPI_modelVersion = '1.0_full';

    $asticaAPI_doStream = 0; //Determines whether to display responses in real-time.
    $asticaAPI_low_priority = 0; //Lower costs by receiving a URL to query for results. 
    
    $asticaAPI_input = 'https://astica.ai/example/asticaListen_sample.wav';

    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'input' => $asticaAPI_input,
        'doStream' => $asticaAPI_doStream,
        'low_priority' => $asticaAPI_low_priority
    ];

    // Call API function and store result
    $asticaAPI_result = asticaAPI($asticaAPI_endpoint, $asticaAPI_payload, $asticaAPI_timeout);

    
    
        //Handle asticaAPI response
    if(isset($asticaAPI_result['status'])) {
        // Output Error if exists    
       if($asticaAPI_result['status'] == 'error') {        
            echo '<b>Output:</b><br> ' . $asticaAPI_result['error'];
        } 
        // Output Success if exists
        if($asticaAPI_result['status'] == 'success') {     
            if(isset($asticaAPI_result['resultURI'])) {  
                echo '<b>Output URI:</b><br> ' . $asticaAPI_result['resultURI'];
                echo '<br><p>Query this URL to obtain the output of your results';               
            } else {
                echo '<b>Output:</b><br> ' . $asticaAPI_result['text'];
            }    
        }
    } else { echo 'Invalid response'; }        
    
    echo '<hr><b>astica API Output:</b><br>'; 
    print_r($asticaAPI_result);
    
    /*
    // Output Error if exists
    if(isset($asticaAPI_result['error'])) {        
        echo '<b>Output:</b><br> ' . $asticaAPI_result['error'];
    } 
    
    
    
    
    // Output Success if exists
    if(isset($asticaAPI_result['success'])) {  
        echo '<b>Output:</b><br> ' . $asticaAPI_result['text'];
    } else { 
        echo 'Invalid response'; 
    }
    echo '<hr><b>Raw Output:</b><br>'; print_r($asticaAPI_result);

    */
    // Define API function
    function asticaAPI($endpoint, $payload, $timeout = 15) {
        // Initialize cURL session
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
        
        // Execute cURL request
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Decode JSON response and return
        return json_decode($response, true);
    }
?>