<?php

class AutoComplite_PostingProduct extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_PostingProduct';
    
    /** @return Users_AutoComplite */
    static public function create($name){
        return new self($name);
    } 
    
    protected function init(){ 
        /*******************   Настройка вывода списка   *********************/
        // настрйока запроса
        $list_select = T3Db::api()->select()
        ->from("buyers_channels", array('id', 'title'))
        ->join("users_company_buyer", "users_company_buyer.id = buyers_channels.buyer_id", array(
            'buyerID'       => 'id',
            'buyerTitle'    => 'systemName',
        ));

        $list_select->where('buyers_channels.product=?', 'call');    
        
        $list_select->limit(20);
        
        $this->list_select = $list_select;
        // видимая часть будет производить поиск по этим полям
        $this->search_fields = array(
            'buyers_channels.id', 
            'buyers_channels.title',
            'users_company_buyer.id', 
            'users_company_buyer.systemName',
        );
        
        // настрйока вывода
        $this->list_value = AP_UI_Var::context('id');   
        $this->list_label = AP_UI_Var::value('@@id@@ - @@title@@ :: @@buyerTitle@@')->addDecorator(
            new AP_UI_Decorator_Pattern()
        );
        
        // Настройка Валидаторов
        $this->validate_select = T3Db::api()->select()->from("buyers_channels", "id");
        $this->validate_main = 'buyers_channels.id';    
    }
}
