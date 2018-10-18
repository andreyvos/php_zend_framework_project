<?php

class AutoComplite_City extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_City';
    
    static public function create($name){
        return new self($name);
    } 
    
    protected function init(){ 
        /*******************   Настройка вывода списка   *********************/
        $this->list_select = T3Db::api()->select()
                                        ->distinct()
                                        ->from("geo_us_zips", array('CityMixedCase'))
                                        ->limit(30);
        $this->search_fields = array(
           'geo_us_zips.CityMixedCase',
        );
        
        $this->list_value = AP_UI_Var::context('CityMixedCase');   
        $this->list_label = AP_UI_Var::value('@@CityMixedCase@@')->addDecorator(
            new AP_UI_Decorator_Pattern()
        );
                // Настройка Валидаторов
        $this->validate_select = T3Db::api()->select()->from("geo_us_zips", "CityMixedCase");
        $this->validate_main = 'geo_us_zips.CityMixedCase';  
         
    }
}