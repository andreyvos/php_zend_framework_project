<?php

class FraudAutoFillSum {

    public static function AddToWM($wmid, $c) {
        $r = T3Db::api()->fetchRow("select * from auto_fill_wm where wm='".$wmid."'");

        if ($r == false){
            T3Db::api()->query("insert into auto_fill_wm values(null, '".$wmid."', '".$c."', 1, '".$c."');");
        }
        else{
            T3Db::api()->query("update auto_fill_wm set c=c+1, sum=sum+'".$c."', avg=round(sum/c) where wm='".$wmid."'");
        }  
    }

    public static function CalcLambda($inputArray, $prod="") {
        // Получаем средние значения
        $avreageValue = T3Db::api()->fetchRow("select * from auto_fill_log_sum where product='".$prod."'");
        $params = array(
            "ev_focus",         "ev_blur",          "ev_change",        "ev_click", 
            "ev_dblclick",      "ev_error",         "ev_keydown",       "ev_keypress", 
            "ev_keyup",         "ev_mousedown",     "ev_mousemove",     "ev_mouseout", 
            "ev_mouseover",     "ev_mouseup",       "ev_resize",        "ev_select" 
        );

        $summD = 0;
        $pCount = 0;

        foreach ($params as $paramName) {
            $inputValue = $inputArray[$paramName];
            $avgValue = $avreageValue[$paramName];
            
            $d = $avgValue - $inputValue;
            
            if ($d > 0){
                $summD += ($d / $avgValue) * 100;//abs($avgValue - $inputValue);
                $pCount++;
            }

        }
        
        if($pCount != 0){
            $avgD = round($summD / $pCount); // count($params)
        }
        else {
            $avgD = 0;   
        }
        
        return $avgD;
    }
}
