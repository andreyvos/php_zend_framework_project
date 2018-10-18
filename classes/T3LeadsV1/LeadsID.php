<?php

class T3LeadsV1_LeadsID {
    static public function get($id, $link = true){
        if($link){
            return "<a style='color:#007EBB' href='/en/account/lead/v1/index/id/{$id}'>{$id}</a>";     
        }
        else {
            return $id;
        }
    }     
}
