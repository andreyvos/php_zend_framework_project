<?php

TableDescription::addTable('thank_pages', array(
   'id',
   'template',
   'content',  
   'params', 
   'filters',
));        

class T3Thank_Page extends DbSerializable {
    public $id;
    public $template;
    public $content;
    public $params = array();
    public $filters = array();
    
    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('thank_pages'); 
    }
    
    public function toArray($tables = null){                                  
        $temp = $this->getParams();
        
        $this->params = serialize($this->params); 
        $this->filters = serialize($this->filters); 
        
        $return = parent::toArray($tables);
        $this->setParams($temp);
        
        return $return;
    }  
    
    public function fromArray(&$array){
        parent::fromArray($array);
        
        $this->params = unserialize($this->params); 
        $this->filters = unserialize($this->filters);    
    }
    
    /**
    * @return T3Thank_Content
    */
    public function getContentObject(){
        return T3Thank_Main::getContent($this->content);
    }
    
    /**
    * @return T3Thank_Template
    */
    public function getTemplateObject(){
        return T3Thank_Main::getTemplate($this->template);
    }
    
    public function render(){
        /**
        * 2. получить объект шаблона типа
        * 3. получить объект общего шаблона 
        * 
        * 4. отрендерить шаблон типа с параметрами tnx
        * 5. отрендерить общий шаблон, с параметрами типа + вставить в него отрендеренный шаблон типа      
        */  
        if($this->getTemplateObject()->id && $this->getContentObject()->id){
            
            
            return $this->getTemplateObject()->getObject()->render(
                $this->params + 
                array(
                    'content' => $this->getContentObject()->getObject()->render($this->params)
                )
            );
        }
        return null;
    }
}