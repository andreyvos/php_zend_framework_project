<?php

class T3PersonalPayday{

    static public function personal_add($personalloan,$feed_id){
        $array = array(
            'personalloan' => $personalloan,
            'feed_id' => $feed_id
        );
        T3DB::api()->insert('personalloan_paydayloan',$array);
    }

    static public function payday_add($personalloan,$paydayloan){
        $array = array(
            'paydayloan' => $paydayloan,
            'acceptclick_date' => date("Y-m-d H:i:s")
        );
        T3DB::api()->update('personalloan_paydayloan',$array,"personalloan=$personalloan");
    }

    static public function payday_rejectadd($personalloan){
        $array = array(
            'rejectclick_date' => date("Y-m-d H:i:s")
        );
        T3DB::api()->update('personalloan_paydayloan',$array,"personalloan=$personalloan");
    }
}