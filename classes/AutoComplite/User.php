<?php

class AutoComplite_User extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_User';
    
    /** @return Users_AutoComplite */
    static public function create($name){
        return new self($name);
    } 
    
    protected function init(){ 
        /*******************   Настройка вывода списка   *********************/
        // настрйока запроса
        $this->list_select = T3Db::api()->select()
        ->from("users", array('id', 'nickname'))

        ->limit(15);
        
        // видимая часть будет производить поиск по этим полям
        $this->search_fields = array(
            'users.id',
            'users.nickname'
        );
        
        // настрйока вывода
        $this->list_value = AP_UI_Var::context('id');   
        $this->list_label = AP_UI_Var::value('@@id@@ - @@nickname@@')->addDecorator(
            new AP_UI_Decorator_Pattern()
        );
        
        // Настройка Валидаторов
        $this->validate_select = T3Db::api()->select()->from("users", "id");
        $this->validate_main = 'users.id';
    }
}