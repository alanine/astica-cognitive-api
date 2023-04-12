<!-- Improved compatibility of back to top link: See: https://github.com/othneildrew/Best-README-Template/pull/73 -->
<a name="readme-top"></a>
<!--
*** Thanks for checking out the Best-README-Template. If you have a suggestion
*** that would make this better, please fork the repo and create a pull request
*** or simply open an issue with the tag "enhancement".
*** Don't forget to give the project a star!
*** Thanks again! Now go create something AMAZING! :D
-->



<!-- PROJECT SHIELDS -->
<!--
*** I'm using markdown "reference style" links for readability.
*** Reference links are enclosed in brackets [ ] instead of parentheses ( ).
*** See the bottom of this document for the declaration of the reference variables
*** for contributors-url, forks-url, etc. This is an optional, concise syntax you may use.
*** https://www.markdownguide.org/basic-syntax/#reference-style-links
-->


<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://astica.org">
    <img src="examples/javascript-API/asset/img/icon.png" alt="astica" width="80" height="80">
  </a>

  <h3 align="center">astica Cognitive API - Javascript</h3>

  <p align="center">
    Speak, hear, and see with one line of javascript.
    <br />
    <a href="https://astica.org"><strong>Powered by astica.org</strong></a>
    <br />
    <br />
    <a href="https://www.astica.org/vision/describe/?fr=git" title="asticaVision demo">Vision Demo</a>
    ·
    <a href="https://www.astica.org/code-examples/javascript-API/asticaListen_sample.html" title="asticaListen demo">Listen Demo</a>
    ·
    <a href="https://www.astica.org/code-examples/javascript-API/asticaVoice_sample.html" title="asticaVoice demo">Voice Demo</a>
    ·
    <a href="https://www.astica.org/code-examples/javascript-API/asticaGPT_sample.html" title="asticaGPT demo">GPT-S Demo</a>
  </p>
</div>





<!-- GETTING STARTED -->
## Getting Started

These demonstrations require an API key for the astica.org cognitive API. You can register and get your key instantly.

### Quick Start


  ```js
<script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
<script>
    asticaAPI_start('API KEY HERE'); //only needs to be called once.        
    
    //Simple Vision:      
    asticaVision('Image URL'); //simple computer vision  
    asticaVision('Image URL', 'Description,Faces,Objects'); //with options:
    
    //Simple Listen:      
    asticaListen('https://www.astica.org/endpoint/ml/inputs/audio/sample_1.wav'); 
    
    //Simple Voice:      
    asticaVoice('Hi! How are you doing today?');

    //Simple GPT-S:      
    asticaGPT('What color is an apple?');
    
</script>
  ```

### Installation

_Get started by including the astica.api.js within your project._

1. Get your API Key at [https://astica.org](https://astica.org)
2. Add the astica javascript API to your project:


   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
   ```
   
3. Authenticate:


   ```js
   asticaAPI_start('API KEY HERE'); //only needs to be called once.      
   ```
   
4. Using asticaVision:



   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
    <script>
        asticaAPI_start('API KEY HERE'); //run at least once    

       
        //Example 1:   
        asticaVision('Image URL', 'Objects'); //simple computer vision  
        //Example 2:   
        asticaVision('Image URL', 'Description,Faces,Objects'); //with options:
      
        //Example 3:      
        //advanced with parameters:
        asticaVision(
            '1.0_full', //modelVersion: 1.0_full, 2.0_full
            'IMAGE URL', //Input Image
            'Description,Moderate,Faces', //or 'all'
            your_astica_CallBack, //Your Custom Callback function
        ); 
        
        //Set Your Custom Callback Function 
        function your_astica_CallBack(data) {   
            if(typeof data.error != 'undefined') { alert(data.error); }         
            console.log(data); //view all data
        }	   
    </script>
   ```
   
5. Using asticaListen with remote File:


   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
    <script>
        //simple usage
        function asticaListen_Sample_simple() {  
            asticaListen('https://www.astica.org/endpoint/ml/inputs/audio/sample_1.wav'); 
        }       
        setTimeout(function() { 
            asticaAPI_start('API KEY HERE'); //run at least once    
            asticaListen_Sample_simple();  
        }, 2000);
        
        
        //with parameters:
        function asticaListen_Sample() {  
            
            var astica_modelVersion = '1.0_full';
            var astica_modelInput = 'https://www.astica.org/endpoint/ml/inputs/audio/sample_1.wav';
         
            //With default callback:
            asticaListen(astica_modelVersion, astica_modelInput); 
            
            //With custom callback function:
            asticaListen(astica_modelVersion, astica_modelInput, your_astica_CallBack);          
        }    
        function your_astica_CallBack(data) {   
            if(typeof data.error != 'undefined') { alert(data.error); }         
            console.log(data); //view all data
        }	
        setTimeout(function() { 
            asticaAPI_start('API KEY HERE'); //run at least once    
            asticaListen_Sample(); 
        }, 1000);
    </script>
   ```
   
6. Using asticaListen with local File:


   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
    <script>
        var asticaTranscribeFile_input = document.getElementById('astica_ML_voice_input');     
        var asticaTranscribeFile_localData;
        document.addEventListener("DOMContentLoaded", () => {                    
            asticaTranscribeFile_input.addEventListener("change", function () {
                asticaTranscribeFile = asticaTranscribeFile_input.files[0];
            });
        });
        function asticaVoice_transcribeFile_test() {
            asticaAPI_start(document.getElementById("astica_ML_apikey").value); //only needs to be called once.   
            asticaListen_file('1.0_full', asticaTranscribeFile, your_astica_CallBack);                
        } 
        function your_astica_CallBack(data) {     
            if(typeof data.error != 'undefined') { alert(data.error); return; }
            console.log(data);
        }	
        //view all data
    </script>


   ```
   
7. Using asticaVoice:


   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
    <script>
        asticaAPI_start('API KEY HERE'); //only needs to be called once.        
        
        //Simple usage:  
        
        asticaVoice('Hi! How are you doing today?');
        
        
        //Specify a voice id:
        
        asticaVoice('Hi! How are you doing today?', 5);
        
        //With custom callback:
        
        asticaVoice_speak('Hi! How are you doing today?', 5, your_astica_CallBack);   
        function your_astica_CallBack(data) {     
            if(typeof data.error != 'undefined') { alert(data.error); return; }      
        }	
          
          
        //With function and paramaters:
        
        function asticaVoice_example(string) {
            asticaVoice(
                '1.0_full'
                string,
                'en-US', 
                0, 
                your_astica_CallBack
            );               
        } 
        setTimeout(function() { 
            asticaAPI_start('API KEY HERE'); //only needs to be called once.   
            asticaVoice_example('Hi! How are you doing?'); 
        }, 1000);
    </script>
   ```

7. Using asticaGPT:


   ```js
    <script src="https://www.astica.org/endpoint/ml/javascript/2023-04-10/astica.api.js"></script>
    <script>
        asticaAPI_start('API KEY HERE'); //only needs to be called once.  

        //Simple Usage:
        asticaGPT_generate("Write a sentence about apples:")

        //Simple Usage with Token Limit:
        asticaGPT_generate("Write a sentence about apples:", 10)

        //With Token Limit:
        asticaGPT_generate("Write a sentence about apples:", 10)

        //Advanced:
         var asticaGPT_params = {
            temperature:document.getElementById("astica_ML_temperature").value,
            top_p:document.getElementById("astica_ML_top_p").value,
            think_pass:document.getElementById("astica_ML_think_pass").value,
            stop_sequence:document.getElementById("astica_ML_stop_sequence").value,
            stream_output: document.getElementById("astica_ML_stream_output").value
        }

        asticaGPT_generate(
            document.getElementById("astica_ML_version").value,
            document.getElementById("astica_ML_gpt_input").value,
            document.getElementById("astica_ML_tokens_max").value,
            asticaGPT_params
        );        



        //default asticaGPT callbacks
        function asticaGPT_generateComplete(data){
            console.log(data);
            if(typeof data.error != 'undefined') { alert(data.error); }     
            $('#your_div').val(data.output)
        }        
        function asticaGPT_generatePreview(data){   
            console.log(data);               
            if(typeof data.error != 'undefined') { alert(data.error); return; }     
            $('#your_div').val(data.output)                    
        }  


        //Plug and play example:

        function asticaGPT_example(string, tokens) {
           asticaGPT_generate(string, tokens) 
        } 
        setTimeout(function() { 
            asticaAPI_start('API KEY HERE'); //only needs to be called once.   
            asticaGPT_example('Write a sentence about apples:', 55); //max 55 tokens
        }, 1000);  
    </script>
   ```
      

<p align="right">(<a href="#readme-top">back to top</a>)</p>




<!-- CONTACT -->
## Contact

success@onamal.com

[https://github.com/alanine/astica-cognitive-api/tree/main/examples/javascript-API](https://github.com/alanine/astica-cognitive-api/tree/main/examples/javascript-API)

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/othneildrew/Best-README-Template.svg?style=for-the-badge
[contributors-url]: https://github.com/othneildrew/Best-README-Template/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/othneildrew/Best-README-Template.svg?style=for-the-badge
[forks-url]: https://github.com/othneildrew/Best-README-Template/network/members
[stars-shield]: https://img.shields.io/github/stars/othneildrew/Best-README-Template.svg?style=for-the-badge
[stars-url]: https://github.com/othneildrew/Best-README-Template/stargazers
[issues-shield]: https://img.shields.io/github/issues/othneildrew/Best-README-Template.svg?style=for-the-badge
[issues-url]: https://github.com/othneildrew/Best-README-Template/issues
[license-shield]: https://img.shields.io/github/license/othneildrew/Best-README-Template.svg?style=for-the-badge
[license-url]: https://github.com/othneildrew/Best-README-Template/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/othneildrew
[product-screenshot]: images/screenshot.png
[Next.js]: https://img.shields.io/badge/next.js-000000?style=for-the-badge&logo=nextdotjs&logoColor=white
[Next-url]: https://nextjs.org/
[React.js]: https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB
[React-url]: https://reactjs.org/
[Vue.js]: https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D
[Vue-url]: https://vuejs.org/
[Angular.io]: https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white
[Angular-url]: https://angular.io/
[Svelte.dev]: https://img.shields.io/badge/Svelte-4A4A55?style=for-the-badge&logo=svelte&logoColor=FF3E00
[Svelte-url]: https://svelte.dev/
[Laravel.com]: https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white
[Laravel-url]: https://laravel.com
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com 
