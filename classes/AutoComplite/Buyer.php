<?php

class AutoComplite_Buyer extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_Buyer';
    
    /** @return Users_AutoComplite */
    static public function create($name){
        return new self($name);
    } 
    
    protected function init(){ 
        /*******************   Настройка вывода списка   *********************/
        // настрйока запроса
        $this->list_select = T3Db::api()->select()
        ->from("users_company_buyer", array('id', 'systemName'))

        ->limit(15);
        
        // видимая часть будет производить поиск по этим полям
        $this->search_fields = array(
            'users_company_buyer.id', 
            'users_company_buyer.systemName'
        );
        
        // настрйока вывода
        $this->list_value = AP_UI_Var::context('id');   
        $this->list_label = AP_UI_Var::value('@@id@@ - @@systemName@@')->addDecorator(
            new AP_UI_Decorator_Pattern()
        );
        
        // Настройка Валидаторов
        $this->validate_select = T3Db::api()->select()->from("users_company_buyer", "id");
        $this->validate_main = 'users_company_buyer.id';    
    }
}