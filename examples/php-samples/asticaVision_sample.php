<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.org
    $asticaAPI_timeout = 60; // seconds  Using "gpt" or "gpt_detailed" will increase response time.

    $asticaAPI_endpoint = 'https://www.astica.org:9141/vision/describe';
    $asticaAPI_modelVersion = '2.0_full';  //1.0_full or 2.0_full  

    $asticaAPI_input = 'https://www.astica.org/inputs/analyze_3.jpg'; //or base64 encoded string: data:image/png;base64,iVBORw0KG.....
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
    //////Caption and Describe
    //////////////////////////
    if(isset($result['caption_GPTS']) &&  $result['caption_GPTS'] != '') {
        echo '<hr><b>GPT Caption:</b> '.$result['caption_GPTS'];
    }
    if(isset($result['caption']) &&  $result['caption']['text'] != '') {
        echo '<hr><b>Caption:</b> '.$result['caption']['text'];
    }    
    if(isset($result['caption_list'])) { //v2.0_full only
        echo '<hr><b>Additional Captions:</b> '.count($result['caption_list']);
        foreach($result['caption_list'] as $caption) {
            echo '<li>';
            print_r($caption);
            echo '</li>';
        }
    }
    if(isset($result['caption_tags'])) {
    echo '<hr><b>Caption Tags Found:</b> '.count($result['caption_tags']);
        foreach($result['caption_tags'] as $caption_tags) {
            echo '<li>';
            print_r($caption_tags);
            echo '</li>';
        }
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
    echo '<hr><b>Tags Found:</b> '.count($result['tags']);
        foreach($result['faces'] as $tags) {
            echo '<li>';
            print_r($tags);
            echo '</li>';
        }
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
