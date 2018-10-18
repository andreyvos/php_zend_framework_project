<?php
/**
* Класс для рендера с макросами и параметрами
*/

abstract class T3Thank_AbstractRender extends DbSerializable {
    protected $renderFunctions = array(
        'urlencode' => 'urlencode',
        'unique_example' => array('T3Thank_AbstractRender', 'functionExample')
    );                                                   
    
    static public function functionExample($value){
        return md5(md5($value));    
    }
    
    public function abstractRender($text, $params = array()){
        if(is_array($params) && count($params)){
            foreach($params as $k => $v){
                $text = str_replace("{{$k}}", $v, $text); 
                
                foreach($this->renderFunctions as $k1 => $v1){
                    $text = str_replace("{{$k}/{$k1}}", call_user_func($v1, $v), $text);     
                }
            }
        }  
        return $text;     
    }    
}