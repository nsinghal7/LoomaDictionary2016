<?php

function translate($api_key,$text,$target,$source)
{
$url = 'https://www.googleapis.com/language/translate/v2?q=' . rawurlencode($text) . '&target='. $target .'&format=text&source='. $source .'&key='.$api_key . '';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);                 
    curl_close($ch);
 
    $obj =json_decode($response,true); //true converts stdClass to associative array.
    return $obj;
}   

function translateToNepali($word){

	$api_key = 'AIzaSyDl6vZfYbT9z8NumsSJSjdq77wIRqVHy7M';
	$text = $word;
	$source = "en";
	$target = "ne";
	return translate($api_key,$text,$target,$source);
}
 

?>