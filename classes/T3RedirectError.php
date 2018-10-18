<?php

class T3RedirectError {
    static public function addItem($hash,$aff_id=0){
        try{
            $data = array(
                'hash' => $hash,
                'aff_id' => $aff_id,
                'date' => date("Y-m-d H:i:s"),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'referrer' => ifset($_SERVER['HTTP_REFERER']),
            );
            
            T3Db::api()->insert('post_redirect_false',$data);
        }
        catch(Exception $e){}   
    }
}