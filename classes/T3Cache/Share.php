<?php

class T3Cache_Share {
    static protected $total;
    
    static public function total($total){
        self::$total = $total;
    } 
    
    static public function get($sum){
        if(self::$total == 0)return "0 %";
        return sprintf("%.02f", round(($sum/self::$total)*100 ,2)) . " %";
    } 
}