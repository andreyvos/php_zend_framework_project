<?php

class T3WebmasterChannelDocumentations{

    protected static $_instance = null;
    
    public $database;

    protected function  initialize() {
        $this->database = T3Db::api();
    }

    /**
    * @return T3WebmasterChannelDocumentations
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }
    
    static public function isProduct($product){
        return (bool)self::getInstance()->database->fetchOne('select count(*) from server_post_documentations where product=?', $product);    
    }
    
    static public function getProducts(){
        return self::getInstance()->database->fetchAll("SELECT server_post_documentations.id, server_post_documentations.product, leads_type.title, server_post_documentations.show 
        FROM server_post_documentations INNER JOIN leads_type ON (server_post_documentations.product = leads_type.name) ORDER BY leads_type.prioritet desc");  
    }

    static public function deleteMenuItem($id){
        return (bool)self::getInstance()->database->query('delete from server_post_documentations where id=?', $id);    
    }
    
    static public function addMenuItem($name){
        self::getInstance()->database->delete('server_post_documentations', "product=" . self::getInstance()->database->quote($name));
        
        $obj = new T3WebmasterChannelDocumentation();
        $obj->product = $name;
        $obj->show = '0';
        $obj->insertIntoDatabase();
        
        return $obj;    
    }
    
    
}