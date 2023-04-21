<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.org
    $asticaAPI_timeout = 35; //seconds

    $asticaAPI_endpoint = 'https://www.astica.org:9141/vision/describe';
    $asticaAPI_modelVersion = '1.0_full';  //1.0_full or 2.0_full  

    $asticaAPI_input = 'https://www.astica.org/inputs/analyze_3.jpg';
    $asticaAPI_visionParams = 'gpt, description, objects, faces'; //comma separated options; leave blank for all; note "gpt" and "gpt_detailed" are slow.
    /*
        '1.0_full' supported options:
            description
            objects
            categories
            moderate
            tags
            brands
            color
            faces
            celebrities
            landmarks
            gpt new (Slow - be patient)
            gpt_detailed new (Much Slower)
            
        '2.0_full' supported options:
            description
            objects
            tags
            describe_all new
            text_read new
            gpt new (Slow - be patient)
            gpt_detailed new (Much Slower)
     */

    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'visionParams' => $asticaAPI_visionParams,
        'input' => $asticaAPI_input,
    ];
    
    // Call API function and store result
    $result = asticaAPI($asticaAPI_endpoint, $asticaAPI_payload, $asticaAPI_timeout);

    
    //////////////////////////
    ///////////////////Caption
    //////////////////////////
    if(isset($result['caption_GPTS']) &&  $result['caption_GPTS'] != '') {
        echo '<hr><b>GPT Caption:</b> '.$result['caption_GPTS'].'<hr>';
    }
    if(isset($result['caption']) &&  $result['caption']['text'] != '') {
        echo '<hr><b>Caption:</b> '.$result['caption']['text'].'<hr>';
    }
    //////////////////////////
    //////////Detailed Caption
    //////////////////////////
    if(isset($result['CaptionDetailed']) &&  $result['CaptionDetailed']['text'] != '') {
        echo '<hr><b>CaptionDetailed:</b> '.$result['CaptionDetailed']['text'].'<hr>';
    }
    //////////////////////////
    ///////////////////Objects
    //////////////////////////
    if(isset($result['objects'])) {
        echo '<hr><b>Objects Found:</b> '.count($result['objects']);
        foreach($result['objects'] as $object) {
            echo '<li>';
            print_r($object);
            echo '</li>';
        }
    }
    //////////////////////////
    /////////////////////Faces
    //////////////////////////
    if(isset($result['faces'])) {
    echo '<hr><b>Faces Found:</b> '.count($result['faces']);
        foreach($result['faces'] as $face) {
            echo '<li>';
            print_r($face);
            echo '</li>';
        }
    }
    //////////////////////////
    ////////////////////Brands
    //////////////////////////
    if(isset($result['brand'])) {
    echo '<hr><b>Brands Found:</b> '.count($result['brands']);
        foreach($result['brand'] as $brand) {
            echo '<li>';
            print_r($brand);
            echo '</li>';
        }
    }
    //////////////////////////
    //////////////////////Tags
    //////////////////////////
    if(isset($result['tags'])) {
        echo '<hr>Tags: ';
        echo implode(', ', $result['tags']);
    }
    echo '<br><br><hr>API Usage: '.$result['astica']['api_qty'].' transactions';
    //////////////////////////
    ////////////////Raw Output
    //////////////////////////
    echo '<pre>'; print_r($result); echo '</pre>';
    
    
    
    
    
    
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