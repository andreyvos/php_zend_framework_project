<?php

class T3ZpReportMinPrice {

    const ONLY_TIME_FORMAT = 'HH:mm';

    const RETURN_POST_RESULT_STATUS = 'Return';

    public static $postResultStatuses = array(
      'Sold' => array(
        'title' => 'Sold',
      ),
      'PriceConflict' => array(
        'title' => 'Price Conflict',
      ),
      'Error' => array(
        'title' => 'Error',
      ),
      'ConfigError' => array(
        'title' => 'Config Error',
      ),
      'Filtered' => array(
        'title' => 'Filtered',
      ),
      'SendError' => array(
        'title' => 'Send Error',
      ),
      'AnalysisError' => array(
        'title' => 'Analysis Error',
      ),
      'Rejected' => array(
        'title' => 'Rejected',
      ),
      'GlobalReject' => array(
        'title' => 'Global Reject',
      ),
      'Duplicated' => array(
        'title' => 'Duplicated',
      ),
      'Unknown' => array(
        'title' => 'Unknown',
      ),
    );

    protected static $_instance;

    protected function __construct(){
      $this->database = T3Db::api();
    }

    protected function recordExists(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct,
      $minPrice
    ){

    return array();
        /*
      $exists = $this->database->fetchOne('
        select count(*) from buyers_statistics_grouped_min_price
        where
          record_date = ? and
          buyer_id = ? and
          buyer_channel_id = ? and
          lead_webmaster_id = ? and
          lead_webmaster_agent_id = ? and
          post_result_status = ? and
          lead_product = ? and
          min_price = ?
        ',
      array(
        $date,
        $buyerId,
        $buyerChannelId,
        $webmasterId,
        $webmasterAgentId,
        $postResultStatus,
        $leadProduct,
        $minPrice,     
      )) != 0;

      return $exists;
      */

    }

    public function insertIntoGrouped(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct,
      $minPrice,
      $leadStatus,
      $isReturn,
      $earnings
    ){

        /*
      $this->database->insert(
        'buyers_statistics_grouped_min_price',
        array(
          'record_date' => $date,
          'records_count' => 1,
          'leads_count' => $isReturn ? 0 : 1,
          'returns_count' => $isReturn ? 1 : 0,
          'status_sold_count' => !$isReturn && $leadStatus == 'sold' ? 1 : 0,
          'status_reject_count' => !$isReturn && $leadStatus == 'reject' ? 1 : 0,
          'status_error_count' => !$isReturn && $leadStatus == 'error' ? 1 : 0,
          'leads_earnings' => $isReturn ? 0 : $earnings,
          'returns_earnings' => $isReturn ? $earnings : 0,
          'total_earnings' => $earnings,
          'buyer_id' => $buyerId,
          'buyer_channel_id' => $buyerChannelId,
          'lead_webmaster_id' => $webmasterId,
          'lead_webmaster_agent_id' => $webmasterAgentId,
          'post_result_status' => $postResultStatus,
          'lead_product' => $leadProduct,
          'min_price' => $minPrice,
        )
      );
      */

    }

    public function appendToGrouped(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct,
      $minPrice,
      $leadStatus,
      $isReturn,
      $earnings,
      $removeRecord = false
    ){

      $earnings = (double)$earnings;

      if(!$removeRecord)
        $sign = '+';
      else
        $sign = '-';

      $incStr = "records_count = records_count $sign 1";
      $incStr .= ", total_earnings = total_earnings $sign $earnings";
      if($isReturn){
        $incStr .= ", returns_count = returns_count $sign 1";
        $incStr .= ", returns_earnings = returns_earnings $sign $earnings";
      }else{
        $incStr .= ", leads_count = leads_count $sign 1";
        $incStr .= ", leads_earnings = leads_earnings $sign $earnings";
        if($leadStatus == 'sold')
          $incStr .= ", status_sold_count = status_sold_count $sign 1";
        else if($leadStatus == 'reject')
          $incStr .= ", status_reject_count = status_reject_count $sign 1";
        else if($leadStatus == 'error')
          $incStr .= ", status_error_count = status_error_count $sign 1";
      }

      /*
      $this->database->query("
        update buyers_statistics_grouped_min_price set
          $incStr
        where
          record_date = ? and
          buyer_id = ? and
          buyer_channel_id = ? and
          lead_webmaster_id = ? and
          lead_webmaster_agent_id = ? and
          post_result_status = ? and
          lead_product = ? and
          min_price = ?
      ", array(
        $date,
        $buyerId,
        $buyerChannelId,
        $webmasterId,
        $webmasterAgentId,
        $postResultStatus,
        $leadProduct,
        $minPrice,
      ));
      */
    }

    protected function today(&$datetime, &$date){
      if(empty($datetime))
        $datetime = mySqlDateTimeFormat();
      if(empty($date))
        $date = mySqlDateFormat();
    }

    public function recordLead(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $buyerId, $buyerChannelId, $datetime = null, $date = null){

      if(T3Testing_Load::isTest()){
        $buyerId = T3Testing_Load::$testBuyerId;
        $buyerChannelId = T3Testing_Load::$testBuyerChannelId;
      }

      $this->today($datetime, $date);


      // buyers_statistics_grouped_min_price ////////

      $exists = $this->recordExists(
        $date,
        $buyerId,
        $buyerChannelId,
        $lead->affid,
        $lead->agentID,
        $postResult->status,
        $lead->product,
        $lead->minPrice
      );

      if(!$exists){
        $this->insertIntoGrouped(
          $date,
          $buyerId,
          $buyerChannelId,
          $lead->affid,
          $lead->agentID,
          $postResult->status,
          $lead->product,
          $lead->minPrice,
          $lead->status,
          false,
          $lead->ttl
        );
      }else{
        $this->appendToGrouped(
          $date,
          $buyerId,
          $buyerChannelId,
          $lead->affid,
          $lead->agentID,
          $postResult->status,
          $lead->product,
          $lead->minPrice,
          $lead->status,
          false,
          $lead->ttl
        );
      }

    }

 /*   public function recordReturn(T3Lead_Return $return, $buyerId, $buyerChannelId, $datetime = null, $date = null){

      if($return->ttl == 0)
        return;

      $this->today($datetime, $date);

      $item = new T3BuyersStatsItem();
      $item->fromReturn($return, $datetime, $buyerId, $buyerChannelId);
      $item->makeGroupIndexFieldsNotNull();
      $item->insertIntoDatabase();

      // buyers_statistics_grouped ////////

      $exists = $this->recordExists(
        $date,
        $item->buyer_id,
        $item->buyer_channel_id,
        $item->lead_webmaster_id,
        $item->lead_webmaster_agent_id,
        self::RETURN_POST_RESULT_STATUS,
        $item->lead_product
      );

      if(!$exists){
        $this->insertIntoGrouped(
          $date,
          $item->buyer_id,
          $item->buyer_channel_id,
          $item->lead_webmaster_id,
          $item->lead_webmaster_agent_id,
          self::RETURN_POST_RESULT_STATUS,
          $item->lead_product,
          '',
          true,
          $return->ttl
        );
      }else{
        $this->appendToGrouped(
          $date,
          $item->buyer_id,
          $item->buyer_channel_id,
          $item->lead_webmaster_id,
          $item->lead_webmaster_agent_id,
          self::RETURN_POST_RESULT_STATUS,
          $item->lead_product,
          '',
          true,
          $return->ttl
        );
      }

      ///////////////////////////////////////

      return $item;

    }*/

    /** @return T3ZpReportMinPrice */
    public static function getInstance() {
      if (is_null(self::$_instance)) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }

    public function getMinPricesForWebmaster($webmasterId){
      return array();
      /*
      return $this->database->fetchCol('
        SELECT DISTINCT min_price FROM buyers_statistics_grouped_min_price
        WHERE lead_webmaster_id = ? ORDER BY  min_price
      ', array($webmasterId));
      */

    }

    public function fillTestData(){
      /*
      $this->database->delete('buyers_statistics_grouped_min_price');

      $webmastersIds = $this->database->fetchCol('select id from users_company_webmaster');
      $agentsIds = $this->database->fetchCol("select id from users where role = 'webmaster_agent'");
      $buyersIds = $this->database->fetchCol('select id from users_company_buyer');

      $buyersChannelsData = $this->database->fetchAll('select id, buyer_id from buyers_channels');
      $buyersChannels = array();
      foreach($buyersChannelsData as $v){
        if(!isset($buyersChannels[$v['buyer_id']]))
          $buyersChannels[$v['buyer_id']] = array();
        $buyersChannels[$v['buyer_id']][] = $v['id'];
      }
      $products = $this->database->fetchCol('select name from leads_type');

      $getMethods = array('js_form','post_channel');

      $minPrices = array(10, 30, 60);

      $statuses = array_keys(self::$postResultStatuses);

      for($i=100; $i<10100; $i++){
     // for($i=1; $i<=20; $i++){

        $buyerId = $buyersIds[rand(0, min(5, count($buyersIds))-1)];

        if(!isset($buyersChannels[$buyerId]))
          continue;

        $buyerChannelId = $buyersChannels[$buyerId][rand(0, min(3, count($buyersChannels[$buyerId]))-1)];


        $lead = new T3Lead();

        $lead->affid = $webmastersIds[rand(0, min(3, count($webmastersIds))-1)];
        $lead->agentID = $agentsIds[rand(0, min(3, count($agentsIds))-1)];
        $lead->id = $i;
        $lead->product = $products[rand(0, min(3, count($products))-1)];
        $lead->status = $statuses[rand(0, min(5, count($statuses))-1)];
        $lead->get_method = $getMethods[rand(0, count($getMethods)-1)];
        $lead->ttl = rand(1000, 5000) / 100;        

        $lead->setMinPrice($minPrices[rand(0, count($minPrices)-1)]);

        $postResult = new T3BuyerChannel_PostResult();
        $postResult->setId(rand(100,100000000));
        $postResult->status = $statuses[rand(0, min(3, count($statuses))-1)];

        $zd = new Zend_Date();
        $zd->subSecond(rand(1000, 10000000));

        $this->recordLead(
          $lead,
          $postResult,
          $buyerId,
          $buyerChannelId,
          $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
          $zd->toString(MYSQL_DATE_FORMAT_ZEND)
        );


      }
/*
      //for($i=1; $i<5; $i++){
      for($i=10000; $i<10500; $i++){

        $buyerId = $buyersIds[rand(0, count($buyersIds)-1)];

        if(!isset($buyersChannels[$buyerId]))
          continue;

        $buyerChannelId = $buyersChannels[$buyerId][rand(0, count($buyersChannels[$buyerId])-1)];


        $return = new T3Lead_Return();

        $item = new T3BuyersStatsItem();
        $return->affid = $webmastersIds[rand(0, count($webmastersIds)-1)];
        $return->agentID = $agentsIds[rand(0, count($agentsIds)-1)];
        $return->id = $i;
        $return->lead_id = rand(100, 10000);
        $return->product = $products[rand(0, count($products)-1)];
        $return->get_method = $getMethods[rand(0, count($getMethods)-1)];
        $return->ttl = - (rand(1000, 5000) / 100);


        $zd = new Zend_Date();
        $zd->subSecond(rand(1000, 10000000));

        $this->recordReturn(
          $return,
          $buyerId,
          $buyerChannelId,
          $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
          $zd->toString(MYSQL_DATE_FORMAT_ZEND)
        );


      }
      */
    }

}

T3ZpReportMinPrice::getInstance();
