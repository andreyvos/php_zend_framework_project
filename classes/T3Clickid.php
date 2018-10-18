<?php

class T3Clickid {

    static public function getClickIdByLeadId($id){
        return T3Db::api()->fetchOne("select clickid from leads_visitors where lead_id='".$id."'");
    }



}


