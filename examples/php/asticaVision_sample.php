<?php    
    $asticaAPI_key = 'YOUR API KEY'; //visit https://astica.ai
    $asticaAPI_timeout = 30; // seconds  Using "gpt" or "gpt_detailed" will increase response time.

    $asticaAPI_endpoint = 'https://vision.astica.ai/describe';
    $asticaAPI_modelVersion = '2.5_full';  //1.0_full, 2.0_full, 2.1_full or 2.5_full
    
    if(1 == 2) {
        //Input Method 1: https URL of a jpg/png image (faster)
        $asticaAPI_input = 'https://astica.ai/example/asticaVision_sample.jpg'; 
    } else {
        //Input Method 2: base64 encoded string of a local image (slower)          
        $image_path = 'image.jpg';
        $image_data = file_get_contents($image_path);
        $asticaAPI_input = base64_encode($image_data);
    }
    
    //comma separated options; leave blank for all; note "gpt" and "gpt_detailed" are slower.
    //see all: https://astica.ai/vision/documentation/#parameters
    $asticaAPI_visionParams = 'gpt, describe, describe_all, tags, objects, faces'; 
    $asticaAPI_visionParams = ''; 
    $asticaAPI_gpt_prompt = ''; // only used if visionParams includes "gpt" or "gpt_detailed"
    $asticaAPI_prompt_length = '90'; // number of words in GPT response
    $objects_custom_kw = ''; // only used if visionParams includes "objects_custom" (v2.5_full or higher)
    

    /*        
        '2.5_full' supported visionParams: https://astica.ai/vision/documentation/#parameters
            describe
            describe_all
            gpt (or) gpt_detailed 
            text_read
            objects
            objects_custom
            objects_color
            categories
            moderate
            tags
            color
            faces
            celebrities
            landmarks
            brands        
    */

    // Define payload array
    $asticaAPI_payload = [
        'tkn' => $asticaAPI_key,
        'modelVersion' => $asticaAPI_modelVersion,
        'input' => $asticaAPI_input,
        'visionParams' => $asticaAPI_visionParams,
        'gpt_prompt' => $asticaAPI_gpt_prompt,
        'prompt_length' => $asticaAPI_prompt_length,
        'objects_custom_kw' => $objects_custom_kw
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
            //echo 'Caption Text: '.$caption['text'];
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
    ///////////Custom Objects
    //////////////////////////
    if(isset($result['objects_custom'])) {
        echo '<hr><b>Custom Objects Found:</b> '.count($result['objects_custom']);
        foreach($result['objects_custom'] as $object_custom) {
            echo '<li>';
            print_r($object_custom);
            echo '</li>';
        }
    }
    //////////////////////////
    ///////////Colors Objects
    //////////////////////////
    if(isset($result['colors_object'])) {
        echo '<hr><b>Object Colors:</b> '.count($result['colors_object']);
        foreach($result['colors_object'] as $object_color) {
            echo '<li>';
            print_r($object_color);
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
