<?php

class T3BuyerCompanys {
    static protected $buyers = array();
    
    /**
    * Загрузить объект баера (компании)
    * 
    * @param int $id
    * @return T3BuyerCompany
    */
    static public function getCompany($id){
        if(!isset(self::$buyers[$id])){
            self::$buyers[$id] = new T3BuyerCompany();
            self::$buyers[$id]->fromDatabase($id);
        } 
        
        return self::$buyers[$id];   
    }
    
    static public function isBuyer($id){   
        return (bool)T3Db::api()->fetchOne('select count(*) from users_company_buyer where id=?', (int)$id);
    }  
    
    static public function getPostings($buyer, $all = null){
        $result = array();
        
        if(!is_null($all)){
            $result[''] = $all;    
        }
        
        if($buyer){
            $all = T3Db::api()->fetchAll("select id, title, product from buyers_channels where buyer_id=? order by product, title", $buyer);    
            
            if(count($all)){
                foreach($all as $el){
                    $prod = T3Products::getTitle($el['product']);
                    
                    $result["{$prod}"][$el['id']] = "{$el['title']}";
                }   
            }
        }
        
        return $result;
    } 
    
    static public function reindexProducts(){
        $all = T3Db::api()->fetchAll("select buyer_id as cid, product from buyers_channels group by buyer_id, product");  
        
        $buyers = array();
        foreach($all as $el){
            if(!isset($buyers[$el['cid']])) $buyers[$el['cid']] = array();
            $buyers[$el['cid']][] = $el['product'];
        }
        
        varExport(count($all));
        varExport(count($buyers));
            
        T3Db::api()->update("users_company_buyer", array('products' => ""), "1=1");
        foreach($buyers as $cid => $products){
            try{
                T3Db::api()->update("users_company_buyer", array('products' => implode(",", $products)), "id='{$cid}'");
            }
            catch(Exception $e){
                varExport($cid);
                varExport($products);     
            }
        }  
    } 
}