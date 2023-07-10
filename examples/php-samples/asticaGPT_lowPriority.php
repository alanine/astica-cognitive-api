<?php
    /////////////////////////
    /////////////////////////
    //token of the API Call: (NOT API KEY)
    $asticaAPI_callGUID = '42932955-f497-4dca-9eeb-bea2488f492d';    
    //API endpoint for GPT generation results:
    $asticaAPI_uri = 'https://nlp.astica.org/result/'.$asticaAPI_callGUID.'.json';
    $asticaAPI_payload = [];
    /////////////////////////
    /////////////////////////
    
    $asticaAPI_result = asticaAPI($asticaAPI_uri, $asticaAPI_payload, 5);
    if($asticaAPI_result['status'] != 'error') {
        if($asticaAPI_result['status'] == 'waiting') {
            echo '<hr>We are still waiting for the task to finish..';
        }
        if($asticaAPI_result['status'] == 'success') {
            echo '<hr><b>GPT Output:</b> '.$asticaAPI_result['output'];
        }
    }
    echo '<hr>astica API Output:<br>';
    print_r($asticaAPI_result);
    
    
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