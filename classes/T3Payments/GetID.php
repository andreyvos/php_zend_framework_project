<?php

class T3Payments_GetID {
    public $rolingParserGood = false;
    public $exist = null;
    
    public $wm;
    public $num;
    
    public $parserNum;
    public $parserWm;
    
    function __construct($id, $checkDb = true){
        $this->set($id, $checkDb);         
    }
    
    /**
    * Парсер ID пеймента в формате 000.00000
    * - первая часть ID пеймента, относительно вебмстера
    * - вторая часть ID вебмастера
    * 
    * @param mixed $id
    * @param mixed $checkDb
    */
    public function set($id, $checkDb = true){
        $this->exist = null;
        $this->rolingParserGood = false;
        $this->wm = null;
        $this->num = null;
        $this->parserNum = null;
        $this->parserWm = null;
        
        if(strlen($id)){
            $ar = explode('.', $id);

            if(count($ar) == 2){
                $this->parserNum = (int)$ar[0];
                $this->parserWm  = (int)$ar[1]; 
                
                $currentUser =& T3Users::getInstance()->getCurrentUser();
                
                if($currentUser->isRoleAdmin()){
                    $this->rolingParserGood = true;
                    $this->wm = $this->parserWm;
                    $this->num = $this->parserNum;
                } 
                else if($currentUser->isRoleWebmasterAgent()){
                    if(T3WebmasterCompanys::isWebmaster($this->parserWm, $currentUser->id)){
                        $this->rolingParserGood = true;
                        $this->wm = $this->parserWm;
                        $this->num = $this->parserNum;    
                    }
                }
                else if($currentUser->isRoleWebmaster()){
                    if($this->parserWm == $currentUser->company_id){
                        $this->rolingParserGood = true;
                        $this->wm = $this->parserWm;
                        $this->num = $this->parserNum;   
                    }
                }      
            }
            
            if($checkDb){
                $this->isExist();    
            }
        }     
    }
    
    public function isExist(){
        if($this->rolingParserGood){
            $this->exist = (bool)T3Db::api()->fetchOne(
                'select count(*) from webmasters_payments where webmaster_id=? and successive_id=?', 
                array(
                    $this->wm,
                    $this->num,
                )
            );
        } 
        return $this->exist;   
    }
    
        
}