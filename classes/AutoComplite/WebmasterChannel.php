<?php

class AutoComplite_WebmasterChannel extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_WebmasterChannel';
    
    /** @return Users_AutoComplite */
    static public function create($name){
        return new self($name);
    } 
    
    protected function updateList($data){
        foreach($data as $k => $v){
            $type = ($v['channel_type'] == 'js_form') ? "Form" : "Post";     
            $data[$k]['label'] = "{$v['id']} ({$type}:" . T3Products::getTitle($v['product']) . ") {$v['title']}";
        }
        
        return $data;
    }
    
    
    
    protected function init(){ 
        /*******************   Настройка вывода списка   *********************/
        // настрйока запроса
        if(T3Users::getCUser()->isRoleWebmaster() || $this->getRel('webmaster')){
            $this->list_select = T3Db::api()->select()
            ->from("cache_summary_days_details", array(
                'id' => 'cache_summary_days_details.channel_id',
                'product' => 'channels.product',
                'channel_type' => 'channels.channel_type',
                'title' => 'channels.title'
            ))
            ->joinLeft("channels", "channels.id=cache_summary_days_details.channel_id")
            ->where("cache_summary_days_details.channel_id  > 1")
            ->group("cache_summary_days_details.channel_id")
             
            ->limit(15);
            
            if(T3Users::getCUser()->isRoleWebmaster()){
                $this->list_select->where("cache_summary_days_details.userid=?", T3Users::getCUser()->company_id);
            }
            
            if($this->getRel('product'))    $this->list_select->where("channels.product=?", $this->getRel('product'));   
            if($this->getRel('type'))       $this->list_select->where("channels.channel_type=?", $this->getRel('type'));   
            if($this->getRel('webmaster'))  $this->list_select->where("cache_summary_days_details.userid=?", $this->getRel('webmaster'));  
            
            // видимая часть будет производить поиск по этим полям
            $this->search_fields = array(
                'channels.title', 
                'cache_summary_days_details.channel_id'
            );
            
            // настрйока вывода
            $this->list_value = AP_UI_Var::context('id');   
            $this->list_label = AP_UI_Var::value('@@label@@')->addDecorator(
                new AP_UI_Decorator_Pattern()
            );
            
            // Настройка Валидаторов
            $this->validate_select = T3Db::api()->select()->from("channels", "id");
            $this->validate_main = 'channels.id';
        }
        else {
            $this->list_select = T3Db::api()->select()
            ->from("channels", array(
                'id'            => 'channels.id',
                'product'       => 'channels.product',
                'channel_type'  => 'channels.channel_type',
                'title'         => 'channels.title'
            ))
             
            ->limit(15);  
             
            if($this->getRel('product'))    $this->list_select->where("channels.product=?", $this->getRel('product'));   
            if($this->getRel('type'))       $this->list_select->where("channels.channel_type=?", $this->getRel('type'));                 
            
            // видимая часть будет производить поиск по этим полям
            $this->search_fields = array(
                'channels.title', 
                'channels.id'
            );
            
            // настрйока вывода
            $this->list_value = AP_UI_Var::context('id');   
            $this->list_label = AP_UI_Var::value('@@label@@')->addDecorator(
                new AP_UI_Decorator_Pattern()
            );
            
            // Настройка Валидаторов
            $this->validate_select = T3Db::api()->select()->from("channels", "id");
            $this->validate_main = 'channels.id';    
        }    
    }
}