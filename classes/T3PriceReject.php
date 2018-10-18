<?php

class T3PriceReject {

    static public function addRecord($channel_id,$buyer_id,$lead_id,$price){

        $data = array(
            'channel_id'=>$channel_id,
            'buyer_id'=>$buyer_id,
            'datetime'=>date("Y-m-d H:i:s"),
            'lead_id'=>$lead_id,
            'price'=>$price,
        );
        try{
            T3Db::api()->insert('price_reject',$data);
        }
        catch(Exception $e){}
    }


}
