<?php
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 25; // seconds

    $asticaAPI_endpoint = 'https://astica.ai:9161/gpt/generate';
    $asticaAPI_modelVersion = 'GPT-S2'; //engine to use.
    $asticaAPI_think_pass = 1; //INT; number of passes
    $asticaAPI_temperature = 0.7; //creativity of response
    $asticaAPI_top_p = 0.35; //diversity and predictability of response
    $asticaAPI_token_limit = 55; //Length of response
    
    $asticaAPI_stop_sequence = ''; //Comma separated. 'AI:,Human:'
    $asticaAPI_stream_output = 0; //(0 or 1); not available yet: Determines whether to display responses in real-time.
    $asticaAPI_low_priority = 0; //(0 or 1) Lower costs by receiving a URL to query for results. 

    $asticaAPI_instruction = ''; //optional; additional context priming.
    $asticaAPI_input = 'Write a sentence describing the iPhone 12:';
    
    echo '<H2>Using POST</h2>';
    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'instruction' => $asticaAPI_instruction,
        'input' => $asticaAPI_input,
        'think_pass' => $asticaAPI_think_pass,
        'temperature' => $asticaAPI_temperature,
        'top_p' => $asticaAPI_top_p,
        'token_limit' => $asticaAPI_token_limit,
        'stop_sequence' => $asticaAPI_stop_sequence,
        'stream_output' => $asticaAPI_stream_output,
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
                echo '<b>Output URI:</b><br> <a href="'.$asticaAPI_result['resultURI'].'" target="_blank">'.$asticaAPI_result['resultURI'].'</a>';
                echo '<hr><p><b>Note:</b>Query this URL to obtain the output of your results:</p>';  
                echo '<pre>$asticaAPI_result = json_decode(file_get_contents("'.$asticaAPI_result['resultURI'].'"), true);
echo \'Status: \'.$asticaAPI_result[\'status\'];
echo \'Output: \'.$asticaAPI_result[\'output\'];
echo \'astica API Output:<br>\';
print_r($asticaAPI_result)                
</pre>';
/*
    $asticaAPI_result = json_decode(file_get_contents($asticaAPI_result['resultURI']), true);
    echo 'Status: '.$asticaAPI_result['status'];
    echo 'Output: '.$asticaAPI_result['output'];
    echo 'astica API Output:<br>';
    print_r($asticaAPI_result);  
*/
            } else {
                echo '<b>Output:</b><br> ' . $asticaAPI_result['output'];
            }    
        }
    } else { echo 'Invalid response'; }        
    
    echo '<hr><b>astica API Output:</b><br>';  
    print_r($asticaAPI_result);

    /*
    echo '<H2>Using GET</h2>';
    $asticaAPI_endpoint_formatted = $asticaAPI_endpoint;
    $asticaAPI_endpoint_formatted .= '?model='.$asticaAPI_modelVersion;
    $asticaAPI_endpoint_formatted .= '&think_pass='.$asticaAPI_think_pass;
    $asticaAPI_endpoint_formatted .= '&temperature='.$asticaAPI_temperature;
    $asticaAPI_endpoint_formatted .= '&top_p='.$asticaAPI_top_p;
    $asticaAPI_endpoint_formatted .= '&token_limit='.$asticaAPI_top_p;
    $asticaAPI_endpoint_formatted .= '&instruction='.$asticaAPI_instruction;
    $asticaAPI_endpoint_formatted = 'input='.urlencode($asticaAPI_input);
    
    $result = file_get_contents($asticaAPI_endpoint_formatted);
    print_r($result);
    echo 'complete';
    */
    // Define API function
    function asticaAPI($endpoint, $payload, $timeout = 15) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout
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