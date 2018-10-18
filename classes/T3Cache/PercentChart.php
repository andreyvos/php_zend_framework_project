<?php

class T3Cache_PercentChart {
    static $width = "200px";
    
    static public function setWidth($width){
        self::$width = $width;  
    }
    
    static public function render($percent){
        // MyZend_Site::addCSS('table/status.css');
        $percent = (float)$percent;
        if($percent < 0) $percent = 0;
        //if($percent > 100) $percent = 100;
        
        $percentInt = round($percent);

        $width_chart = $percentInt;
        if ($width_chart>100){
            $width_chart = 100;
        }
        
        return "<!--" . sprintf('%04d', round($percent*10)) . "--><div style='height:20px;width:" . self::$width . 
            ";'><div style='height:13px;white-space: nowrap;width:{$width_chart}%;background:#AEA;" .
            "border:#8D8 solid 1px;padding:3px;-moz-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px;'>{$percent} %</div></div>";
    }
    
}
