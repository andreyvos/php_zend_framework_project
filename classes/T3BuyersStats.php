<?php

class T3BuyersStats {

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

    public static $useLiteStatisticsTable = true;//false;
    public static $turnOffWritingToPldTables = true;//false;

    public static $summaryAvailableConditionFields = array(
      'buyer_id',
      'lead_webmaster_id',
      'lead_webmaster_agent_id',
      'buyer_channel_id',
      'lead_product',
    );

    public static $detailedInfoAvailableConditionFields = array(
      'buyer_id' => 'buyers_statistics.buyer_id',
      'post_result_status' => 'buyers_statistics.post_result_status' ,
      'lead_webmaster_id' => 'buyers_statistics.lead_webmaster_id',
      'lead_webmaster_agent_id' => 'buyers_statistics.lead_webmaster_agent_id',
      'buyer_channel_id' => 'buyers_statistics.buyer_channel_id',
      'lead_product' => 'buyers_statistics.lead_product',
      'lead_state' => 'leads_data.data_state',
    );

    public static $detailedInfoAvailableConditionFields_Lite = array(
      'buyer_id' => 'buyers_statistics_lite.buyer_id',
      'post_result_status' => 'buyers_statistics_lite.post_result_status' ,
      'lead_webmaster_id' => 'buyers_statistics_lite.lead_webmaster_id',
      'lead_webmaster_agent_id' => 'buyers_statistics_lite.lead_webmaster_agent_id',
      'buyer_channel_id' => 'buyers_statistics_lite.buyer_channel_id',
      'lead_product' => 'buyers_statistics_lite.lead_product',
      'lead_state' => 'leads_data.data_state',
    );

    public static $returnsAvailableConditionFields = array(
      'buyer_id' => 'leads_returns.buyer',
      'lead_webmaster_id' => 'leads_returns.affid',
      'lead_webmaster_agent_id' => 'leads_returns.agentID',
      'buyer_channel_id' => 'leads_returns.posting',
      'lead_product' => 'leads_returns.product',
      'lead_state' => 'leads_data.data_state',
    );

    public static $returnsAvailableConditionFields_Lite = array(
      'buyer_id' => 'buyers_statistics_lite_returns.buyer_id',
      'lead_webmaster_id' => 'buyers_statistics_lite_returns.lead_webmaster_id',
      'lead_webmaster_agent_id' => 'buyers_statistics_lite_returns.lead_webmaster_id',
      'buyer_channel_id' => 'buyers_statistics_lite_returns.buyer_channel_id',
      'lead_product' => 'buyers_statistics_lite_returns.lead_product',
      'lead_state' => 'buyers_statistics_lite_returns.lead_state',
    );

    public static $getTypesTitles = array(
      'js_form' => 'Form',
      'post_channel' => 'Post Channel',
      '' => '',
    );

    protected static $_instance;


    public $enqueueLeadsMode = false;
    public $leadsQueue = array();

    protected function __construct(){
      $this->database = T3Db::api();
      T3Invoices::$periods = array_keys(T3Invoices::$periodsData);
      T3Invoices::$weekDays = array_keys(T3Invoices::$weekDaysData);
    }

    protected function recordExists(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct
    ){

      $leadProductId = T3Products::getID($leadProduct);

      $exists = $this->database->fetchOne(
        'select count(*) from buyers_statistics_grouped ' .
        'where ' .
          'record_date = ? and ' .
          'buyer_id = ? and ' .
          'buyer_channel_id = ? and ' .
          'lead_webmaster_id = ? and ' .
          'lead_webmaster_agent_id = ? and ' .
          'post_result_status = ? and ' .
          'lead_product_id = ? ',
      array(
        $date,
        $buyerId,
        $buyerChannelId,
        $webmasterId,
        $webmasterAgentId,
        $postResultStatus,
        $leadProductId //$leadProduct,
      )) != 0;

      return $exists;

    }

    public function insertIntoGrouped(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct,
      $leadStatus,
      $isReturn,
      $earnings
    ){

      $leadProductId = T3Products::getID($leadProduct);

      $this->database->insert(
        'buyers_statistics_grouped',
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
          'lead_product_id' => $leadProductId,
        )
      );

    }

    public function appendToGrouped(
      $date,
      $buyerId,
      $buyerChannelId,
      $webmasterId,
      $webmasterAgentId,
      $postResultStatus,
      $leadProduct,
      $leadStatus,
      $isReturn,
      $earnings,
      $removeRecord = false
    ){

      $leadProductId = T3Products::getID($leadProduct);

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

      $this->database->query(
        "update buyers_statistics_grouped set " .
          "$incStr " .
        "where " .
          "record_date = ? and " .
          "buyer_id = ? and " .
          "buyer_channel_id = ? and " .
          "lead_webmaster_id = ? and " .
          "lead_webmaster_agent_id = ? and " .
          "post_result_status = ? and " .
          "lead_product_id = ?", 
        array(
            $date,
            $buyerId,
            $buyerChannelId,
            $webmasterId,
            $webmasterAgentId,
            $postResultStatus,
            $leadProductId, //$leadProduct,
      ));

    }

    protected function today(&$datetime, &$date){
      if(empty($datetime))
        $datetime = mySqlDateTimeFormat();
      if(empty($date))
        $date = mySqlDateFormat();
    }

    function curl_post_async($url, $params){
        foreach ($params as $key => &$val) {
          if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key.'='.urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts=parse_url($url);

        $fp = fsockopen($parts['host'],
            isset($parts['port'])?$parts['port']:80,
            $errno, $errstr, 30);

        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);
    }

    public function recordOrEnqueueLead(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $buyerId, $buyerChannelId, $datetime = null, $date = null){

      try{
        if($this->enqueueLeadsMode){
          $this->enqueueLead($lead, $postResult, $buyerId, $buyerChannelId, $datetime, $date);
        }else{
          $this->recordLead($lead, $postResult, $buyerId, $buyerChannelId, $datetime, $date);
        }
      }catch(Exception $e){
        /*$this->database->query('insert into buyers_stats_record_lead_async
          set record_datetime = ? , params = ?', array(mySqlDateTimeFormat(),$e->getMessage() ));*/
      }


    }

    public function enqueueLead(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $buyerId, $buyerChannelId, $datetime = null, $date = null){
      $this->today($datetime, $date);
      $this->leadsQueue[] = array(
        'lead' => $lead,
        'postResult' => $postResult,
        'buyerId' => $buyerId,
        'buyerChannelId' => $buyerChannelId,
        'datetime' => $datetime,
        'date' => $date,
      );
    }

    public function commitLeadsQueue(){

      if(!$this->enqueueLeadsMode)
        return;

      $itemsLiteData = array();
      $itemsData = array();

      foreach($this->leadsQueue as $v){
        
        $lead = $v['lead'];
        $postResult = $v['postResult'];
        $buyerId = $v['buyerId'];
        $buyerChannelId = $v['buyerChannelId'];
        $datetime = $v['datetime'];
        $date = $v['date'];

        $item = $this->getItemForLeadRecording($lead, $postResult, $buyerId, $buyerChannelId, $datetime, $date);

        if(empty($item->error_description)){

          $itemData = array(
            'record_datetime' => $item->record_datetime,          //  datetime
            'buyer_id' => $item->buyer_id,                //  int(11)
            'buyer_channel_id' => $item->buyer_channel_id,         //  int(11)
            'lead_webmaster_id' => $item->lead_webmaster_id,        //  int(11)
            'lead_webmaster_agent_id' => $item->lead_webmaster_agent_id,   //  int(11)
            'lead_id' => $item->lead_id,                  //  int(11)
            'lead_product' => $item->lead_product,              //  varchar(255)
            'lead_status' => $item->lead_status,              //  varchar(255)
            'lead_get_method' => $item->lead_get_method,          //  varchar(255)
            'lead_is_from_v1' => $item->lead_is_from_v1,          //  tinyint(1)
            'lead_email' => $item->lead_email,
            'lead_ip' => $item->lead_ip,
            'lead_state' => $item->lead_state,
            'lead_homephone' => $item->lead_homephone,
            'post_result_id' => $item->post_result_id,
            'post_result_status' => $item->post_result_status,       //  varchar(255)
            'is_return' => $item->is_return,                //  tinyint(1)
            'return_id' => $item->return_id,                 //  int(11)
            'earnings' => $item->earnings,                  //  decimal(10,2)
            'error_description' => $item->error_description,  
          );
          $itemsData[] = $itemData;

          $itemLiteData = array(
            'record_datetime' => $item->record_datetime,
            'buyer_id' => $item->buyer_id,
            'buyer_channel_id' => $item->buyer_channel_id,
            'lead_webmaster_id' => $item->lead_webmaster_id,
            'lead_id' => $item->lead_id,
            'lead_product_id' => T3Products::getID($item->lead_product),
            'post_result_status' => $item->post_result_status,
            'earnings' => $item->earnings,
          );
          $itemsLiteData[] = $itemLiteData;

          $exists = $this->recordExists(
            $date,
            $item->buyer_id,
            $item->buyer_channel_id,
            $item->lead_webmaster_id,
            $item->lead_webmaster_agent_id,
            $postResult->status,
            $item->lead_product
          );

          if(!$exists){
            $this->insertIntoGrouped(
              $date,
              $item->buyer_id,
              $item->buyer_channel_id,
              $item->lead_webmaster_id,
              $item->lead_webmaster_agent_id,
              $postResult->status,
              $item->lead_product,
              $lead->status,
              false,
              $postResult->priceTTL//$lead->ttl
            );
          }else{
            $this->appendToGrouped(
              $date,
              $item->buyer_id,
              $item->buyer_channel_id,
              $item->lead_webmaster_id,
              $item->lead_webmaster_agent_id,
              $postResult->status,
              $item->lead_product,
              $lead->status,
              false,
              $postResult->priceTTL//$lead->ttl
            );
          }

        }else{

          $this->recordLead($lead, $postResult, $buyerId, $buyerChannelId, $datetime, $date);

        }


      }

      if(!empty($itemsLiteData)){
        T3Db::api()->insertMulty(
          'buyers_statistics_lite',
          array(
            'record_datetime',
            'buyer_id',
            'buyer_channel_id',
            'lead_webmaster_id',
            'lead_id',
            'lead_product_id',
            'post_result_status',
            'earnings',
          ),
          $itemsLiteData
        );
      }

      if(!T3BuyersStats::$turnOffWritingToPldTables){
        if(!empty($itemsData)){
          T3Db::api()->insertMulty(
            'buyers_statistics',
            array(
              'record_datetime',          //  datetime
              'buyer_id',                 //  int(11)
              'buyer_channel_id',         //  int(11)
              'lead_webmaster_id',        //  int(11)
              'lead_webmaster_agent_id',  //  int(11)
              'lead_id',                  //  int(11)
              'lead_product',             //  varchar(255)
              'lead_status',              //  varchar(255)
              'lead_get_method',          //  varchar(255)
              'lead_is_from_v1',          //  tinyint(1)
              'lead_email',
              'lead_ip',
              'lead_state',
              'lead_homephone',
              'post_result_id',
              'post_result_status',       //  varchar(255)
              'is_return',                //  tinyint(1)
              'return_id',                //  int(11)
              'earnings',                 //  decimal(10,2)
              'error_description',
            ),
            $itemsData
          );
        }
      }
      

    }

    public function getItemForLeadRecording(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $buyerId, $buyerChannelId, $datetime = null, $date = null){
      $this->today($datetime, $date);      
      $item = new T3BuyersStatsItem();      
      $item->fromNewLead($lead, $postResult, $datetime, $buyerId, $buyerChannelId);
      $item->makeGroupIndexFieldsNotNull();
      return $item;
    }

    public function recordLead(T3Lead $lead, T3BuyerChannel_PostResult $postResult, $buyerId, $buyerChannelId, $datetime = null, $date = null){

      if(T3Testing_Load::isTest()){
        $buyerId = T3Testing_Load::$testBuyerId;
        $buyerChannelId = T3Testing_Load::$testBuyerChannelId;
      }

      $this->today($datetime, $date);
      
      $item = new T3BuyersStatsItem();      
      $item->fromNewLead($lead, $postResult, $datetime, $buyerId, $buyerChannelId);
      $item->makeGroupIndexFieldsNotNull();

      if(!T3BuyersStats::$turnOffWritingToPldTables){
        $item->insertIntoDatabase();
      }

      try{
        $this->database->insert('buyers_statistics_lite', array(
          'record_datetime' => $item->record_datetime,
          'buyer_id' => $item->buyer_id,
          'buyer_channel_id' => $item->buyer_channel_id,
          'lead_webmaster_id' => $item->lead_webmaster_id,
          'lead_id' => $item->lead_id,
          'lead_product_id' => T3Products::getID($item->lead_product),
          'post_result_status' => $item->post_result_status,
          'earnings' => $item->earnings,
        ));

        $itemLiteId = $this->database->lastInsertId();

        if(!empty($item->error_description)){
          $this->database->insert('buyers_statistics_errors', array(
            'record_id' => $itemLiteId,
            'error_description' => $item->error_description,
          ));
        }

      }catch(Exception $e){

        

      }

      // buyers_statistics_grouped ////////

      $exists = $this->recordExists(
        $date,
        $item->buyer_id,
        $item->buyer_channel_id,
        $item->lead_webmaster_id,
        $item->lead_webmaster_agent_id,
        $postResult->status,
        $item->lead_product
      );

      if(!$exists){
        $this->insertIntoGrouped(
          $date,
          $item->buyer_id,
          $item->buyer_channel_id,
          $item->lead_webmaster_id,
          $item->lead_webmaster_agent_id,
          $postResult->status,
          $item->lead_product,
          $lead->status,
          false,
          $postResult->priceTTL //$lead->ttl
        );
      }else{
        $this->appendToGrouped(
          $date,
          $item->buyer_id,
          $item->buyer_channel_id,
          $item->lead_webmaster_id,
          $item->lead_webmaster_agent_id,
          $postResult->status,
          $item->lead_product,
          $lead->status,
          false,
          $postResult->priceTTL //$lead->ttl
        );
      }
      
      ///////////////////////////////////////

      return $item;

    }

    public function recordReturn(T3Lead_Return $return, $buyerId, $buyerChannelId, $datetime = null, $date = null){

      if($return->ttl == 0)
        return;

      $this->today($datetime, $date);

      $item = new T3BuyersStatsItem();
      $item->fromReturn($return, $datetime, $buyerId, $buyerChannelId);
      $item->makeGroupIndexFieldsNotNull();

      if(!T3BuyersStats::$turnOffWritingToPldTables){
        $item->insertIntoDatabase();
      }

      $commentId = 0;

      try{

        $this->database->query('
          insert ignore into buyers_statistics_lite_returns_comments
          set return_comment = ?
        ', array(
          $return->comment,
        ));

        $commentId = $this->database->fetchOne('
          select id from buyers_statistics_lite_returns_comments
          where return_comment = ?
        ', array(
          $return->comment,
        ));

      }catch(Exception $e){}

      try{

        $this->database->insert('buyers_statistics_lite_returns', array(
          'record_datetime' => $item->record_datetime,
          'buyer_id' => $item->buyer_id,
          'buyer_channel_id' => $item->buyer_channel_id,
          'lead_webmaster_id' => $item->lead_webmaster_id,
          'lead_id' => $item->lead_id,
          'lead_product_id' => T3Products::getID($item->lead_product),
          'comment_id' => $commentId,
          'earnings' => $item->earnings,
        ));

      }catch(Exception $e){}

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

    }

    public function fillReturnsGroupedTable(){

      $lastDates = $this->database->fetchCol("
        select record_date from buyers_statistics_returns_grouped
        order by record_date desc limit 1
      ");

      if(!empty($lastDates)){

        $lastDatesString = dbQuote($this->database, $lastDates);

        $lastDateIds = $this->database->fetchCol("
          select id from leads_returns where date(return_datetime) in ($lastDatesString)
        ");

        $lastDateIdsString = dbQuote($this->database, $lastDateIds);

        $this->database->query("
          update leads_returns set buyers_returns_grouped_checked=0 where id in ($lastDateIdsString)
        ");

        $this->database->query("
          delete from buyers_statistics_returns_grouped where record_date in ($lastDatesString)
        ");

      }


      $ids = $this->database->fetchCol("
        select id from leads_returns where not buyers_returns_grouped_checked
      ");

      if(empty($ids))
        return;

      $idsString = dbQuote($this->database, $ids);

      $this->database->query("
        insert into buyers_statistics_returns_grouped
        select

          0,
          date(return_datetime),
          count(*),
          sum(ttl),
          buyer,
          posting,
          product,
          0,
          comment 

        from leads_returns where id in ($idsString)
        group by date(return_datetime), buyer, posting, product, comment
      ");

      $this->database->query("
        update leads_returns set buyers_returns_grouped_checked=1 where id in ($idsString)
      ");

    }

    public function getTodaySummaryForBuyerChannels($buyerId){

      $data = $this->database->fetchAll(
        "SELECT DATE(NOW()) AS `date`, bc.id AS channel_id, " .
        "IFNULL(t1.sold, 0) AS sold, IFNULL(t1.send, 0) AS send, " .
        "IFNULL(ROUND(sold/send*100, 2), 0) AS persents FROM " .
        "(SELECT id FROM buyers_channels WHERE " . (!empty($buyerId) ? "buyer_id = ?" : "1") . ") bc " .
        "LEFT JOIN " .
        "(SELECT buyer_channel_id, SUM(leads_count*(post_result_status='Sold')) AS sold, " .
        "SUM(leads_count) AS send " .
        "FROM buyers_statistics_grouped " .
        "WHERE " . (!empty($buyerId) ? "buyer_id = ?" : "1") . " AND " .
        "record_date = DATE(NOW()) " .
        "GROUP BY buyer_channel_id) t1 " .
        "ON bc.id = t1.buyer_channel_id ", 
        !empty($buyerId) ? array($buyerId, $buyerId) : array()
      );

      return $data;

    }

    public function getWebmastersAgents(){

      return $this->database->fetchPairs("
        select id, nickname
        from users
        where role='webmaster_agent'
      ");

    }

    protected function arrayToCSVString(&$values){

      $string = '';

      foreach($values as $v){
        $string .= '"' . str_replace('"', '""' , $v) . '";';
      }

      $string .= "\n";

      return $string;

    }

    protected function tableToCSVString(&$table){

      $result = '';

      if(empty($table))
        return $result;

      $first = reset($table);
      $keys = array_keys($first);

      $result .= $this->arrayToCSVString($keys);

      foreach($table as $v)
        $result .= $this->arrayToCSVString($v);

      return $result;

    }

    public function exportCSV(& $table){

      header('Content-type: text/csv');
      header('Content-Disposition: attachment; filename="data.csv"');
      
      die($this->tableToCSVString($table));

    }

    public function & getSummaryByDays($query, &$statuses){
      $statuses = array();

      if($query['empty']){
        $result = array();
        return $result;
      }

      $availableFilters = T3BuyersStats::$summaryAvailableConditionFields;

      $select = T3Db::apiReplicant()->select()
        ->from('buyers_statistics_grouped', array(
          'record_date',
          'system_record_date' => 'record_date',
          'leads_count' => 'sum(leads_count)',
          'leads_earnings' => 'sum(leads_earnings*(post_result_status="sold"))',
          'returns_count' => 'sum(returns_count)',
          'returns_earnings' => 'sum(returns_earnings)',
          'total_earnings' => 'sum(leads_earnings*(post_result_status="sold")+returns_earnings)',
        ));

      $statusesSelect = T3Db::apiReplicant()->select()
        ->from('buyers_statistics_grouped', array(
          'record_date',
          'leads_count' => 'sum(leads_count)',
          'post_result_status',
        ));

      foreach($availableFilters as $filter){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }
        else if($query['select_types'][$filter] == 'certain'){

          if($filter == 'lead_product'){
            $leadProductId = T3Products::getID($query['select_values']['lead_product']);
            $select->where("lead_product_id = ?", $leadProductId);
            $statusesSelect->where("lead_product_id = ?", $leadProductId);
          }
          else{
            $select->where("$filter = ?", $query['select_values'][$filter]);
            $statusesSelect->where("$filter = ?", $query['select_values'][$filter]);
          }
        } 
      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote(T3Db::apiReplicant(), $allowedProductsIds);
          $select->where("buyers_statistics_grouped.lead_product_id in ($row)");
        }
      }

      if(!empty($query['start_date'])){
        $zd = new Zend_Date($query['start_date'], MYSQL_DATETIME_FORMAT_ZEND);
        $select->where('record_date >= date(?)', $query['start_date']);
        $statusesSelect->where('record_date >= date(?)', $query['start_date']);
      }

      if(!empty($query['end_date'])){
        $zd = new Zend_Date($query['end_date'], MYSQL_DATETIME_FORMAT_ZEND);

        $select->where('record_date <= date(?)', $query['end_date']);
        $statusesSelect->where('record_date <= date(?)', $query['end_date']);
      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $statusesSelect->where("0");
        else{
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote(T3Db::apiReplicant(), $allowedProductsIds);
          $statusesSelect->where("lead_product_id in ($row)");
        }
      }

      $select->group('record_date');
      $select->order('record_date desc');

      $statusesSelect->group('record_date');
      $statusesSelect->group('post_result_status');
      $statusesSelect->order('record_date');

      if($query['isBuyer']){
        $selects = array(
          $select,
          $statusesSelect,
        );
        foreach($selects as $s)
          if(!empty($s))
            $s->where('buyer_id = ?', $query['buyerId']);
      }

      $output = T3Db::apiReplicant()->query($select)->fetchAll();

      if(empty($output))
        return $output;

      $zd = new Zend_Date();
      if(!empty($query['end_date'])){
        try{
          $zd->set($query['end_date'], MYSQL_DATE_FORMAT_ZEND);
        }catch(Exception $e){}
      }
      $zd->addDay(1);
      $zd->setTime('00:00:00');
      
      $result = array();

      foreach($output as $v){

        for(;;){
          $zd->subDay(1);
          $zdString = $zd->toString(MYSQL_DATE_FORMAT_ZEND);
          if($zdString == $v['system_record_date'])
            break;
          $result[] = array(
            'record_date' => $zdString,//DateFormat::dateOnly($zdString),
            'system_record_date' => $zdString,
            'leads_count' => '0',
            'leads_earnings' => '0.0',
            'returns_count' => '0',
            'returns_earnings' => '0.0',
            'total_earnings' => '0.0',    
          );
        }

        $result[] = $v;

      }

      $statusesResult = T3Db::apiReplicant()->query($statusesSelect)->fetchAll();
      
      $statusesKeys = array();
      foreach($statusesResult as &$v){
        $statusesKeys[$v['post_result_status']] = true;
      }

      $statuses = array_keys($statusesKeys);
      $index = array();
      foreach($result as $k => &$v){
        foreach($statuses as $status)
          $v["status_$status"] = 0;
        $index[$v['record_date']] = & $result[$k];
      }

      foreach($statusesResult as &$v){
        $index[$v['record_date']]["status_{$v['post_result_status']}"] = $v['leads_count'];
      }      

      return $result;

    }

    public function getDetailedInfoQuery($query, $forCount = false){

      $select = $this->database->select();

      if($query['empty']){
        return $select;
      }

      if(!$forCount){
        $select
          ->from('buyers_statistics', array(
            'record_datetime',          //  datetime
            'buyer_id',                 //  int(11)
            'buyer_channel_id',         //  int(11)
            'lead_webmaster_id',        //  int(11)
            'lead_webmaster_agent_id',  //  int(11)
            'lead_id',                  //  int(11)
            'lead_product',             //  varchar(255)
            'lead_status',              //  varchar(255)
            'lead_get_method',          //  varchar(255)
            'lead_is_from_v1',          //  tinyint(1)
            'lead_email',
            'lead_ip',
            'post_result_status',
            'earnings',
            'error_description',
            'is_return',
          ));
      }else{
        $select->from('buyers_statistics', array('c' => 'count(*)'));
      }

      if(!$forCount){
        $select->joinLeft(
          'leads_data',
          "buyers_statistics.lead_id=leads_data.id",
          array('lead_num' => 'num', 'lead_state' => 'data_state', 'lead_ttl' => 'ttl')
        );
      }

      if(!$forCount){
        $select
          ->joinLeft(
            'users_company_webmaster',
            "buyers_statistics.lead_webmaster_id=users_company_webmaster.id",
            array('lead_webmaster_systemName' => 'systemName')
          )
          ->joinLeft(
            'users_company_buyer',
            "buyers_statistics.buyer_id=users_company_buyer.id",
            array(
              'buyer_systemName' => 'systemName',
            )
          )
          ->joinLeft(
            'leads_type',
            "buyers_statistics.lead_product=leads_type.name",
            array('lead_product_title' => 'title')
          )
          ->joinLeft(
            'users',
            "buyers_statistics.lead_webmaster_agent_id=users.id",
            array('lead_webmaster_agent_nickname' => 'nickname')
          )
          ->joinLeft(
            'buyers_channels',
            "buyers_statistics.buyer_channel_id=buyers_channels.id",
            array('buyer_channel_title' => 'title')
          );

      }

      $availableFilters = T3BuyersStats::$detailedInfoAvailableConditionFields;


      foreach($availableFilters as $filter => $fieldName){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }else if($query['select_types'][$filter] == 'certain'){
          $select->where("$fieldName = ?", $query['select_values'][$filter]);
        }

      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{
          $row = dbQuote($this->database, $query['allowedProducts']);
          $select->where("buyers_statistics.lead_product in ($row)");
        }
      }

      if(!empty($query['start_date']))
        $select->where('buyers_statistics.record_datetime >= ?', $query['start_date']);

      if(!empty($query['end_date']))
        $select->where('buyers_statistics.record_datetime <= ?', $query['end_date']);

      if(!$forCount){
        if(empty($query['without_paging']))
          $select->limit($query['page_size'], ($query['_page']-1)*$query['page_size']);
        $select->order('record_datetime desc');
      }

      if($query['isBuyer']){
        $select->where('buyers_statistics.buyer_id = ?', $query['buyerId']);
      }    
      
      return $select;

    }

    public function getDetailedInfoQuery_Lite($query, $forCount = false){

      $select = $this->database->select();

      if($query['empty']){
        return $select;
      }

      if(!$forCount){
        $select
          ->from('buyers_statistics_lite', array(
            'id',
            'record_datetime',
            'buyer_id',
            'buyer_channel_id',
            'lead_webmaster_id',
            'lead_id',
            'lead_product_id',      
            'post_result_status',
            'earnings',
          ));
      }
      else{
        $select->from('buyers_statistics_lite', array('c' => 'count(*)'));
      }


      if(!$forCount){        

        $select->joinLeft(
          'leads_data',
          "buyers_statistics_lite.lead_id=leads_data.id",
          array('lead_num' => 'num', 'lead_state' => 'data_state', 'lead_ttl' => 'ttl')
        );
      }

      $availableFilters = T3BuyersStats::$detailedInfoAvailableConditionFields_Lite;


      foreach($availableFilters as $filter => $fieldName){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }else if($query['select_types'][$filter] == 'certain'){

          if($filter == 'lead_product'){
            $leadProductId = T3Products::getID($query['select_values']['lead_product']);
            $select->where("buyers_statistics_lite.lead_product_id = ?", $leadProductId);
          }else{
            $select->where("$fieldName = ?", $query['select_values'][$filter]);
          }
          
        }

      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote($this->database, $allowedProductsIds);
          $select->where("buyers_statistics_lite.lead_product_id in ($row)");
        }
      }

      if(!empty($query['start_date']))
        $select->where('buyers_statistics_lite.record_datetime >= ?', $query['start_date']);

      if(!empty($query['end_date']))
        $select->where('buyers_statistics_lite.record_datetime <= ?', $query['end_date']);

      if(!$forCount){
        if(empty($query['without_paging']))
          $select->limit($query['page_size'], ($query['_page']-1)*$query['page_size']);
        $select->order('record_datetime desc');
      }

      if($query['isBuyer']){
        $select->where('buyers_statistics_lite.buyer_id = ?', $query['buyerId']);
      }





      return $select;

    }


    public function & getDetailedInfo($query){

      if(!T3BuyersStats::$useLiteStatisticsTable){
        $select = $this->getDetailedInfoQuery($query, false);
      }else{
        $select = $this->getDetailedInfoQuery_Lite($query, false);
      }

      $selectText = $select->__toString();

      $result = T3Db::apiReplicant()->fetchAll($selectText);


      if(!empty($result)){

        if(T3BuyersStats::$useLiteStatisticsTable){

          $buyersAgents = T3Db::apiReplicant()->fetchPairs('SELECT id, agentID FROM users_company_buyer');

          $agentsNames = T3UserBuyerAgents::getAgentsListPairs();

          $idsBuyers = T3Db::apiReplicant()->fetchPairs('SELECT id, systemName FROM users_company_buyer');
          $idsBuyersChannels = T3Db::apiReplicant()->fetchPairs('SELECT id, title FROM buyers_channels');

          $webmastersIds = array();
          foreach($result as $v){
            $webmastersIds[] = $v['lead_webmaster_id'];
          }
          $webmastersIds = array_unique($webmastersIds);
          if(!empty($webmastersIds)){
            
            $webmastersIdsString = implode(', ', $webmastersIds);
            $webmastersData = T3Db::apiReplicant()->fetchAll("SELECT id, systemName, agentID FROM users_company_webmaster where id in ($webmastersIdsString)");

          }else{
            $webmastersData = array();
          }

          $webmastersAgentsIds = T3Db::apiReplicant()->fetchCol('SELECT id FROM users_webmaster_agents');
          if(!empty($webmastersAgentsIds)){
            $webmastersAgentsIdsString = implode(', ', $webmastersAgentsIds);
            $webmastersAgentsIdsNames = T3Db::apiReplicant()->fetchPairs("select id, nickname from users where id in ($webmastersAgentsIdsString)");
          }else{
            $webmastersAgentsIdsNames = array();
          }

          $webmastersData = groupBy($webmastersData, null, 'id');

          $leadsIds = array();
          foreach($result as $k => $v)
            $leadsIds[] = $v['lead_id'];

          if(!empty($leadsIds)){

            $leadsIdsString = implode(', ', $leadsIds);
            $leadsData = T3Db::apiReplicant()->fetchAll("
                    select
                    id, data_email, data_state, ip_address, ttl, num, get_method,data_phone
                    from leads_data where id in ($leadsIdsString)");

            $leadsData = groupBy($leadsData, null, 'id');

          }else{
            $leadsData = array();
          }

          $statsIds = array();
          foreach($result as $v){
            $statsIds[] = $v['id'];
          }
          if(!empty($statsIds)){
            $statsIdsString = implode(', ', $statsIds);
            $errorsPairs = T3Db::apiReplicant()->fetchPairs("
              select record_id, error_description
              from buyers_statistics_errors where record_id in ($statsIdsString)
            ");
          }else{
            $errorsPairs = array();
          }

          $products = T3Db::apiReplicant()->fetchPairs("
            select id, title from leads_type where activ='1'
          ");

          foreach($result as $k => $v){

            $result[$k]['lead_is_from_v1'] = '0';
            $result[$k]['has_return'] = '0';
            $result[$k]['buyer_systemName'] = isset($idsBuyers[$result[$k]['buyer_id']]) ? $idsBuyers[$result[$k]['buyer_id']] : '';
            $result[$k]['buyer_channel_title'] = isset($idsBuyersChannels[$result[$k]['buyer_channel_id']]) ? $idsBuyersChannels[$result[$k]['buyer_channel_id']] : '';
            $result[$k]['lead_webmaster_systemName'] = isset($webmastersData[$result[$k]['lead_webmaster_id']]['systemName']) ? $webmastersData[$result[$k]['lead_webmaster_id']]['systemName'] : '';

            if(!empty($webmastersData[$result[$k]['lead_webmaster_id']]['agentID']) && isset($webmastersAgentsIdsNames[$webmastersData[$result[$k]['lead_webmaster_id']]['agentID']])){
              $result[$k]['lead_webmaster_agent_nickname'] = $webmastersAgentsIdsNames[$webmastersData[$result[$k]['lead_webmaster_id']]['agentID']];
            }else{
              $result[$k]['lead_webmaster_agent_nickname'] = '';
            }
            
            if (!empty($result[$k]['earnings']) && $result[$k]['earnings']>0){
            $result[$k]['lead_phone'] = isset($leadsData[$result[$k]['lead_id']]['data_phone']) ? $leadsData[$result[$k]['lead_id']]['data_phone'] : '';
            }else{
                $result[$k]['lead_phone'] = '';    
            }
            $result[$k]['lead_email'] = isset($leadsData[$result[$k]['lead_id']]['data_email']) ? $leadsData[$result[$k]['lead_id']]['data_email'] : '';
            $result[$k]['lead_state'] = isset($leadsData[$result[$k]['lead_id']]['data_state']) ? $leadsData[$result[$k]['lead_id']]['data_state'] : '';
            $result[$k]['lead_ip'] = isset($leadsData[$result[$k]['lead_id']]['ip_address']) ? $leadsData[$result[$k]['lead_id']]['ip_address'] : '';
            $result[$k]['lead_ttl'] = isset($leadsData[$result[$k]['lead_id']]['ttl']) ? $leadsData[$result[$k]['lead_id']]['ttl'] : '';
            $result[$k]['lead_num'] = isset($leadsData[$result[$k]['lead_id']]['num']) ? $leadsData[$result[$k]['lead_id']]['num'] : '';
            $result[$k]['lead_get_method'] = isset($leadsData[$result[$k]['lead_id']]['get_method']) ? $leadsData[$result[$k]['lead_id']]['get_method'] : '';


            $result[$k]['lead_product_title'] = isset($products[$result[$k]['lead_product_id']]) ? $products[$result[$k]['lead_product_id']] : '';

            $result[$k]['error_description'] = isset($errorsPairs[$result[$k]['id']]) ? $errorsPairs[$result[$k]['id']] : '';

          }

        }else{

          $indices = array();
          $back = array();
          foreach($result as $k => $v){
            if(!$result[$k]['is_return']){
              $index = $v['lead_id'] . "_" . $v['buyer_channel_id'];
              $indices[] = $index;
              $back[$index] = $k;
            }
            $result[$k]['has_return'] = 0;
          }

          if(!empty($indices)){

            $indicesString = dbQuote(T3Db::apiReplicant(), $indices);

            $col = T3Db::apiReplicant()->fetchAll("select return_datetime, concat(lead_id, '_', posting) as `index` from leads_returns where concat(lead_id, '_', posting) in ($indicesString)");

            foreach($col as $v){
              if(!isset($back[$v['index']]))
                continue;
              $result[$back[$v['index']]]['has_return'] = 1;
              $result[$back[$v['index']]]['lead_return_datetime'] = $v['return_datetime'];
            }

          }

        }

      }
      
      return $result;

    }

    public function getDetailedInfoCount($query){

      return 10000;
    
      $select = $this->getDetailedInfoQuery($query, true);
      $result = $this->database->query($select)->fetchAll();
      return empty($result) ? 0 : $result[0]['c'];

    }


    public function getReturnsQuery($query, $forCount = false){

      $select = $this->database->select();

      if($query['empty']){
        return $select;
      }

      if(!$forCount){
        $select
          ->from('leads_returns', array(
            'lead_id',
            'lead_is_from_v1' => 'from_v1',
            'lead_product' => 'product',
            'lead_get_method' => 'get_method',
            'webmaster_channel_id' => 'channel_id',
            'subacc',
            'invoiceItemType',
            'invoiceItemID',
            'buyer_id' => 'buyer',
            'buyer_channel_id' => 'posting',
            'webmaster_id' => 'affid',
            'refaffid',
            'lead_webmaster_agent_id' => 'agentID',
            'wm',
            'ref',
            'agn',
            'ttl',
            'lead_datetime',
            'record_datetime' => 'return_datetime',
            'lead_email' => 'data_email',
            'data_phone',
            'data_ssn',
            'data_state',
            'cron',
            'return_comment' => 'comment',
          ));
      }else{
        $select->from('leads_returns', array('c' => 'count(*)'));
      }

      $select->joinLeft(
        'leads_data',
        "leads_returns.lead_id=leads_data.id",
        array('lead_num' => 'num', 'lead_state' => 'data_state')
      );
      
      if(!$forCount){

        $select
          ->joinLeft(
            'users_company_webmaster',
            "leads_returns.affid=users_company_webmaster.id",
            array('lead_webmaster_systemName' => 'systemName')
          )
          ->joinLeft(
            'users_company_buyer',
            "leads_returns.buyer=users_company_buyer.id",
            array(
              'buyer_systemName' => 'systemName',
            )
          )
          ->joinLeft(
            'leads_type',
            "leads_returns.product=leads_type.name",
            array('lead_product_title' => 'title')
          )
          ->joinLeft(
            'users',
            "leads_returns.agentID=users.id",
            array('lead_webmaster_agent_nickname' => 'nickname')
          )
          ->joinLeft(
            'buyers_channels',
            "leads_returns.posting=buyers_channels.id",
            array('buyer_channel_title' => 'title')
          );

      }

      $availableFilters = T3BuyersStats::$returnsAvailableConditionFields;

      foreach($availableFilters as $filter => $fieldName){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }else if($query['select_types'][$filter] == 'certain'){
          $select->where("$fieldName = ?", $query['select_values'][$filter]);
        }

      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{
          $row = dbQuote($this->database, $query['allowedProducts']);
          $select->where("leads_returns.product in ($row)");
        }
      }

      if(!empty($query['start_date']))
        $select->where('return_datetime >= ?', $query['start_date']);

      if(!empty($query['end_date']))
        $select->where('return_datetime <= ?', $query['end_date']);

      if(!$forCount){
        if(empty($query['without_paging']))
          $select->limit($query['page_size'], ($query['_page']-1)*$query['page_size']);
        $select->order('return_datetime desc');
      }

      if($query['isBuyer'])
        $select->where('leads_returns.buyer = ?', $query['buyerId']);

      /*  
	  if($query['buyerAgentId'] && $query['buyerAgentId'] != '') {
      	
      	$buyer_agent_channels = T3UserBuyerAgent::getBuyerAgentChannels( (int)$query['buyerAgentId'] );
      	if(count($buyer_agent_channels)){
      	$select->where('leads_returns.posting in('. implode(',', $buyer_agent_channels).')');
      	//varDump2($select->__toString());
      	} else {
      		$select->where('leads_returns.posting in(100)');
      	}
      }
      */  
        
      return $select;

    }

    public function getReturnsQuery_Lite($query, $forCount = false){

      $select = $this->database->select();

      if($query['empty']){
        return $select;
      }

      if(!$forCount){
        $select
          ->from('buyers_statistics_lite_returns', array(
            'lead_id',
            'lead_product_id',
            'buyer_id',
            'buyer_channel_id',
            'webmaster_id' => 'lead_webmaster_id', 'lead_webmaster_id', // чтобы было доступно по обоим названиям
            'ttl' => 'earnings',
            'return_datetime' => 'record_datetime', 'record_datetime',
            'comment_id',
            //'return_comment' => 'comment', // write ???
          ));
      }else{
        $select->from('buyers_statistics_lite_returns', array('c' => 'count(*)'));
      }


      if(!$forCount){

      }

      $availableFilters = T3BuyersStats::$returnsAvailableConditionFields_Lite;

      foreach($availableFilters as $filter => $fieldName){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }else if($query['select_types'][$filter] == 'certain'){

          if($filter == 'lead_product'){
            $leadProductId = T3Products::getID($query['select_values']['lead_product']);
            $select->where("buyers_statistics_lite_returns.lead_product_id = ?", $leadProductId);
          }else{
            $select->where("$fieldName = ?", $query['select_values'][$filter]);
          }

        }

      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{          
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote($this->database, $allowedProductsIds);
          $select->where("buyers_statistics_lite_returns.lead_product_id in ($row)");
        }
      }


      if(!empty($query['start_date']))
        $select->where('record_datetime >= ?', $query['start_date']);

      if(!empty($query['end_date']))
        $select->where('record_datetime <= ?', $query['end_date']);

      if(!$forCount){
        if(empty($query['without_paging']))
          $select->limit($query['page_size'], ($query['_page']-1)*$query['page_size']);
        $select->order('record_datetime desc');
      }

      if($query['isBuyer'])
        $select->where('buyers_statistics_lite_returns.buyer_id = ?', $query['buyerId']);


      return $select;

    }

    public function & getReturns($query){


      if(!T3BuyersStats::$useLiteStatisticsTable){
        $select = $this->getReturnsQuery($query, false);
      }else{
        $select = $this->getReturnsQuery_Lite($query, false);
      }

      $selectText = $select->__toString();

      $result = T3Db::apiReplicant()->fetchAll($selectText);


      if(!empty($result)){

        if(T3BuyersStats::$useLiteStatisticsTable){

          $commentsData = T3Db::apiReplicant()->fetchPairs('
            SELECT id, return_comment FROM buyers_statistics_lite_returns_comments
          ');

          $agentsNames = T3UserBuyerAgents::getAgentsListPairs();

          $idsBuyers = T3Db::apiReplicant()->fetchPairs('SELECT id, systemName FROM users_company_buyer');
          $idsBuyersChannels = T3Db::apiReplicant()->fetchPairs('SELECT id, title FROM buyers_channels');

          $webmastersIds = array();
          foreach($result as $v){
            $webmastersIds[] = $v['lead_webmaster_id'];
          }
          $webmastersIds = array_unique($webmastersIds);
          if(!empty($webmastersIds)){

            $webmastersIdsString = implode(', ', $webmastersIds);
            $webmastersData = T3Db::apiReplicant()->fetchAll("SELECT id, systemName, agentID FROM users_company_webmaster where id in ($webmastersIdsString)");

          }else{
            $webmastersData = array();
          }

          $webmastersAgentsIds = T3Db::apiReplicant()->fetchCol('SELECT id FROM users_webmaster_agents');
          if(!empty($webmastersAgentsIds)){
            $webmastersAgentsIdsString = implode(', ', $webmastersAgentsIds);
            $webmastersAgentsIdsNames = T3Db::apiReplicant()->fetchPairs("select id, nickname from users where id in ($webmastersAgentsIdsString)");
          }else{
            $webmastersAgentsIdsNames = array();
          }

          $webmastersData = groupBy($webmastersData, null, 'id');

          $leadsIds = array();
          foreach($result as $k => $v)
            $leadsIds[] = $v['lead_id'];

          if(!empty($leadsIds)){

            $leadsIdsString = implode(', ', $leadsIds);
            $leadsData = T3Db::apiReplicant()->fetchAll("
                    select
                    id, data_email, data_state, ip_address, ttl, num, get_method
                    from leads_data where id in ($leadsIdsString)");

            $leadsData = groupBy($leadsData, null, 'id');

          }else{
            $leadsData = array();
          }


          $products = T3Db::apiReplicant()->fetchPairs("
            select id, title from leads_type where activ='1'
          ");

          foreach($result as $k => $v){

            $result[$k]['lead_is_from_v1'] = '0';
            $result[$k]['has_return'] = '0';
            $result[$k]['buyer_systemName'] = isset($idsBuyers[$result[$k]['buyer_id']]) ? $idsBuyers[$result[$k]['buyer_id']] : '';
            $result[$k]['buyer_channel_title'] = isset($idsBuyersChannels[$result[$k]['buyer_channel_id']]) ? $idsBuyersChannels[$result[$k]['buyer_channel_id']] : '';
            $result[$k]['lead_webmaster_systemName'] = isset($webmastersData[$result[$k]['lead_webmaster_id']]['systemName']) ? $webmastersData[$result[$k]['lead_webmaster_id']]['systemName'] : '';

            if(!empty($webmastersData[$result[$k]['lead_webmaster_id']]['agentID']) && isset($webmastersAgentsIdsNames[$webmastersData[$result[$k]['lead_webmaster_id']]['agentID']])){
              $result[$k]['lead_webmaster_agent_nickname'] = $webmastersAgentsIdsNames[$webmastersData[$result[$k]['lead_webmaster_id']]['agentID']];
            }else{
              $result[$k]['lead_webmaster_agent_nickname'] = '';
            }


            $result[$k]['lead_email'] = isset($leadsData[$result[$k]['lead_id']]['data_email']) ? $leadsData[$result[$k]['lead_id']]['data_email'] : '';
            $result[$k]['lead_state'] = isset($leadsData[$result[$k]['lead_id']]['data_state']) ? $leadsData[$result[$k]['lead_id']]['data_state'] : '';
            $result[$k]['lead_ip'] = isset($leadsData[$result[$k]['lead_id']]['ip_address']) ? $leadsData[$result[$k]['lead_id']]['ip_address'] : '';
            $result[$k]['lead_ttl'] = isset($leadsData[$result[$k]['lead_id']]['ttl']) ? $leadsData[$result[$k]['lead_id']]['ttl'] : '';
            $result[$k]['lead_num'] = isset($leadsData[$result[$k]['lead_id']]['num']) ? $leadsData[$result[$k]['lead_id']]['num'] : '';
            $result[$k]['lead_get_method'] = isset($leadsData[$result[$k]['lead_id']]['get_method']) ? $leadsData[$result[$k]['lead_id']]['get_method'] : '';


            $result[$k]['lead_product_title'] = isset($products[$result[$k]['lead_product_id']]) ? $products[$result[$k]['lead_product_id']] : '';

            $result[$k]['return_comment'] = isset($commentsData[$result[$k]['comment_id']]) ? $commentsData[$result[$k]['comment_id']] : '';

          }

        }

      }


      return $result;

    }

    public function getReturnsCount($query){

      if(!T3BuyersStats::$useLiteStatisticsTable){
        $select = $this->getReturnsQuery($query, true);
      }else{
        $select = $this->getReturnsQuery_Lite($query, true);
      }

      $result = $this->database->query($select)->fetchAll();
      return empty($result) ? 0 : $result[0]['c'];

    }

    public function getBuyersList($isBuyerAgent = false){

      if(!$isBuyerAgent){
        return T3Users::getInstance()->getBuyersSystemNames_Array();
      }else{

        $productsForAgent = T3UserBuyerAgents::getProducts();
        if(empty($productsForAgent))
          return array();


        $list = dbQuote($this->database, $productsForAgent);
        return $this->database->fetchPairs("
          select a.buyer_id , b.systemName
          from buyers_channels a
          left join users_company_buyer b
          on a.buyer_id = b.id
          where product in ($list) 
          group by a.buyer_id
          order by b.systemName
        ");

      }

    }

    public function getBuyersProductsDistribution($products){

      if(empty($products))
        return array();

      $productsStr = dbQuote($this->database, $products);

      $data = T3Db::apiReplicant()->fetchAll("
        select a.buyer_id, a.product, b.systemName as buyer_systemName from buyers_channels a
        left join users_company_buyer b
        on a.buyer_id = b.id
        where product in ($productsStr) group by product, buyer_id
        order by b.systemName
      ");

      $result = array('' => array());

      foreach($products as $product)
        $result[$product] = array();


      foreach($data as $v){
        if(!isset($result[$v['product']]))
          continue;
        $result[$v['product']][$v['buyer_id']] = $v['buyer_systemName'];
        $result[''][$v['buyer_id']] = $v['buyer_systemName'];
      }

      return $result;

    }

    public function getBuyerChannels($buyerId, $isBuyerAgent = false){

      if(!$isBuyerAgent){

        return T3Db::apiReplicant()->fetchPairs('
          select id, title
          from buyers_channels
          where buyer_id = ?
        ', array($buyerId));

      }else{

        $productsForAgent = T3UserBuyerAgents::getProducts();
        if(empty($productsForAgent))
          return array();

        $list = dbQuote($this->database, $productsForAgent);
        return T3Db::apiReplicant()->fetchPairs("
          select id, title
          from buyers_channels
          where buyer_id = ? and product in ($list)
        ", array($buyerId));

      }

    }
    
    public function getBuyerChannelsByProduct($buyerId, $product){

      return $this->database->fetchPairs("
          select id, title
          from buyers_channels
          where buyer_id = ? and product='$product'
        ", array($buyerId));

    }

    public function & getReturnsCommentSummaryByDays($query, &$statuses, &$statusesCounts){
      $statuses = array();

      if($query['empty']){
        $result = array();
        return $result;
      }

      $availableFilters = T3BuyersStats::$summaryAvailableConditionFields;

      $select = $this->database->select()
        ->from('buyers_statistics_returns_grouped', array(
          'record_date',
          'system_record_date' => 'record_date',
          'returns_count' => 'sum(returns_count)',
          'returns_earnings' => 'sum(returns_earnings)',
          'return_comment',
        ));

      $statusesSelect = $this->database->select()
        ->from('buyers_statistics_returns_grouped', array(
          'record_date',
          'returns_count' => 'sum(returns_count)',
          'return_comment',
        ));

      foreach($availableFilters as $filter){
        if(
          !isset($query['select_types'][$filter]) ||
          $query['select_types'][$filter] != 'all' &&
          $query['select_types'][$filter] != 'certain'
        )
          continue;

        if($query['select_types'][$filter] == 'all'){

        }else if($query['select_types'][$filter] == 'certain'){
          
          if($filter == 'lead_product'){
            $leadProductId = T3Products::getID($query['select_values']['lead_product']);
            $select->where("lead_product_id = ?", $leadProductId);
            $statusesSelect->where("lead_product_id = ?", $leadProductId);
          }else{
            $select->where("$filter = ?", $query['select_values'][$filter]);
            $statusesSelect->where("$filter = ?", $query['select_values'][$filter]);
          }

        }
        
      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $select->where("0");
        else{
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote($this->database, $allowedProductsIds);
          $select->where("buyers_statistics_returns_grouped.lead_product_id in ($row)");
        }
      }

      if(!empty($query['start_date'])){
        $zd = new Zend_Date($query['start_date'], MYSQL_DATETIME_FORMAT_ZEND);

        $select->where('record_date >= date(?)', $query['start_date']);

        $statusesSelect->where('record_date >= date(?)', $query['start_date']);
      }

      if(!empty($query['end_date'])){
        $zd = new Zend_Date($query['end_date'], MYSQL_DATETIME_FORMAT_ZEND);

        $select->where('record_date <= date(?)', $query['end_date']);
        $statusesSelect->where('record_date <= date(?)', $query['end_date']);
      }

      if(isset($query['allowedProducts'])){
        if(is_array($query['allowedProducts']) && empty($query['allowedProducts']))
          $statusesSelect->where("0");
        else{
          $allowedProductsIds = array();
          foreach($query['allowedProducts'] as $product){
            $allowedProductsIds[] = T3Products::getID($product);
          }
          $row = dbQuote($this->database, $allowedProductsIds);
          $statusesSelect->where("lead_product_id in ($row)");
        }
      }

      $select->group('record_date');
      $select->order('record_date desc');

      $statusesCountsSelect = clone $statusesSelect;
      $statusesCountsSelect->group('return_comment');
      $statusesCountsSelect->order('sum(returns_count) desc');
      $statusesCountsSelect->order('return_comment');

      $statusesSelect->group('record_date');
      $statusesSelect->group('return_comment');
      $statusesSelect->order('record_date desc');
      $statusesSelect->order('sum(returns_count) desc');
      $statusesSelect->order('return_comment');

      if($query['isBuyer']){
        $selects = array(
          $select,
          $statusesSelect,
        );
        foreach($selects as $s)
          if(!empty($s))
            $s->where('buyer_id = ?', $query['buyerId']);
      }

      $output = $this->database->query($select)->fetchAll();

      if(empty($output))
        return $output;

      $zd = new Zend_Date();
      if(!empty($query['end_date'])){
        try{
          $zd->set($query['end_date'], MYSQL_DATE_FORMAT_ZEND);
        }catch(Exception $e){}
      }
      $zd->addDay(1);
      $zd->setTime('00:00:00');

      $result = array();

      foreach($output as $v){

        for(;;){
          $zd->subDay(1);
          $zdString = $zd->toString(MYSQL_DATE_FORMAT_ZEND);
          if($zdString == $v['system_record_date'])
            break;
          $result[] = array(
            'record_date' => $zdString,
            'system_record_date' => $zdString,
            'returns_count' => '0',
            'returns_earnings' => '0.0',
          );
        }

        $result[] = $v;

      }
      $statusesResult = $this->database->query($statusesSelect)->fetchAll();
      $statusesCountsData = $this->database->query($statusesCountsSelect)->fetchAll();

      $statusesKeys = array();
      foreach($statusesResult as &$v){
        $statusesKeys[$v['return_comment']] = true;
      }

      $statusesCounts = array();
      foreach($statusesCountsData as &$v){
        $statusesCounts[$v['return_comment']] = array(
          'title' => $v['return_comment'],
          'count' => $v['returns_count'],
        );
      }

      $statuses = array_keys($statusesCounts);
      $index = array();
      foreach($result as $k => &$v){
        foreach($statuses as $status)
          $v["status_$status"] = '-';
        $index[$v['record_date']] = & $result[$k];
      }

      foreach($statusesResult as &$v){
        $index[$v['record_date']]["status_{$v['return_comment']}"] = $v['returns_count'];
      }

      return $result;

    }

    /** @return T3BuyersStats */
    public static function getInstance() {
      if (is_null(self::$_instance)) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }

    public function getStatusTitle($status){
      if(isset(self::$postResultStatuses[$status])){
        return self::$postResultStatuses[$status]['title'];
      }
      return $status;
    }

    public function removeZeroRecords(){
      $this->database->query("delete from buyers_statistics_grouped where records_count = 0");
    } 
}

T3BuyersStats::getInstance();