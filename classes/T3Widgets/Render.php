<?php

class T3Widgets_Render {
    static protected function getDirName(){
        return dirname(__FILE__) . DS . "view" . DS;    
    }
    
    public function renderFile($fileName){
        
        ob_start();
        include self::getDirName() . $fileName . ".phtml";
        $return = ob_get_contents();
        ob_clean();
        
        return $return;
    }    
}