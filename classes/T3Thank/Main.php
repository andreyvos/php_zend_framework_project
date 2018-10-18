<?php

class T3Thank_Main {
    static protected $pages = array();
    static protected $contents = array();
    static protected $templates = array();
    
    /**
    * Получить Tnx по ID
    * 
    * @param int $id
    * @return T3Thank_Page
    */
    static public function getPage($id){
        $id = (int)$id;
        if(!isset(self::$pages[$id])){
            self::$pages[$id] = new T3Thank_Page();
            self::$pages[$id]->fromDatabase($id);
        }   
        return self::$pages[$id];
    }
    
    /**
    * Получить объект типового шаблона
    * 
    * @param int $id
    * @return T3Thank_Content
    */
    static public function getContent($id){
        $id = (int)$id;
        if(!isset(self::$contents[$id])){
            self::$contents[$id] = new T3Thank_Content();
            self::$contents[$id]->fromDatabase($id);
        }   
        return self::$contents[$id];
    }
    
    /**
    * Получить объект основного шаблона
    * 
    * @param int $id
    * @return T3Thank_Template
    */
    static public function getTemplate($id){
        $id = (int)$id;
        if(!isset(self::$templates[$id])){
            self::$templates[$id] = new T3Thank_Template();
            self::$templates[$id]->fromDatabase($id);
        }   
        return self::$templates[$id];
    }
    
    
    /*********************************************************************************/
    
    
    /**
    * Подобрать страницу для лида
    * 
    * @param T3Lead $lead
    * @return T3Thank_Page
    */
    static public function getPageForLead(T3Lead $lead){
        return self::getPage(1);  
    }
    
    /**
    * Подобрать страницу для данных (использует во внешних интеграциях)
    * 
    * @param array $header
    * @param array $body
    * 
    * @return T3Thank_Page
    */
    static public function getPageForData($header, $body = array()){
        return self::getPage(1);  
    }
}