<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 60; // seconds

    //See Input Documentation:  https://astica.ai/design/documentation/#inputs
    
    $asticaAPI_endpoint = 'https://design.astica.ai/generate_image';
    $asticaAPI_modelVersion = '2.0_full';  
    $asticaAPI_prompt = 'close-up photography of older gentleman standing in the rain at night, in a street lit by lamps'; 
    $asticaAPI_prompt_negative = '';
    $asticaAPI_generate_quality = 'faster'; //high, standard, fast, faster
    $asticaAPI_generate_lossless = 0; //0 = Default JPG, 1 = lossless uncompressed PNG
    $asticaAPI_seed = 0; //0 will randomize the seed for every generation
    $asticaAPI_moderate = 1; 
    $asticaAPI_low_priority = 0; //0 = realtime, 1 = low_priority (lower cost)

    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'prompt' => $asticaAPI_prompt,
        'prompt_negative' => $asticaAPI_prompt_negative,
        'generate_quality' => $asticaAPI_generate_quality,
        'generate_lossless' => $asticaAPI_generate_lossless,
        'seed' => $asticaAPI_seed,
        'moderate' => $asticaAPI_moderate,
        'low_priority' => $asticaAPI_low_priority,
    ];
    
    // Call API function and store result
    $result = asticaAPI($asticaAPI_endpoint, $asticaAPI_payload, $asticaAPI_timeout);
    print_r($result); echo '<br>';
    if ($result['status'] === 'error') {
        echo 'Error:', $result['error'];
    } else if ($result['status'] === 'success') {
        if (isset($result['resultURI'])) {
            echo '<br>===============<br>';
            echo 'Low Priority URI: ', $result['resultURI'], '<br>Query this URL to obtain the output of your results';
            echo '<br>===============<br>';
        } else {
            echo '===============<br>';
            echo 'Generated Image:', $result['output'];
            echo '===============<br>';
        }
    } else { 
        echo 'Invalid response'; 
    } 

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
        if(!isset($result['status'])) {
            $result = json_decode(json_decode($response), true);            
        }
        return $result;
    }
?>