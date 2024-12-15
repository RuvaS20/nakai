<?php
// Add this to functions/news.php

function fetchArtNews() {
    $apiKey = '8d9fd1f9-c9d9-4228-87e0-8a92d0cdd5a2';
    $section = 'artanddesign';
    $pageSize = 6;
    
    $url = "https://content.guardianapis.com/search?"
         . "section={$section}&"
         . "show-fields=thumbnail,trailText&"
         . "page-size={$pageSize}&"
         . "api-key={$apiKey}&"
         . "order-by=newest";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        return false;
    }
    
    curl_close($ch);
    
    return json_decode($response, true);
}
?>