<?php

class T3Ui_After {   
  static public function render($main, $addHtmlType = 'default'){
    if(is_numeric($main)){
        if($addHtmlType == 'default'){
            $start = "<span style='color:#999'>";
            $fin = "</span>";
        }
        else {
            $start = "";
            $fin = "";    
        }
        
        if($main >= 2592000){
            $temp = floor($main/86400);
            if($temp%30 == 0)   $main = floor($temp/30) . " {$start}month{$fin}";        
            else {
                if(($temp%30) == 1) $main = floor($temp/30) . " {$start}month{$fin} " . ($temp%30) . " {$start}day{$fin}";
                else                $main = floor($temp/30) . " {$start}month{$fin} " . ($temp%30) . " {$start}days{$fin}"; 
            }
        }
        else if($main >= 86400){
            $temp = floor($main/3600);
            $days = floor($temp/24);
            if($temp%24 == 0){
                if($days == 1)  $main = $days . " {$start}day{$fin}";
                else            $main = $days . " {$start}days{$fin}"; 
            }
            else {
                if($days == 1)  $main = $days . " {$start}day{$fin} " . ($temp%24) . " {$start}hour{$fin}";
                else            $main = $days . " {$start}days{$fin} " . ($temp%24) . " {$start}hour{$fin}";
            }
        }
        else if($main >= 3600){
            $temp = floor($main/60);
            if($temp%60 == 0)   $main = floor($temp/60) . " {$start}hour{$fin}";        
            else                $main = floor($temp/60) . " {$start}hour{$fin} " . ($temp%60) . " {$start}min{$fin}";
        }
        else if($main >= 60){
            if($main%60 == 0)   $main = floor($main/60) . " {$start}min{$fin}";        
            else                $main = floor($main/60) . " {$start}min{$fin} " . ($main%60) . " {$start}sec{$fin}";
        }
        else {
            $main = "{$main} {$start}sec{$fin}";    
        }     
    }
    else {
        $main = '';    
    }
    
    return $main; 
  }
}
