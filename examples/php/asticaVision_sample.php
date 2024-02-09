<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 30; // seconds  Using "gpt" or "gpt_detailed" will increase response time.

    $asticaAPI_endpoint = 'https://vision.astica.ai/describe';
    $asticaAPI_modelVersion = '2.1_full';  //1.0_full, 2.0_full, or 2.1_full 

    //Input Method 1: https URL of a jpg/png image (faster)
    $asticaAPI_input = 'https://astica.ai/example/asticaVision_sample.jpg'; 
    
    /*
    //Input Method 2: base64 encoded string of a local image (slower)  
    $image_path = 'image.jpg';
    $image_data = file_get_contents($image_path);
    $image_extension = pathinfo($image_path, PATHINFO_EXTENSION);
    $asticaAPI_input = 'data:image/' . $image_extension . ';base64,' . base64_encode($image_data);
    */
    
    //comma separated options; leave blank for all; note "gpt" and "gpt_detailed" are slower.
    //see all: https://astica.ai/vision/documentation/#parameters
    $asticaAPI_visionParams = 'gpt, describe, describe_all, tags, objects, faces'; 
    $asticaAPI_gpt_prompt = ''; // only used if visionParams includes "gpt" or "gpt_detailed"
    $asticaAPI_prompt_length = '90'; // number of words in GPT response
    
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
            gpt              (Slow)
            gpt_detailed     (Slower)
            
        '2.0_full' supported options:
            description
            objects
            tags
            describe_all 
            text_read 
            gpt             (Slow)
            gpt_detailed    (Slower)
            
         '2.1_full' supported options:
            Supports all options 
            
    */

    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'input' => $asticaAPI_input,
        'visionParams' => $asticaAPI_visionParams,
        'gpt_prompt' => $asticaAPI_gpt_prompt,
        'prompt_length' => $asticaAPI_prompt_length
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
