<?php
/**
     *  Author: Colton
     *  Date: 7/1/16
     *  Filename: translator.php
     *
     *  Description:
     *  This file contains a generic translate function, as well as a more specific
     *  funciton that will translate to Nepali from english given a certain word
     */


/**
 *  translates text into a specified language
 *
 *  Takes a server api key, the text to be translated, a target language,
 *  and the source language
 *
 *  Returns an array from the JSON returned by the google translate API
 *
 */
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

/**
 *  Translates an english word to nepali
 *  
 *  Takes a string with the word to be translated
 *  
 *  Returns 
 */
function translateToNepali($word){

	$api_key = 'AIzaSyDl6vZfYbT9z8NumsSJSjdq77wIRqVHy7M';
	$text = $word;
	$source = "en";
	$target = "ne";
	$obj = translate($api_key,$text,$target,$source);

    return $obj['data']['translations'][0]['translatedText'];
}
 

?>