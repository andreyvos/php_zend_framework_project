<?php

class T3Channels {

    protected static $_instance = null;
    public $system;
    public $database;

    protected function initialize() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();        
    }

    /**
    * @return T3Channels
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }

    public function postChannelChangeStatus($id, $status)
    {
        //die("{$id} - {$status}");
        $this->database->update('channels_post', array('status' => $status), 'id = ' . $this->database->quote($id));
    }
    
    public function postChannelExists($id, $password) {
      $result = $this->database->fetchOne("
        SELECT count(*)
        FROM channels_post
        WHERE id = ? AND password = ?
      ", array($id, $password));
      return (bool)$result;
    }

    public function jsFormExists($userId, $referrer, $product) {

      $url = Zend_Uri::factory($referrer);
      if(!$url->valid())
        return false;

      $domainAndPath2 = parse_url($referrer);
      $domainAndPath2 = $a['host'].$a['path'];
      
      /*
      $domainAndPath = $url->getHost() . $url->getPath();
      if($domainAndPath[strlen($domainAndPath)-1] !== '/')
        $domainAndPath .= '/';
      */
        
      $ar = $this->database->fetchAll('
        SELECT count(*) as c
        FROM channels_js_forms AS cjf
        LEFT JOIN channels AS c
        ON cjf.id = c.id
        WHERE c.company_id = ? AND cjf.url_domain_and_path = ? AND cjf.product = ?
      ', array($userId, $domainAndPath2, $product));

      return $ar[0]['c']!='0';

    }

    public function getChannelsHeaders_Array($params = array(), $order = array()){
      return T3SimpleDbSelect::select('channels', $params, $order)->fetchAll();
    }

    public function getJsChannels_Array($params = array(), $order = array()){
      $select = $this->database->select()->from('channels_js_forms');
      T3SimpleDbSelect::adjustStatic($select, $params, $order);    
      return $this->database->query($select)->fetchAll();
    }

    public function getPostChannelByDocumentationKey($key)
    {
        $result =  $this->database->fetchRow("select * from channels_post where documentation_key=?", $key);
        return $result;
    }

    public function getPostChannels_Array($params = array(), $order = array()){
        
        
        return T3SimpleDbSelect::select('channels_post', $params, $order)->fetchAll();
    }

    public function createPostChannel_Array($params = array())
    {
        $this->database->insert('channels_post', $params);
        $liid = $this->database->lastInsertId();
        return $liid;
    }

    public function deletePostChannel($id)
    {
        $this->database->delete("channels_post", 'id = '.$this->database->quote($id));
    }

    public function editPostChannel($id, $params = array())
    {
        // Именение ещё не готово
    }

    public function getJsChannelsDomains_Array($params = array()){
      $select = $this->database->select()->from('channels_js_forms')->group('url_domain')->order('url_domain');
      T3SimpleDbSelect::adjustStatic($select, $params);
      return $this->database->query($select)->fetchAll();
    }

    public function getJsChannelsDomainsByWebmaster_Array($webmasterId){
      return $this->database->fetchAll("
        SELECT cjf.url, cjf.url_domain, cjf.url_domain_and_path
        FROM channels as c
        LEFT JOIN channels_js_forms as cjf
        ON c.id = cjf.id
        WHERE c.channel_type = 'js_form' and c.company_id = ?
      ", array($webmasterId));     
    }
    
    static public function getChannelHeaderInformation($channelID, $param = null){
        if(is_null($param)){
            return T3System::getConnect()->fetchRow("select * from channels where id=?", $channelID);
        }
        else {
            return T3System::getConnect()->fetchOne("select `{$param}` from channels where id=?", $channelID);
        }  
    }
    
    public function searchChannelsIds($search_id)
    {
      if($search_id != "") {  
          return $this->database->fetchAll("
            SELECT id, title, channel_type
            FROM channels
            WHERE id LIKE '".$search_id."%'
            ORDER BY id ASC LIMIT 50 
          ");
      } else {
          return $this->database->fetchAll("
            SELECT id, title, channel_type
            FROM channels
            ORDER BY id ASC LIMIT 50 
          ", array($search_id));
      }
    }
    
    static public function getChannel($id){
        $type = T3System::getConnect()->fetchOne('select channel_type from channels where id=?', $id);
        
        $channel = null;
        if($type == 'post_channel') $channel = new T3Channel_Post(); 
        else if($type == 'js_form') $channel = new T3Channel_JsForm(); 
        
        if(!is_null($channel)){
            $channel->fromDatabase($id);
            if($channel->id) return $channel;
        }
        
        return null;    
    }
    
    
    protected static $allImages = null;
    static public function getAllImages(){
        if(is_null(self::$allImages)){
            self::$allImages = array();
            
            $picsDir = BASE_DIR . '/T3System/scripts/applicationforms/pic/';
            
            $pics = glob($picsDir . '*.png');
            if(is_array($pics) && count($pics)){
                foreach($pics as $pic){
                    $pic = str_replace($picsDir, '', $pic); 
                    $temp = substr($pic, 0, strlen($pic) - 4);  
                    
                    self::$allImages[$temp] = "/system/applicationforms/pic/{$pic}";       
                }
            }
        }
        
        return self::$allImages;   
    }
    
    static public function addServerPostChannelGetID($webmasterID){
        $webmasterID = (int)$webmasterID;
        
        T3Db::api()->query("LOCK TABLES channels_post_nums WRITE"); 
        $searchNum = T3Db::api()->fetchOne('select current_num from channels_post_nums where webmaster=?', $webmasterID);
        
        if($searchNum === false){
            T3Db::api()->insert('channels_post_nums', array(
                'webmaster'   => $webmasterID,
                'current_num' => '1',
            ));    
        }
        else {
            T3Db::api()->update('channels_post_nums', array(
                'current_num' => new Zend_Db_Expr("current_num+1"),
            ), "webmaster={$webmasterID}");    
        }
        
        
        T3Db::api()->query("UNLOCK TABLES");  
        
        return (int)$searchNum + 1;      
    }
    
    static public function isAccess($id, $encryptionID = false){
        if($encryptionID) $id = IdEncryptor::decode($id);
        
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select();
        
        $select->from("channels", array(
            new Zend_Db_Expr("count(*)")
        ))
        ->where("id=?", $id);
        
        if(T3Users::getCUser()->isRoleWebmaster()){
            $select->where("company_id=?", T3Users::getCUser()->company_id);
        }
        
        return (bool)T3Db::api()->fetchOne($select); 
    }

}

