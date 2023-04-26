import requests
def asticaVision(model_version, image_url, options=None, callback=None):
    url = 'https://www.astica.org:9141/vision/describe'
    headers = {
        'Content-Type': 'application/json'
    }
    print(image_url);
    data = {
        'tkn': 'API-KEY',
        'modelVersion': model_version,
        'input': image_url,
        'visionParams': options
        }
    response = requests.post(url, headers=headers, data=data, verify = False)
    print(response.text)
    
    if response.status_code == 200:
        return response.json()
    else:
        return {'error': 'Failed to process image.'}
    
Image = 'https://www.astica.org/inputs/analyze_3.jpg'
# Example 1
result = asticaVision('1.0_full', Image, 'Objects')
print(result)