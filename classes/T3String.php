<?php

class T3String {

    
       // these functions need to be moved somewhere, maybe separate class for string works         
    static public function value_in($element_name, $xml, $content_only = true) {
    if ($xml == false) {
        return false;
    }
    $found = preg_match('#&lt;'.$element_name.'(?:\s+[^>]+)?&gt;(.*?)'.
            '&lt;/'.$element_name.'&gt;#s', $xml, $matches);
    if ($found != false) {
        if ($content_only) {
            return $matches[1];  //ignore the enclosing tags
        } else {
            return $matches[0];  //return the full pattern match
        }
    }
    // No match found: return false.
    return false; 
	}
	
	
	
    static public function get_string_between($string, $start, $end){
	$string = " ".$string;
	$ini = strpos($string,$start);
	if ($ini == 0) return "";
	$ini += strlen($start);
	$len = strpos($string,$end,$ini) - $ini;
	return substr($string,$ini,$len);
	}    


}