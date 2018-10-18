<?php

class T3AutoPingPost {  
    static protected $prices;
    static protected $status;

    static private $products = array('auto_loan_pingpost', 'auto_insurance_pingpost', 'commercial_insurance', 'solar_pingpost');

    static public function isPingPostProduct($product) {
        return in_array($product, self::$products);
    }

    static public function addPrice($lead_id, $channel_id, $total_price, $wm_price, $data) {
        try {
            $key = md5($lead_id.$channel_id . microtime(1) . rand(10000, 99999));
            
            $row = array(
                'lead_id'       =>  $lead_id,
                'channel_id'    =>  $channel_id,
                'total_price'   =>  $total_price,
                'wm_price'      =>  $wm_price,
                'key'           =>  $key,
                'data'          =>  serialize($data)
            );
            T3Db::api()->insert('pingpost_result', $row);
            
            // Если ошибки не произошло - значит ключ уникальный, добавлем его в хранилище
            self::$prices[] = array($wm_price, $key);
        }
        catch(Exception $e){
            // В таблице есть уникальный индекс по key
            // Если такой key уже есть, то попробывать создать еще раз  
            self::addPrice($lead_id, $channel_id, $total_price, $wm_price, $data);
        }
    }

    static public function getPrices() {
        return self::$prices;
    }
    static public function getPingPostProductList() {
        return "'".implode("','", self::$products)."'";
    }

    static public function getData($key){
        $res = T3Db::api()->fetchRow("
            select 
                pingpost_result.lead_id,
                pingpost_result.data,
                pingpost_relation.channel_id_post as channel_id,
                pingpost_result.is_post
            from 
                pingpost_result,
                pingpost_relation 
            where 
                pingpost_result.key=? and 
                pingpost_result.channel_id=pingpost_relation.channel_id_ping
        ", $key);
        
        if($res !== false && isset($res['data'])){
            self::setConf(unserialize($res['data']));
            unset($res['data']);
        }
        
        return $res;
    }
    
    /**
    * Поменить этот ключ как отработанный
    */
    static public function post($key){
        T3Db::api()->update("pingpost_result", array(
            'is_post' => '1'    
        ), "`key`=" . T3Db::api()->query($key));
    }
    
    
    static protected $conf;
    
    static public function setConf($data){
        self::$conf = $data;   
    }
    
    static public function getConf(){
        return self::$conf;    
    }

    static public function getRelevantChannel($channel_id){
        return T3Db::api()->fetchOne("select channel_id_post from pingpost_relation where channel_id_ping='$channel_id'");
    }

}

?>
