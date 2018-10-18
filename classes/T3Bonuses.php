<?php


class T3Bonuses {

  protected static $_instance = null;
  protected $database;
  protected $allBonusesCount;

  protected function __construct(){
    $this->database = T3Db::apiReplicant();
  }

  /**
   * @return T3Bonuses
   */
  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }


  public function getAllBonusesCount(){
    if($this->allBonusesCount === null)
      $this->allBonusesCount = $this->database->fetchOne('select count(*) from webmasters_bonuses');
    return $this->allBonusesCount;
  }
  
  public function selectByParams($params, $limit, $onlyForCount){

    $select = $this->database->select();


    if($onlyForCount)
      $select->from('webmasters_bonuses', array('c' => 'count(*)'));
    else
      $select->from('webmasters_bonuses');


    $select->order("webmasters_bonuses.{$params['order']}");
    if($limit)
      $select->limit($params['page_size'], ($params['_page']-1)*$params['page_size']);


    if($params['min_bound'])
      $select->where("date(webmasters_bonuses.action_datetime)>=date(?)", $params['start_date_datetime']);
    if($params['max_bound'])
      $select->where("date(webmasters_bonuses.action_datetime)<=date(?)", $params['end_date_datetime']);
    if(!empty($params['webmasterID'])){
      if(!is_array($params['webmasterID'])){
        $select->where("webmaster_id = ?", $params['webmasterID']);
      }else{
        if($params['webmasterIDAction'] == 'include'){
          $select->where("webmaster_id in (?)", $params['webmasterID']);
        }else{
          $select->where("webmaster_id not in (?)", $params['webmasterID']);
        }
      }
    }
    if(!empty($params['channelType']))
      $select->where("webmasters_bonuses.lead_get_method = ?", $params['channelType']);
    if(!empty($params['product']))
      $select->where("webmasters_bonuses.lead_product = ?", $params['product']);


    if($params['isAdmin']){

    }else if($params['isAgent']){
      if($onlyForCount){
        $select->joinLeft('users_company_webmaster', "webmasters_bonuses.webmaster_id=users_company_webmaster.id");   
      }
      $select->where("users_company_webmaster.agentID = ?", T3Users::getInstance()->getCurrentUserId());
    }else if($params['isWebmaster']){
      $select->where("webmasters_bonuses.webmaster_id = ?", T3Users::getInstance()->getCurrentUser()->company_id);
    }


    if(!$onlyForCount)
      $select
        ->joinLeft('leads_type', "webmasters_bonuses.lead_product=leads_type.name",
          array('lead_product_title' => 'title'))
        ->joinLeft('users_company_webmaster', "webmasters_bonuses.webmaster_id=users_company_webmaster.id",
          array('webmaster_systemName' => 'systemName', 'webmaster_agent_id' => 'agentID'))
        ->joinLeft('channels', "webmasters_bonuses.lead_channel_id=channels.id",
          array('lead_channel_title' => 'title'))
        ->joinLeft('leads_data', "webmasters_bonuses.lead_id=leads_data.id",
          array('lead_num' => 'num'));

    return $select;
    
  }

  public function getBonusesCount($params){
    $select = $this->selectByParams($params, false, true);
    $result = $this->database->query($select)->fetchAll();
    return $result[0]['c'];
  }

  public function getBonuses($params){
    $select = $this->selectByParams($params, true, false);
    return $this->database->query($select)->fetchAll();
  }
}

T3Bonuses::getInstance();