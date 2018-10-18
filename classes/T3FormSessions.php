<?php

class T3FormSessions { 
    public static function RegisterNew($a){
        /*
        try{
            T3Db::api()->insert('form_sessions', array(
                'cdt' => date("Y:m:d H:i:s"),
            ));
            return T3Db::api()->lastInsertId();
        }
        catch(Exception $e){}
        */
        return "0";
        
    }

    public static function AddToLog($a){
        /*
        try{
            T3Db::api()->insert('form_sessions_log', $a);
        }
        catch(Exception $e){}
        */
    }      
}