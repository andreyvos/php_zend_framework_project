<?php

abstract class T3Widgets_Abstract extends T3Widgets_Render {
    protected $viewName = null;
    protected $show = true;
    
    public function setShow($flag){
        $this->show = (bool)$flag;    
    }
    
    protected function getViewName(){
        if(!is_null($this->viewName)){
            return $this->viewName;
        }
        else {
            $temp = explode("_", get_class($this));
            return $temp[1];    
        }    
    }
    
    public function render(){
        if($this->show){
            return $this->renderFile($this->getViewName()); 
        }
        else {
            return;        
        }
    }    
}