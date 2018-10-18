<?php

class T3Ui_Concatenation {
    /**
    * Создание элемента управления
    * 
    * @param mixed $name
    * @param mixed $value
    */
    static public function renderElement($name = 'concatenation', $value = '-!defaultPostGet!-'){
        MyZend_Site::addJS("tasks/concatenation.js");
        MyZend_Site::addCSS("tasks/main.css");
        
        if($value == '-!defaultPostGet!-'){
            $value = ifset($_POST[$name], ifset($_GET[$name], null));    
        }
        
        if($value == ''){
            $value = "{}";    
        }   
        
        MyZend_Site::addJS(array(
            "jquery.color.js", 
            'json/JSON.js',
            'json/JSONError.js', 
        ));
        
        echo "
        <script>
            var concatenation = new concatenation('concatenation', " . Zend_Json::encode(T3Task_General::getUsers()) . ");
            concatenation.setCurrentUsers({$value});
            concatenation.renderWrite();
        </script>";
    }
    
    static public function renderText(T3Task_Item $task){
        MyZend_Site::addJS("tasks/concatenation.js");
        MyZend_Site::addCSS("tasks/main.css");
        
        //varExport($task->concatenationArray);
        $result = '';
        
        if(!count($task->concatenationArray['users'])){
            $result =  "<span class='concatenation_none'>None</span>";    
        }
        else {
            for($i = 0; $i< count($task->concatenationArray['users']); $i++){
                if($i!=0) $result.= " &nbsp;&rarr;&nbsp; ";
                
                if($i == $task->concatenationArray['current'])        $addClass = 'concatenation_user_now';
                else if($i > $task->concatenationArray['current'])    $addClass = 'concatenation_user_wait';
                else if($i < $task->concatenationArray['current'])    $addClass = 'concatenation_user_complite';
                
                $result.= "<span class='{$addClass}'>{$task->concatenationArray['users'][$i]['name']}</span>";     
            } 
        }
        
        return $result;
    }
    
    static public function renderTextForEmail(T3Task_Item $task){
        MyZend_Site::addJS("tasks/concatenation.js");
        MyZend_Site::addCSS("tasks/main.css");
        
        //varExport($task->concatenationArray);
        $result = '';
        
        if(!count($task->concatenationArray['users'])){
            $result =  "<span class='concatenation_none'>None</span>";    
        }
        else {
            for($i = 0; $i< count($task->concatenationArray['users']); $i++){
                if($i!=0) $result.= " &nbsp;&rarr;&nbsp; ";
                
                if($i == $task->concatenationArray['current'])        $addClass = 'color:#000;';
                else if($i > $task->concatenationArray['current'])    $addClass = 'color:#999;';
                else if($i < $task->concatenationArray['current'])    $addClass = 'color:#0A0;';
                
                $result.= "<span style='{$addClass}'>{$task->concatenationArray['users'][$i]['name']}</span>";     
            } 
        }
        
        return $result;
    }    
}