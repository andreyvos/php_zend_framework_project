<?php

class T3Widgets extends T3Widgets_Render {
    protected $apps = array();
    
    static public function createObject(){
        $obj = new T3Widgets;
        $obj->addDefaultWidgets();
        
        return $obj;    
    }
    
    /**
    * Добавление виджетов по умолчанию
    */
    public function addDefaultWidgets() {
        if (T3Users::getCUser()->isRoleWebmaster()) {
            //Добавил в связи с акцией про ipad
            $this->ipad = T3Users::getInstance()->getCurrentUser()->ipad;
            if ($this->ipad == 0) {
                $this->addWidget('Baner');
            }
            ///--конец кина----///
            $this->addWidget('AccountRepresentative');
        }
        
        if(T3Users::getCUser()->isRoleWebmaster() || T3Users::getCUser()->isRoleBuyer()){
            $this->addWidget('Earnings');    
        }
        $this->addWidget('PersonalInfo');

        if(T3Users::getCUser()->isRoleWebmaster()){
            // $this->addWidget('OtherProjects');
        }

        $this->addWidget('Tasks');
        $this->addWidget('Notifications');           
    }
    
    /**
    * Добавление нового виджета
    * 
    * @param mixed $widget
    */
    public function addWidget($widget){
        if(is_string($widget)){
            $class_name = "T3Widgets_{$widget}";
            $widget = new $class_name(); 
        }
        
        if($widget instanceof T3Widgets_Abstract){
            $this->apps[] = $widget;
        }            
    }                                       
    
    public function render(){
        return self::renderFile('layout');
    }    
}