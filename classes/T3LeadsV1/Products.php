<?php

class T3LeadsV1_Products {
    static protected $products;
    
    static public function getProducts(){
        if(!self::$products){
            self::$products = T3Db::v1()->fetchPairs("select `type`, Title from form_type where visible='1'");      
        } 
        return self::$products;
    }
    
    static public function getTitle($name, $ifNot = 'unknown'){
        self::getProducts();
        return isset(self::$products[$name]) ? self::$products[$name] : $ifNot;
    }        
}