<?php

class T3Render_Width {
    static public function render($min, $max){
        $unknown    =   "<span style='color:#777'>?</span>"; 
        $px         =   "<span style='color:#AAA;margin-left:2px;font-size:10px;'>px</span>";
        
        if($min == 0 && $max == 0)  $main = $unknown;
        else if($min == $max)       $main = "{$min}{$px}";
        else if($min == 0)          $main = "{$unknown} - {$max}{$px}";   
        else if($max == 0)          $main = "{$min}{$px} - {$unknown}";   
        else                        $main = "{$min}{$px} - {$max}{$px}";
        
        return $main;
    }    
}