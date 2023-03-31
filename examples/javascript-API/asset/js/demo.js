
  
function letUsKnow() {
    dynamicallyLoadScript('https://www.googletagmanager.com/gtag/js?id=G-81KKR01C0H)';
    setTimoue(function() {
        window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'G-81KKR01C0H');
    },15000);
}  
letUsKnow();
function dynamicallyLoadScript(url) {
    var script = document.createElement("script");  // create a script DOM node
    script.src = url;  // set its src to the provided URL
   
    document.head.appendChild(script);  // add it to the end of the head section of the page (could change 'head' to 'body' to add it to the end of the body section instead)
}