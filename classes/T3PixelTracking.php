<?php

class T3PixelTracking {

    static public function getUrlById($id)
    {
        $res = T3System::getConnect()->fetchOne("select url from pixel_tracking where id='".$id."'");

        return $res;
    }


    static public function addUpdate($a)
    {
        if (!empty($a['id']))
        {
            $res = T3System::getConnect()->fetchOne("select url from pixel_tracking where id='".$a['id']."'");
            if (!empty($res))
            {
                // update
                T3System::getConnect()->query("update pixel_tracking set url='".addslashes($a['url'])."'");
            }
            else
            {
                // Isert
                T3System::getConnect()->insert("pixel_tracking", $a);
            }
        }
    }

  
}