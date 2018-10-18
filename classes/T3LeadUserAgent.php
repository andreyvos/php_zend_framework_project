<?
  class T3LeadUserAgent
  {
    public static function CreateNew($id, $ua)
    {
        T3Db::api()->insert('lead_useragent', array('lead_id'=>$id, 'useragent'=>$ua ));
    }

    public static function getuseragent($id)
    {
        $useragent = T3Db::api()->fetchOne("select useragent from lead_useragent where lead_id=$id");
        if (!$useragent){
            $useragent = T3Db::api()->fetchOne("select useragent from lead_useragent where length(useragent)>0 order by lead_id desc limit " . rand(1,10) . ",1");
        }
        return $useragent;
    }

  }