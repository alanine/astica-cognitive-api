const axios = require('axios');
const fs = require('fs');
const path = require('path');
const Base64 = require('js-base64').Base64;

let astica_api_key = 'API KEY HERE';    //visit https://astica.ai
let astica_model_id = 0;               //model_id to update
let astica_class_title = 'Dog'          //class will be created if it does not exist.
let astica_upload_path = 'image.jpg'    // File path or Folder path (PNG or JPEG)

/* 
    You can retrieve your model_id:
      Using astica dashboard: https://astica.ai/account/models/ 
      Using API: https://train.astica.ai/dataset/list
*/


async function asticaTrain_directory_upload(model_id, class_title, directory_path){
    fs.readdir(directory_path, (err, files) => {
        files.forEach(file => {
            let filePath = path.join(directory_path, file);
            asticaTrain_file_upload(model_id, class_title, filePath)
        });
    });
}

async function asticaTrain_file_upload(model_id, class_title, filePath){
    let file = fs.readFileSync(filePath, { encoding: 'base64' });
    let sample_uid = path.basename(filePath) + "_" + Math.floor(Math.random() * 100000);
    await asticaTrain_upload(model_id, class_title, file, sample_uid)
}

async function asticaTrain_upload(model_id, class_title, encoded_file, sample_uid){
    let astica_endpoint = 'https://train.astica.ai/model/upload';
    let astica_requestData = {
        tkn: astica_api_key,
        model_id: model_id,
        model_class: class_title,
        input: encoded_file,
        uid: sample_uid,
    }

    try{
        let response = await axios.post(astica_endpoint, astica_requestData, { headers: { 'Content-Type': 'application/json'}})
        
        if(response.data.status != 'OK'){
            console.log("api request failed");
            console.log(response.data.error);
        } else {
            // Handle the success response
            console.log("Upload successful");
        }

    }catch(error){
        console.log("Error: ", error);
    }
}

if(fs.lstatSync(astica_upload_path).isDirectory()){
    asticaTrain_directory_upload(astica_model_id, astica_class_title, astica_upload_path);
}else{
    asticaTrain_file_upload(astica_model_id, astica_class_title, astica_upload_path);
}