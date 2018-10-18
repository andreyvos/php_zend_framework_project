<?php

class T3BuyerReturnPings {


  const ambiguous = 'ambiguous';
  const cannot_identify = 'cannot_identify';
  const identified = 'identified';

  protected static $_instance;

  public $lastException;

  protected function __construct(){
    $this->database = T3Db::api();
  }

    /** @return T3BuyerReturnPings */
  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function makeRemoved($ids){

    if(empty($ids))
      return;

    $idsString = dbQuote($this->database, $ids);

    $this->database->query("

      update buyers_returns_pings set removed = 1 where id in ($idsString)

    ");

  }

  public function retrievePing($data){

    try{

      if(empty($data['cid'])
        || empty($data['key'])){
        throw new Exception('Error. Authentication Failed.');
      }

      if(empty($data['email']) && empty($data['ssn']) && empty($data['homephone']))
        throw new Exception('Bad format. None of the fields "email", "ssn", "homephone" is specified.');

      if(!isset($data['reason']))
        $data['reason'] = '';
      if(!isset($data['email']))
        $data['email'] = '';
      if(!isset($data['ssn']))
        $data['ssn'] = '';
      if(!isset($data['homephone']))
        $data['homephone'] = '';

      if(isset($data['lead_datetime'])){
        $data['lead_datetime'] = mySqlDateTimeFormat(strtotime($data['lead_datetime']));
      }else{
        $data['lead_datetime'] = null;
      }

      $data['cid'] = (string)($data['cid']);
      $data['key'] = (string)($data['key']);
      $data['reason'] = (string)($data['reason']);
      $data['email'] = (string)($data['email']);
      $data['ssn'] = (string)($data['ssn']);
      $data['homephone'] = (string)($data['homephone']);



      if($this->database->fetchOne('
        select count(*) from users_company_buyer where
        id = ? and return_ping_auth_key = ?
      ', array($data['cid'], $data['key']))==0)
        throw new Exception('Error. Authentication Failed.');

      $item = new T3BuyerReturnPingItem();

      $item->record_datetime = mySqlDateTimeFormat();
      $item->buyer_id = $data['cid'];
      $item->buyer_channel_id = '';
      $item->authentication_key = $data['key'];
      $item->sent_to_call_center = 0;
      $item->lead_id = 0;
      $item->lead_identified = 0;
      $item->lead_identification_undertaken = 0;
      $item->lead_email = $data['email'];
      $item->lead_homephone = $data['homephone'];
      $item->posted_lead_datetime = $data['lead_datetime'];
      $item->return_reason = $data['reason'];
      $item->has_ambiguity = 0;
      $item->ambiguity_resolved = 0;
      $item->ambiguity_message_sent = 0;
      $item->ambiguity_message_sending_datetime = '';
      $item->ambiguity_message_text = '';

      $item->insertIntoDatabase();

      /*
       * $data['cid']
       * $data['email']
       * $data['ssn']
       * $data['homephone']
       * $data['key']
       * lead date
       */

     }catch(Exception $e){
       $this->lastException = $e;
       return false;
     }

     return true;


  }


  public function removePingsCannotBeIdentified($buyerId){

    $this->database->query('
      update buyers_returns_pings
      set removed = 1
      where buyer_id = ? and lead_identification_undertaken and cannot_be_identified
    ', array($buyerId));

  }


  public function resolveAmbiguity($recordId, $leadId){

    $ping = new T3BuyerReturnPingItem();
    if($ping->fromDatabase($recordId) === false)
      return false;

    if(!in_array($leadId, $ping->ambiguityLeadsIds))
      return false;

    $this->database->query('
      update buyers_returns_pings
      set
        lead_id = ?,
        lead_identified = 1,
        ambiguity_resolved = 1
      where id = ?

    ', array($leadId, $recordId));

    return true;

  }

  public function getRandomAuthenticationKeyForBuyer(){

    return randomString(30);

  }

  public function assignAllEmptyAuthenticationKeysForBuyers(){

    $ar = $this->database->fetchAll('
      select id, return_ping_auth_key from users_company_buyer
    ');

    foreach($ar as $v){
      if(!empty($v['return_ping_auth_key']))
        continue;
      $this->database->query('
        update users_company_buyer set return_ping_auth_key = ? where id = ?
      ', array($this->getRandomAuthenticationKeyForBuyer(), $v['id']));
    }

  }

  public function getList($buyerId, &$identified, &$ambiguous, &$cannotIdentify, &$additionalLeadsData){
  /*<th>Lead Id</th>
  <th>Lead Date/Time</th>
  <th>Posting</th>
  <th>Webmaster</th>
  <th>Return Request Date/Time</th>
  <th>Product</th>
  <th>Email</th>
  <th>Homephone</th>
  <th>SSN</th>*/
    $listsData = $this->database->fetchAll('

      select

        a.id as record_id,
        c.id as lead_id,
        c.datetime as lead_datetime,
        a.buyer_channel_id,
        b.title as buyer_channel_title,
        c.affid as webmaster_id,
        d.systemName as webmaster_systemName,
        a.record_datetime,
        c.product as lead_product,
        e.title as lead_product_title,

        c.data_email as lead_email,
        c.data_ssn as lead_ssn,
        c.data_phone as lead_homephone,

        a.lead_email as posted_lead_email,
        a.lead_ssn as posted_lead_ssn,
        a.lead_homephone as posted_lead_homephone,
        a.posted_lead_datetime,
        a.return_reason,

        a.lead_identified,
        a.ambiguity_resolved,
        a.has_ambiguity,
        a.cannot_be_identified,
        a.ambiguity_leads_ids,
        a.sent_to_call_center,
        a.recieved_from_call_center,
        a.call_center_approved

      from buyers_returns_pings a
      left join buyers_channels b on a.buyer_channel_id=b.id
      left join leads_data c on a.lead_id = c.id
      left join users_company_webmaster d on c.affid = d.id
      left join leads_type e on c.product = e.name
      where

         a.lead_identification_undertaken and
        
        !a.approvement_undertaken and
        !a.removed

      and a.buyer_id = ?

      order by c.datetime

    ', array($buyerId));

    $identified = array();
    $ambiguous = array();
    $cannotIdentify = array();

    $ambiguityLeadsIds = array();

    foreach($listsData as $v){

      if($v['lead_identified'])
        $identified[] = $v;
      else if(!$v['ambiguity_resolved'] && $v['has_ambiguity']){
        $ambiguous[] = $v;
        $ambiguityLeadsIds = array_merge($ambiguityLeadsIds, unserialize($v['ambiguity_leads_ids']));
      }else if($v['cannot_be_identified'])
        $cannotIdentify[] = $v;
      else{
        //////////////
      }

    }

    $additionalLeadsData = array();

    if(!empty($ambiguityLeadsIds)){

      $ambiguityLeadsIdsString = dbQuote($this->database, $ambiguityLeadsIds);

      if(!T3BuyersStats::$useLiteStatisticsTable){

      }
      else{

        $data = $this->database->fetchAll("
          select

            a.id as lead_id,
            a.datetime as lead_datetime,
            a.affid as webmaster_id,
            c.systemName as webmaster_systemName,
            a.product as lead_product,
            d.title as lead_product_title,
            a.data_email as lead_email,
            a.data_ssn as lead_ssn,
            a.data_phone as lead_homephone,
            e.buyer_channel_id,
            f.title as buyer_channel_title

          from leads_data a
          left join users_company_webmaster c on a.affid = c.id
          left join leads_type d on a.product = d.name
          left join buyers_returns_pings_sup_stat_lite e on a.id=e.lead_id and e.buyer_id = ?
          left join buyers_channels f on e.buyer_channel_id = f.id

          where a.id in ($ambiguityLeadsIdsString)

        ", array($buyerId));
        
      }
      
      foreach($data as $v){
        $additionalLeadsData[$v['lead_id']] = $v;
      }

    }

  }

  public function getHeaderList(){

    $data = $this->database->fetchAll('

      select a.buyer_id, c.systemName as buyer_systemName,

      count(*) as lead_count,
      sum(lead_identified) as lead_identified_count,
      sum(!lead_identified && has_ambiguity && !ambiguity_resolved) as has_ambiguity_count,
      sum(!lead_identified && cannot_be_identified) as cannot_be_identified_count

      from buyers_returns_pings a
      left join users_company_buyer c on a.buyer_id=c.id
      where

         a.lead_identification_undertaken and
        
        !a.approvement_undertaken and
        !a.removed

      group by a.buyer_id

      order by c.systemName

    ');

    return $data;

  }

  public function thereAreNotUndertakenPings(){
    return $this->database->fetchOne('select count(*) from buyers_returns_pings where !lead_identification_undertaken') != 0;
  }

  public function FillSupBuyersStatisticsTableFromDatetime($keyFieldName, $keyFieldValue){

  }

  public function truncateAndFillSupBuyersStatisticsTable(){

    $lastMonthZd = new Zend_Date();
    $lastMonthZd->subMonth(1);
    $lastMonthString = $lastMonthZd->toString(MYSQL_DATETIME_FORMAT_ZEND);


    /*$this->database->query("
      truncate table buyers_returns_pings_sup_stat_lite
    ", array($lastMonthString));*/

    $this->database->query("
      delete from buyers_returns_pings_sup_stat_lite where record_datetime < ?
    ", array($lastMonthString));

    $count = $this->database->fetchOne("
      select * from buyers_returns_pings_sup_stat_lite limit 1
    ");

    if(!empty($count)){
      $this->FillSupBuyersStatisticsTableFromDatetime('record_datetime', $lastMonthString);
    }else{
      $maxId = $this->database->fetchOne("select max(id) from buyers_returns_pings_sup_stat_lite");
      $this->FillSupBuyersStatisticsTableFromDatetime('id', $maxId+1);
    }


  }


  public function appendNewRecordsIntoSupBuyersStatisticsTable(){

    $maxId = $this->database->fetchOne("select max(id) from buyers_returns_pings_sup_stat_lite");

    $this->FillSupBuyersStatisticsTableFromDatetime('id', $maxId+1);


  }

  public function identifyPings(){

    $this->appendNewRecordsIntoSupBuyersStatisticsTable();

    $pingsToIdentifyData = $this->database->fetchAll('
      select * from buyers_returns_pings where !lead_identification_undertaken
    ');

    $unidentifiedPingsIds = array();
    $allFoundLeadsPingsData = array();
    foreach($pingsToIdentifyData as $v){

      $conditionIsSet = false;
      if(!empty($v['lead_email'])){
        $emailConditionString = "lead_email = " . $this->database->quote($v['lead_email']);
        $conditionIsSet = true;
      }else{
        $emailConditionString = '1';
      }

      if(!empty($v['lead_ssn'])){
        $ssnConditionString = "lead_ssn = " . $this->database->quote($v['lead_ssn']);
        $conditionIsSet = true;
      }else{
        $ssnConditionString = '1';
      }

      if(!empty($v['lead_homephone'])){
        $phoneConditionString = "lead_homephone = " . $this->database->quote($v['lead_homephone']);
        $conditionIsSet = true;
      }else{
        $phoneConditionString = '1';
      }

      if($conditionIsSet){
        $statItems = $this->database->fetchAll("
          select * from buyers_returns_pings_sup_stat_lite where
          buyer_id = ? && $emailConditionString && $ssnConditionString && $phoneConditionString && post_result_status = 'Sold'
        ", array($v['buyer_id']));
      }else{
        $statItems = array();
      }

      if(empty($statItems)){

        $this->database->query('
          update buyers_returns_pings set
            lead_identified = 0,
            lead_identification_undertaken = 1,
            cannot_be_identified = 1
          where id = ?
        ', array($v['id']));

      }else{

        if(count($statItems)==1){

          $this->database->query('
            update buyers_returns_pings set
              lead_identified = 1,
              lead_identification_undertaken = 1,
              lead_id = ?,
              buyer_channel_id = ?
            where id = ?
          ', array(
            $statItems[0]['lead_id'],
            $statItems[0]['buyer_channel_id'],
            $v['id'],
          ));

        }else{

          $leadsIds = array();
          foreach($statItems as $statItem){
            $leadsIds[] = $statItem['lead_id'];
          }

          $this->database->query("
            update buyers_returns_pings set
              lead_identified = 0,
              lead_identification_undertaken = 1,
              has_ambiguity = 1,
              ambiguity_leads_ids = ?
            where id = ?
          ", array(serialize($leadsIds), $v['id']));

        }

      }      

    }

  }

  public function getRecievedFromCallCenterList(){

    /*$data = $this->database->fetchAll('

      select * from buyers_returns_pings
      where

        recieved_from_call_center and
        approvement_undertaken and
        !sent_to_returns and
        !removed

    ');*/

    $data = $this->database->fetchAll('

      select

        a.id as record_id,
        c.id as lead_id,
        c.datetime as lead_datetime,
        a.buyer_channel_id,
        b.title as buyer_channel_title,
        c.affid as webmaster_id,
        d.systemName as webmaster_systemName,
        a.record_datetime,
        c.product as lead_product,
        e.title as lead_product_title,

        c.data_email as lead_email,
        c.data_ssn as lead_ssn,
        c.data_phone as lead_homephone,

        a.lead_email as posted_lead_email,
        a.lead_ssn as posted_lead_ssn,
        a.lead_homephone as posted_lead_homephone,
        a.posted_lead_datetime,

        a.approved

      from buyers_returns_pings a
      left join buyers_channels b on a.buyer_channel_id = b.id
      left join leads_data c on a.lead_id = c.id
      left join users_company_webmaster d on c.affid = d.id
      left join leads_type e on c.product = e.name

      where

        a.recieved_from_call_center and
        a.approvement_undertaken and
        !a.sent_to_returns and
        !a.removed

      order by c.datetime

    ');


    $result = array(
      'approved' => array(),
      'not_approved' => array(),
    );

    foreach($data as $v){
      if($v['approved'])
        $result['approved'][] = $v;
      else
        $result['not_approved'][] = $v;
    }

    return $result;

  }

  public function makeReturns($recordsIds){

    if(empty($recordsIds))
      return;

    $recordsIdsString = dbQuote($this->database, $recordsIds);

    $data = $this->database->fetchAll("
      select * from buyers_returns_pings where id in ($recordsIdsString)
    ");

    foreach($data as $v){

      try{

        $this->database->beginTransaction();

        $this->makeReturn($v);

        $this->database->query("
          update buyers_returns_pings
          set sent_to_returns = 1, sending_to_returns_datetime = ?
          where id = ?
        ", array(mySqlDateTimeFormat(), $v['id']));

        $this->database->commit();

      }catch(Exception $e){

        $this->database->rollback();

      }

    }

  }

  public function fillTestData(){
    return null;
  }

  public function approveByAdminAndMakeReturns($ids){
    if(empty($ids))
      return;

    $idsString = dbQuote($this->database, $ids);

    $data = $this->database->fetchAll("
      select * from buyers_returns_pings where id in ($idsString)
    ");

    foreach($data as $v){

      try{

        $this->database->beginTransaction();

        $this->makeReturn($v);

        $this->database->query("

          update buyers_returns_pings
          set
            approvement_made_by_admin = 1,
            approvement_undertaken = 1,
            approved = 1,
            sent_to_returns = 1,
            sending_to_returns_datetime = ?
          where id = ?

        ", array(mySqlDateTimeFormat(), $v['id']));

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();      
      }

    }


  }


  /*****************************************************************************/
  /************************* Р¤РЈРќРљР¦Р�РЇ РџР Р�РќРЇРўР�РЇ РћРўР’Р•РўРђ РћРў РљРћР› Р¦Р•РќРўР Рђ *************/

  public function recieveFromCallCenter($data){

    $this->database->query('

      update buyers_returns_pings set

        recieved_from_call_center = 1,
        approvement_undertaken = 1,
        approved = ?

      where id = ?

    ', array((int)($data['status']=='accept'), $data['more_info']));

  }

  /*****************************************************************************/
  /*****************************************************************************/

  public function recieveFromCallCenter2($transactionId, $approved){

    $this->database->query('

      update buyers_returns_pings set

        recieved_from_call_center = 1,
        call_center_approved = ?

      where call_center_transaction_id = ?

    ', array((int)$approved, $transactionId));

  }




  public function sendToCallCenter($buyerId, $ids){

    if(!empty($ids)){

      $idsString = dbQuote($this->database, $ids);      

      $data = $this->database->fetchAll("
        select * from buyers_returns_pings where buyer_id = ? and lead_identified and id in ($idsString)
      ", array($buyerId));

     /* $callCenter = new T3CallCenter_Management();

      foreach($data as $v){
        $callCenter->InsertNewLead($v['lead_id'], "return", $v["return_reason"], $v["id"]);
      }*/

      /**************************************************************************/
      /************************** РџРћРЎР«Р›РљРђ Р’ РљРћР› Р¦Р•РќРўР  ***************************/

      $callCenter = new T3CallCenter2();
      $sessionsIDs = array();
       foreach($data as $v){
           
	   $lead = new T3Lead();
	   $lead->fromDatabase($v['lead_id']);
	   $info = $callCenter->getInfoFromLead($lead);
	   $info['return_reason']=$v['return_reason'];
	   $template = $callCenter->getTemplate($lead);
          $sessionsIDs[$v['id']] =$callCenter->sendLeadToVerification($info, $template);
      }

      /**************************************************************************/
      /**************************************************************************/

      foreach($sessionsIDs as $id => $transactionId){
        $this->database->query("
          update buyers_returns_pings set
            call_center_transaction_id = ?
          where
            id = ?
        ", array($transactionId, $id));        
      }

      $this->database->query("
        update buyers_returns_pings set

          sent_to_call_center = 1,
          sending_to_call_center_datetime = ?

        where buyer_id = ? and lead_identified and id in ($idsString)

      ", array(mySqlDateTimeFormat(), $buyerId));

    }

  }

  public function recordLog($data, $statusTrue, $exceptionMessage = null){

    $this->database->query('
      insert into buyers_returns_pings_log set
      record_datetime = ?, data_php = ?, status_true = ?, exception_message = ?
    ', array(mySqlDateTimeFormat(), var_export($data, true), $statusTrue, $exceptionMessage));

  }

  public function thereIsReturnAlready($leadId, $channelId, $buyerId){

    if(
      $this->database->fetchOne('select count(*)>0 from leads_returns where lead_id = ? && buyer = ?', array($leadId, $buyerId))
      || $this->database->fetchOne('select count(*)>0 from leads_returns where lead_id = ? && posting = ?', array($leadId, $channelId))
    ){
      
      return true;
      
    }

    return false;

  }

  public function makeCertainLeadReturn($leadId, $buyerId, $returnReason = ''){

    return $this->makeReturn(array(
      'lead_id' => $leadId,
      'buyer_id' => $buyerId,
      "return_reason" => $returnReason,
    ));

  }

  /*
   *
   * $pingData = array(
   *   'lead_id' => ...
   *   'buyer_id' => ...
   *   'return_reason' => ...
   * );
   *
   */
  public function makeReturn($pingData){

    $result = array();

    /************************************************************************/
    $invoiceRecordId = $this->database->fetchOne('
      select id from buyers_leads_sellings where
      lead_id = ? and buyer_id = ?
    ', array($pingData['lead_id'], $pingData['buyer_id']));

    $leadRow = $this->database->fetchRow('
      select * from leads_data where id = ?
    ', array($pingData['lead_id']));

    $return = array(
      "v2_record_type" => "sellings",
      "v2_record_id" => $invoiceRecordId,
      "reason" => $pingData["return_reason"],
      "ttl" =>  - $leadRow['ttl'],
      "wm" =>   - $leadRow['wm'],
      "agn" =>  - $leadRow['agn'],
      "ref" =>  - $leadRow['ref'],
    );
    /************************************************************************/



    $v2_record_type = $return["v2_record_type"];
    $v2_record_id = $return["v2_record_id"];
    $reason = $return["reason"];
    $ttl = round((float)$return['ttl'], 2);
    $wm  = round((float)$return['wm'], 2);
    $agn = round((float)$return['agn'], 2);
    $ref = round((float)$return['ref'], 2);

    if($v2_record_type=='sellings')
      $table = "buyers_leads_sellings";
    else if($v2_record_type=='movements')
      $table = "buyers_leads_movements";
    else
      throw new Exception("v2_record_type unknown");

    $invoiceItem = $this->database->fetchRow("
      select
        a.*,
        leads_data.get_method as lead_get_method,
        leads_data.datetime as lead_datetime,
        leads_data.status as lead_status,
        leads_data.channel_id as webmaster_channel_id,
        leads_data.subacc as webmaster_subacc,
        leads_data.affid as webmaster_id,
        leads_data.refaffid as referral_id,
        leads_data.agentID as agent_id
      from $table a
      left join leads_data on leads_data.id=a.lead_id
      where a.id = ?
    ", array($v2_record_id));

    if(empty($invoiceItem))
      throw new Exception("record does not exist in database");

    $returnObject = new T3Lead_Return();

    $returnObject->setParams(array(
        'user_id'           => T3Users::getCUser()->id,
        'user_ip_address'   => $_SERVER['REMOTE_ADDR'],

      'wm_show'           => (int)($wm != 0),
      'lead_id'           => $invoiceItem['lead_id'],
      'movement_id'       => '0',
      'from_v1'           => '0',

      'product'           => $invoiceItem['lead_product'],
      'get_method'        => T3Synh_Functions::getPostMethod($invoiceItem['lead_get_method']),
      'channel_id'        => $invoiceItem['webmaster_channel_id'],
      'subacc'            => $invoiceItem['webmaster_subacc'],

      'invoiceItemType'   => $v2_record_type,
      'invoiceItemID'     => $invoiceItem['id'],
      'buyer'             => $invoiceItem['buyer_id'],
      'posting'           => $invoiceItem['channel_id'],

      'affid'             => $invoiceItem['webmaster_id'],
      'refaffid'          => $invoiceItem['referral_id'],
      'agentID'           => $invoiceItem['agent_id'],

      'wm'                => $wm,
      'ref'               => $ref,
      'agn'               => $agn,
      'ttl'               => $ttl,

      'lead_datetime'     => $invoiceItem['lead_datetime'],
      'return_datetime'   => date("Y-m-d H:i:S"),

      'data_email'        => $invoiceItem['lead_email'],
      'data_phone'        => $invoiceItem['lead_home_phone'],
      'data_state'        => $invoiceItem['lead_status'],

      'comment'           => $reason,
    ));

    $returnObject->insertIntoDatabase();



    T3Report_Summary::addNewReturn($returnObject);


    /******* Р—Р°РїРёСЃСЊ РІ СЃРёСЃС‚РµРјСѓ РїРµР№РјРµРЅС‚РѕРІ *********/
    $this->database->insert("webmasters_leads_movements", array(
      'action_type'           => 'reject',
      'lead_id'               => $v2_record_id,
      'channel_id'            => $invoiceItem['webmaster_channel_id'],
      'subaccount_id'         => $invoiceItem['webmaster_subacc'],
      'webmaster_id'          => $invoiceItem['webmaster_id'],
      'getting_log_record_id' => '0',
      'action_datetime'       => date("Y-m-d H:i:s"),
      'action_sum'            => $wm,
      'lead_email'            => $invoiceItem['lead_email'],
      'lead_home_phone'       => $invoiceItem['lead_home_phone'],
      'lead_product'          => $invoiceItem['lead_product'],
      'from_old_system'       => '0',
    ));
    /************************************************/

    /******* Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° РІРµР±РјР°СЃС‚РµСЂР° *********/
    $webmaster = new T3WebmasterCompany();
    $webmaster->fromDatabase($invoiceItem['webmaster_id']);
    $webmaster->updateBalance($wm);
    /******************************************/



    ///// Р—Р°РїРёСЃСЊ РІ СЃС‚Р°С‚РёСЃС‚РёРєСѓ Р±Р°Р№РµСЂРѕРІ ////////
    T3BuyersStats::getInstance()->recordReturn($returnObject, $returnObject->buyer, $returnObject->posting);
    //////////////////////////////////////////


    /******* Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° Р±Р°Р№РµСЂР° *********/
    $this->database->query("
      update users_company_buyer set
      balance = balance + ?
      where id = ?
    ", array($invoiceItem['action_sum']/*$ttl*/, $invoiceItem['buyer_id']));
    /******************************************/


    // 4. Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° СЂРµС„РµСЂР°Р»Р°
    if(!empty($invoiceItem['referral_id']) && $ref != 0){
      $this->database->query("
        update users_company_webmaster
        set balance = balance + ?
        where id = ?
      ", array($ref, $invoiceItem['referral_id']));
    }


    // 5. Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° Р°РіРµРЅС‚Р°
    if(!empty($invoiceItem['agent_id']) && $agn != 0){
      $this->database->query("
        update users_webmaster_agents
        set balance = balance + ?
        where id = ?
      ", array($agn, $invoiceItem['agent_id']));
    }


    // 6. Р�Р·РјРµРЅРµРЅРёРµ СЃСѓРјРј РІ Р»РёРґР°С…
    $this->database->query("
      update leads_data set
        wm = wm + ?,
        ref = ref + ?,
        ttl = ttl + ?,
        agn = agn + ?
      where id = ?
    ", array($wm, $ref, $ttl, $agn, $invoiceItem['lead_id']));



    // 7. Р—Р°РїРёСЃСЊ РІ buyers_leads_movements

      $createDatetime = date("Y-m-d H:i:s");

    $this->database->insert("buyers_leads_movements", array(
      'action_type' => 'reject',
      'lead_id' => $invoiceItem['lead_id'],
      'channel_id' => $invoiceItem['channel_id'],
      'buyer_id' => $invoiceItem['buyer_id'],
      'invoice_id' => null,
      'posting_log_record_id' => null,
      'action_datetime' => $createDatetime,
      'channel_action_datetime' => $createDatetime,
      'action_sum' => -$invoiceItem['action_sum'],//+$ttl,
      'lead_email' => $invoiceItem['lead_email'],
      'lead_home_phone' => $invoiceItem['lead_home_phone'],
      'lead_product' => $invoiceItem['lead_product'],
      'is_v1_lead' => '0',
      'syncId' => null,
    ));

      if($invoiceItem['action_sum'] != 0){
          /*
          Logobaza_Main::buyersLeadsMovements()->add(array(
              'mid'               => $this->database->lastInsertId(),     // movement id
              'lead_id'           => $invoiceItem['lead_id'],             // id лида
              'channel_id'        => $invoiceItem['channel_id'],          // id канала, который купил лид
              'buyer_id'          => $invoiceItem['buyer_id'],            // id покупателя, которому принадлежит канал, который купил лид
              'action_sum'        => -$invoiceItem['action_sum'],         // сумма, которую нам должен за него баер
          ), $createDatetime);
          */
      }
    

    $result['ttl'] = $ttl;
    $result['wm'] = $wm;
    $result['agn'] = $agn;
    $result['ref'] = $ref;

    return $result;

  }
  
  public function makeBuyerSideOnlyReturn($buyerId, $leadId, $buyerSum){
    
    $result = array();


    $invoiceRecordId = $this->database->fetchOne('
      select id from buyers_leads_sellings where
      lead_id = ? and buyer_id = ?
    ', array($leadId, $buyerId));
    
    $v2_record_type = 'sellings';
    $v2_record_id = $invoiceRecordId;
    $reason = '';
    
    $ttl = 0;
    $wm  = 0;
    $agn = 0;
    $ref = 0;

    $table = "buyers_leads_movements";

    $invoiceItem = $this->database->fetchRow("
      select
        a.*,
        leads_data.get_method as lead_get_method,
        leads_data.datetime as lead_datetime,
        leads_data.status as lead_status,
        leads_data.channel_id as webmaster_channel_id,
        leads_data.subacc as webmaster_subacc,
        leads_data.affid as webmaster_id,
        leads_data.refaffid as referral_id,
        leads_data.agentID as agent_id
      from $table a
      left join leads_data on leads_data.id=a.lead_id
      where a.id = ?
    ", array($v2_record_id));
    
    $invoiceItem['action_sum'] = $buyerSum;

    if(empty($invoiceItem))
      throw new Exception("record does not exist in database");

    $returnObject = new T3Lead_Return();

    $returnObject->setParams(array(
        'user_id'           => T3Users::getCUser()->id,
        'user_ip_address'   => $_SERVER['REMOTE_ADDR'],

      'wm_show'           => (int)($wm != 0),
      'lead_id'           => $invoiceItem['lead_id'],
      'movement_id'       => '0',
      'from_v1'           => '0',

      'product'           => $invoiceItem['lead_product'],
      'get_method'        => T3Synh_Functions::getPostMethod($invoiceItem['lead_get_method']),
      'channel_id'        => $invoiceItem['webmaster_channel_id'],
      'subacc'            => $invoiceItem['webmaster_subacc'],

      'invoiceItemType'   => $v2_record_type,
      'invoiceItemID'     => $invoiceItem['id'],
      'buyer'             => $invoiceItem['buyer_id'],
      'posting'           => $invoiceItem['channel_id'],

      'affid'             => $invoiceItem['webmaster_id'],
      'refaffid'          => $invoiceItem['referral_id'],
      'agentID'           => $invoiceItem['agent_id'],

      'wm'                => $wm,
      'ref'               => $ref,
      'agn'               => $agn,
      'ttl'               => $ttl,

      'lead_datetime'     => $invoiceItem['lead_datetime'],
      'return_datetime'   => date("Y-m-d H:i:S"),

      'data_email'        => $invoiceItem['lead_email'],
      'data_phone'        => $invoiceItem['lead_home_phone'],
      'data_state'        => $invoiceItem['lead_status'],

      'comment'           => $reason,
    ));

    $returnObject->insertIntoDatabase();

    T3Report_Summary::addNewReturn($returnObject);

    ///////////
    T3BuyersStats::getInstance()->recordReturn($returnObject, $returnObject->buyer, $returnObject->posting);
    //////////

    /****************/
    $this->database->query("
      update users_company_buyer set
      balance = balance + ?
      where id = ?
    ", array($invoiceItem['action_sum']/*$ttl*/, $invoiceItem['buyer_id']));
    /**********************/


    // 4. Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° СЂРµС„РµСЂР°Р»Р°
    /*if(!empty($invoiceItem['referral_id']) && $ref != 0){
      $this->database->query("
        update users_company_webmaster
        set balance = balance + ?
        where id = ?
      ", array($ref, $invoiceItem['referral_id']));
    }*/


    // 5. Р�Р·РјРµРЅРµРЅРёРµ Р±Р°Р»Р°РЅСЃР° Р°РіРµРЅС‚Р°
    /*if(!empty($invoiceItem['agent_id']) && $agn != 0){
      $this->database->query("
        update users_webmaster_agents
        set balance = balance + ?
        where id = ?
      ", array($agn, $invoiceItem['agent_id']));
    }*/


    // 6. Р�Р·РјРµРЅРµРЅРёРµ СЃСѓРјРј РІ Р»РёРґР°С…
    /*$this->database->query("
      update leads_data set
        wm = wm + ?,
        ref = ref + ?,
        ttl = ttl + ?,
        agn = agn + ?
      where id = ?
    ", array($wm, $ref, $ttl, $agn, $invoiceItem['lead_id']));*/


    

    // 7. Р—Р°РїРёСЃСЊ РІ buyers_leads_movements
      $createDatetime = date("Y-m-d H:i:s");

      $this->database->insert("buyers_leads_movements", array(
          'action_type' => 'reject',
          'lead_id' => $invoiceItem['lead_id'],
          'channel_id' => $invoiceItem['channel_id'],
          'buyer_id' => $invoiceItem['buyer_id'],
          'invoice_id' => null,
          'posting_log_record_id' => null,
          'action_datetime' => $createDatetime,
          'channel_action_datetime' => $createDatetime,
          'action_sum' => -$invoiceItem['action_sum'],//+$ttl,
          'lead_email' => $invoiceItem['lead_email'],
          'lead_home_phone' => $invoiceItem['lead_home_phone'],
          'lead_product' => $invoiceItem['lead_product'],
          'is_v1_lead' => '0',
          'syncId' => null,
      ));

      if($invoiceItem['action_sum'] != 0){
          /*
          Logobaza_Main::buyersLeadsMovements()->add(array(
              'mid'               => $this->database->lastInsertId(),     // movement id
              'lead_id'           => $invoiceItem['lead_id'],             // id лида
              'channel_id'        => $invoiceItem['channel_id'],          // id канала, который купил лид
              'buyer_id'          => $invoiceItem['buyer_id'],            // id покупателя, которому принадлежит канал, который купил лид
              'action_sum'        => -$invoiceItem['action_sum'],         // сумма, которую нам должен за него баер
          ), $createDatetime);
          */
      }

    $result['ttl'] = $ttl;
    $result['wm'] = $wm;
    $result['agn'] = $agn;
    $result['ref'] = $ref;

    return $result;    
    
  }


}

T3BuyerReturnPings::getInstance();
