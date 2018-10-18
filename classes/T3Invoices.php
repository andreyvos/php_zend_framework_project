<?php


define('INVOICES_DIRECTORY', BASE_DIR . DS . 'invoices');
define('INVOICES_DIRECTORY_CRON_JOB_OVERDUES', BASE_DIR . DS . "httpdocs" . DS . 'invoices');




class T3Invoices {

    const DIRECTORY = INVOICES_DIRECTORY;
    const DIRECTORY_CRON_JOB_OVERDUES = INVOICES_DIRECTORY_CRON_JOB_OVERDUES;

    const PERIOD_TYPE_WEEKLY = 'weekly';
    const PERIOD_TYPE_BIWEEKLY = 'biweekly';
    const PERIOD_TYPE_MONTHLY = 'monthly';
    const PERIOD_TYPE_MONTHLY_MONDAY = 'monthly_monday';
    const PERIOD_TYPE_DAYS = 'days';

    const INVOICE_TEMPLATE_STANDARD = 'standard';
    const INVOICE_TEMPLATE_GROSS_FACTOR_NET = 'gross_factor_net';
    const INVOICE_TEMPLATE_RECORDS_PRICE_NET = 'records_price_net';
    const INVOICE_TEMPLATE_LEADS_COST_NET = 'leads_cost_net';
    const INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE = 'average_lead_price';
    

    protected static $_instance = null;
    protected $database;

    public static $periodsData = array(
      T3Invoices::PERIOD_TYPE_WEEKLY => array(
        'name' => T3Invoices::PERIOD_TYPE_WEEKLY,
        'title' => 'Weekly',
      ),
      T3Invoices::PERIOD_TYPE_BIWEEKLY => array(
        'name' => T3Invoices::PERIOD_TYPE_BIWEEKLY,
        'title' => 'Biweekly',
      ),
      T3Invoices::PERIOD_TYPE_MONTHLY => array(
        'name' => T3Invoices::PERIOD_TYPE_MONTHLY,
        'title' => 'Monthly',
      ),
      T3Invoices::PERIOD_TYPE_MONTHLY_MONDAY => array(
        'name' => T3Invoices::PERIOD_TYPE_MONTHLY_MONDAY,
        'title' => 'Monthly on Monday',
      ),
      T3Invoices::PERIOD_TYPE_DAYS => array(
        'name' => T3Invoices::PERIOD_TYPE_DAYS,
        'title' => 'Days',
      ),
    );

    public static $periods = array(/**/);
    public static $weekDaysData = array(
      'sunday' => array(
        'digit' => 0,
        'name' => 'sunday',
        'title' => 'Sunday',
      ),
      'monday' => array(
        'digit' => 1,
        'name' => 'monday',
        'title' => 'Monday',
      ),
      'tuesday' => array(
        'digit' => 2,
        'name' => 'tuesday',
        'title' => 'Tuesday',
      ),
      'wednesday'    => array(
        'digit' => 3,
        'name' => 'wednesday',
        'title' => 'Wednesday',
      ),
      'thursday' => array(
        'digit' => 4,
        'name' => 'thursday',
        'title' => 'Thursday',
      ),
      'friday' => array(
        'digit' => 5,
        'name' => 'friday',
        'title' => 'Friday',
      ),
      'saturday' => array(
        'digit' => 6,
        'name' => 'saturday',
        'title' => 'Saturday',
      ),
    );

    public static $weekDays = array(/**/);


    public static $invoiceDetailsConditions = array(
      'action_datetime',
      'lead_product',
      'channel_id',
      'webmaster_id' => 'leads_data.affid',
      'action_sum',
    );

    protected function __construct(){
      $this->database = T3Db::api();
      T3Invoices::$periods = array_keys(T3Invoices::$periodsData);
      T3Invoices::$weekDays = array_keys(T3Invoices::$weekDaysData);
    }

    /** @return T3Invoices */
    public static function getInstance() {
      if (is_null(self::$_instance)) {
        self::$_instance = new self();
      }
      return self::$_instance;
    }

    public function getInvoiceNextDefaultForBuyer($buyer){

      $id = $buyer->id;

      return $this->database->fetchOne('select invoices_next_default from users_company_buyer where id = ?', $id);

    }

    public function massApprove($idsArray){

      set_time_limit(0);
      
      $alreadyProcesses = T3System::getValue('mass_approve_making');
      $startDatetime = T3System::getValue('mass_approve_start_datetime');

      if($alreadyProcesses && !empty($startDatetime) && $startDatetime>mySqlDateTimeFormat(strtotime("-20 minutes"))){
        return;
      }      
      
      if(empty($idsArray))
        return;

      try{
      
        T3System::setValue('mass_approve_making', 1);
        T3System::setValue('mass_approve_start_datetime', mySqlDateTimeFormat());

        $idsStr = dbQuote($this->database, $idsArray);

        $array = $this->database->fetchAll("
          select * from
          buyers_invoices
          where id in ($idsStr)
        ");

        foreach($array as $invoiceData){

          $invoice = new T3Invoice();
          $invoice->fromArray($invoiceData);
          $invoice->sendEmailWithDefaultContents(false, $invoice->getBuyer()->invoices_emails, $invoice->sendings_number != 0);

        }
      
      }catch(Exception $e){        
      }
        
      T3System::setValue('mass_approve_making', 0);

    }
    
    public function sendNotSentInvoices(){
     
      $array = $this->database->fetchCol("
        select id from
        buyers_invoices
        where sendings_number = 0
      ");   
      
      if(!empty($array))
        $this->massApprove($array);
      
    }

    public function acceptInvoice($invoiceId, $payDateTime){

      $invoice = new T3Invoice();
      if($invoice->fromDatabase($invoiceId)===false){
        throw new Exception();
        return false;
      }

      $invoice->payFull($payDateTime);

    }


    public function getAccountingEmails(){
      if(T3System::getValue('invoices_copy_emails_is')){
        $ar = T3System::getValue('invoices_copy_emails_array');
        if(!empty($ar))
          return $ar;
        else
          return array();
      }else
        return array();
    }

    public function getInvoiceHtmlDocumentLink($id, $unique_key){
      return "https://account.t3leads.com/default/invoice/show/id/{$id}/key/{$unique_key}/";
    }

    public function getInvoicesWillBeMade(){

      $now = mySqlDateTimeFormat();

      $buyers = $this->database->fetchAll('

          select a.id, a.systemName, action_sum from users_company_buyer a

          left join

          (select buyer_id, sum(action_sum) as action_sum from (
          select buyer_id, sum(action_sum) as action_sum from buyers_leads_sellings where (invoice_id is null or invoice_id=0) group by buyer_id
          union all
          select buyer_id, sum(action_sum) as action_sum from buyers_leads_movements where (invoice_id is null or invoice_id=0) group by buyer_id
          union all
          select buyer_id, sum(action_sum) as action_sum from buyers_invoices_addings where (invoice_id is null or invoice_id=0) group by buyer_id) b
          group by buyer_id) c

          on a.id = c.buyer_id      where
            (invoices_next_default is not null 
            and invoices_term is not null 
            and invoices_period_type is not null 
            and invoices_period is not null) 
            and invoices_next_default < ? 
            and action_sum>0
            and invoices_are_manual=0
          order by a.systemName

      ', array($now));

      return $buyers;

    }

    public function autoMaking(){

      set_time_limit(0);
      
      $resultIds = array();
      
      $alreadyProcesses = T3System::getValue('invoicesMakingExecution');
      $startDatetime = T3System::getValue('invoicesMakingExecutionStartDatetime');

      if($alreadyProcesses && $startDatetime>mySqlDateTimeFormat(strtotime("-10 hours"))){
        return;
      }

      try{

        T3System::setValue('invoicesMakingExecution', 1);
        T3System::setValue('invoicesMakingExecutionStartDatetime', mySqlDateTimeFormat());


        $this->beforeInvoiceMaking();
        $ar = $this->database->fetchAll('
          SELECT *
          FROM users_company_buyer
          where !invoices_are_manual
        ');
        $date = new Zend_Date();
        $date->setTime('00:00:00');

        $nextDefault = new Zend_Date();
        foreach($ar as &$v){
          
          try{
          
            if(empty($v['invoices_next_default']))
              continue;
            $nextDefault->set($v['invoices_next_default'], MYSQL_DATETIME_FORMAT_ZEND);
            $nextDefault->setTime('00:00:00');
            if(!$nextDefault->isLater($date)){
              $invoice = new T3Invoice();
              $invoice->buyer = new T3BuyerCompany();
              $invoice->buyer->fromArray($v);
              if(emptyNotZero($v['invoices_term']) || empty($v['invoices_period_type']) || emptyNotZero($v['invoices_period']))
                continue;
              $invoice->periodType = $v['invoices_period_type'];
              $invoice->period = $v['invoices_period'];
              $result = $invoice->initializeByPreviousInvoice($v['id'], $v['invoices_term'], $nextDefault->toString(MYSQL_DATETIME_FORMAT_ZEND));
              if($result !== false){
                try{
                  $invoice->make($v['invoices_period_type'], $v['invoices_period']);
                  $resultIds[] = $invoice->id;
                }catch(Exception $e){

                }
              }
            }

          }catch(Exception $e){
            
          }
          
        }

        T3System::setValue('invoicesMakingExecution', 0);

      }catch(Exception $e){
        T3System::setValue('invoicesMakingExecution', 0);
      }
      
      return $resultIds;

    }

    public function getPendingAddings($buyerId){

      $ar = $this->database->fetchAll('
        select * from buyers_invoices_addings
        where buyer_id = ? and (invoice_id is null or invoice_id=0)
      ', array($buyerId));

      $result = array();
      foreach($ar as $v){
        $a = new T3Invoice_Adding();
        $a->fromArray($v);
        $result[$a->id] = $a;
      }

      return $result;

    }

    public function makeInvoiceAdding($buyerId, $sum, $comment){

      $adding = new T3Invoice_Adding();
      $adding->buyer_id = $buyerId;
      $adding->action_datetime = mySqlDateTimeFormat();
      $adding->action_sum = $sum;
      $adding->comment = $comment;
      $adding->invoice_id = null;
      $adding->insertIntoDatabase();
      return $adding->id;

    }

    public function recalcInvoice($invoice){

      if(!is_object($invoice)){
        $id = $invoice;
        $invoice = new T3Invoice();
        if($invoice->fromDatabase($id) === false)
          return false;
      }
      $invoice->calcSums();
      $invoice->saveToDatabase();
      $invoice->writePdfFile(false, $_title, $_fileName);
      return true;

    }

    public function assignUnassignedReturnsToInvoice($invoiceId){

      $this->database->query('
        update buyers_leads_movements a
        right join buyers_leads_sellings b
        on a.lead_id = b.lead_id

        set a.invoice_id = ?

        where ifnull(a.invoice_id, 0) = 0 and
        b.invoice_id = ?
      ', array($invoiceId, $invoiceId));

      $invoice = new T3Invoice();
      $invoice->fromDatabase($invoiceId);
      $invoice->calcSums();
      $invoice->saveToDatabase();
      $invoice->writePdfFile(false, $_title, $_fileName);

    }

    public function & getLeadsBySearchQuery($query){

      if($query['empty']){
        $result = array();
        return $result;
      }

      $fields = array(
        'default' => array('id','lead_id','lead_ssn', 'action_sum', 'action_datetime', 'channel_id', 'buyer_id', 'invoice_id', 'is_v1_lead'),
        'buyers_invoices_addings' => array('id','lead_id' => "('')", 'lead_ssn' => "('')", 'action_sum', 'action_datetime', 'channel_id' => "('')", 'buyer_id', 'invoice_id', 'is_v1_lead' => "('')"),
      );

      $selects = array();

      $tables = array(
        'buyers_leads_sellings',
        'buyers_leads_movements',
        'buyers_invoices_addings',
      );

      foreach($query['search_in'] as $table){

        if(!in_array($table, $tables))
          continue;

        if($table=='buyers_invoices_addings'){
          $select = $this->database->select()
            ->from($table, $fields[$table]+array('item_type' => "('$table')", 'channel_title' => "('')",'product_title' => "('')" ))
            ->joinLeft('users_company_buyer', "$table.buyer_id=users_company_buyer.id", array('buyer_system_name' => 'systemName'));
        }else
          $select = $this->database->select()
            ->from($table, $fields['default']+array('item_type' => "('$table')"))
            ->joinLeft('users_company_buyer', "$table.buyer_id=users_company_buyer.id", array('buyer_system_name' => 'systemName'))
            ->joinLeft('buyers_channels', "$table.channel_id=buyers_channels.id", array('channel_title' => 'title'))
            ->joinLeft('leads_type', "$table.lead_product=leads_type.name", array('product_title' => 'title'));

        $buyerId = null;

        if($query['select_type']=='certain_invoice'){
          if(!$query['also_free_leads']){
            $select->where("$table.invoice_id = ?" , $query['certain_invoice_id']);
          }else{

            if(empty($buyerId)){
              $buyerId = (int)($this->database->fetchOne('
                select buyer_id from buyers_leads_sellings
                where invoice_id=? limit 1', array($query['certain_invoice_id']))
              );              
            }
            
            $select->where("($table.invoice_id = ? or (($table.invoice_id is NULL or $table.invoice_id = 0) and $table.buyer_id=$buyerId))" , $query['certain_invoice_id']);

          }
        }else if($query['select_type']=='certain_buyer'){
          $select->where("$table.buyer_id = ?" , $query['certain_buyer_id']);
          if($query['only_free_leads']){
            $select->where("($table.invoice_id is NULL or $table.invoice_id = 0)");
          }
        }

        $select->where('date(action_datetime) >= date(?)', $query['start_date']);
        $select->where('date(action_datetime) <= date(?)', $query['end_date']);

        $selects[] = $select;

      }

      if(empty($selects)){
        $result = array();
        return $result;
      }

      $actualSelect = $this->database->select(array('*'))->union($selects);

      $result = $this->database->query($actualSelect)->fetchAll();
      return $result;

    }


    public function deleteInvoiceAdding($addingId){

      $adding = new T3Invoice_Adding();
      if($adding->fromDatabase($addingId) === false)
        return false;

      if(!empty($adding->invoice_id))
        return false;

      $adding->deleteFromDatabase();

      return true;

    }

    public function getPrevDefaultInvoiceDate($now, $periodType, $period){

      $nowZd = new Zend_Date($now, MYSQL_DATETIME_FORMAT_ZEND);

      switch($periodType){
        case T3Invoices::PERIOD_TYPE_WEEKLY:
          if($nowZd->get(Zend_Date::WEEKDAY_DIGIT) == 1)
            $nowZd->subWeek(1);
          $nowZd->setWeekday($period == 0 ? 7 : $period);
          break;
        case T3Invoices::PERIOD_TYPE_BIWEEKLY:
          if($nowZd->get(Zend_Date::WEEKDAY_DIGIT) == 1)
            $nowZd->subWeek(1);
          $nowZd->subWeek(1);
          $nowZd->setWeekday($period == 0 ? 7 : $period);
          break;
        case T3Invoices::PERIOD_TYPE_MONTHLY:
          if($nowZd->getDay() == $period)
            $nowZd->subMonth(1);
          $nowZd->setDay($period);
          break;
        case T3Invoices::PERIOD_TYPE_MONTHLY_MONDAY:
          if($nowZd->get(Zend_Date::WEEKDAY_DIGIT) == 1 && $nowZd->getDay()<=7)
            $nowZd->subMonth(1);
          else{
            $nowZd->setDay(1);
            $month = $nowZd->get(Zend_Date::MONTH);
            $nowZd->setWeekday(1);
            if($nowZd->get(Zend_Date::MONTH) != $month)
              $nowZd->addWeek(1);
          }
          break;
        case T3Invoices::PERIOD_TYPE_DAYS:
          $nowZd->subDay($period);
          break;
        default:
          return false;
      }

      $nowZd->setTime('00:00:00');

      return $nowZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

    }

    public function getNextDefaultInvoiceDate($now, $periodType, $period){

      $nowZd = new Zend_Date($now, MYSQL_DATETIME_FORMAT_ZEND);

      switch($periodType){
        case T3Invoices::PERIOD_TYPE_WEEKLY:
          $nowZd->addWeek(1);
          $nowZd->setWeekday($period == 0 ? 7 : $period);
          break;
        case T3Invoices::PERIOD_TYPE_BIWEEKLY:
          $nowZd->addWeek(2);
          $nowZd->setWeekday($period == 0 ? 7 : $period);
          break;
        case T3Invoices::PERIOD_TYPE_MONTHLY:
          $nowZd->addMonth(1);
          $nowZd->setDay($period);
          break;
        case T3Invoices::PERIOD_TYPE_MONTHLY_MONDAY:          
          $nowZd->addMonth(1);
          $nowZd->setDay(1);
          $month = $nowZd->get(Zend_Date::MONTH);
          $nowZd->setWeekday(1);
          if($nowZd->get(Zend_Date::MONTH) != $month)
            $nowZd->addWeek(1);
          break;
        case T3Invoices::PERIOD_TYPE_DAYS:
          $nowZd->addDay($period);
          break;
        default:
          return false;
      }

      $nowZd->setTime('00:00:00');

      return $nowZd->toString(MYSQL_DATETIME_FORMAT_ZEND);

    }

    public function beforeInvoiceMaking(){


      ///////////////////не убирать//////////////////////////
      $this->database->query('
        update buyers_leads_sellings set
        action_sum = 0
        where channel_id = 10107
      ');
      ///////////////////////////////////////////////////////



      $tables = array(
        "buyers_leads_sellings",
        "buyers_leads_movements",
      );

      $timezones = $this->database->fetchCol("
        SELECT distinct timezone
        FROM buyers_channels
      ");

      foreach($timezones as $tz){

        $ourZd = new Zend_Date();
        $theirZd = new Zend_Date();
        $intervalZd = new Zend_Date();

        $theirTime = TimeZoneTranslate::toTheir($tz);
        $theirZd->set($theirTime, MYSQL_DATETIME_FORMAT_ZEND);

        $intervalZd->set($theirZd);
        $intervalZd->sub($ourZd);
        $n = strtotime($intervalZd->toString(MYSQL_DATETIME_FORMAT_ZEND));

        foreach($tables as $table){

          $this->database->query("
            UPDATE $table bls
            LEFT JOIN buyers_channels bc
            ON bls.channel_id = bc.id
            SET bls.channel_action_datetime = bls.action_datetime + interval $n second
            WHERE bc.timezone = ? and (bls.invoice_id is null or bls.invoice_id = 0)
          ", array($tz));

        }

      }
      
    }

    public function makeOverdue(){

      $nowDate = mySqlDateFormat();
      $ids = $this->database->fetchAll("
        SELECT *
        FROM buyers_invoices
        WHERE sendings_number>0 AND not fully_paid AND not was_overdue AND date(timely_limit) <= ?
      ", array($nowDate));


      foreach($ids as $v){
        $inv = new T3Invoice();
        $inv->fromArray($v);
        $inv->makeOverdue();
      }

    }

    public function getLastInvoiceId($buyerId){
      return $this->database->fetchOne('
        select id
        from buyers_invoices
        where buyer_id = ?
        order by creation_datetime desc limit 1
      ', array($buyerId));
    }

    public function getLastInvoiceDateTime($buyer){

      $value = null;

      if(!is_object($buyer)){
        $buyerId = $buyer;
        $buyer = new T3BuyerCompany();
        $buyer->fromDatabase($buyerId);
        if($buyer === false)
          return false;
      }

      if(empty($value))
        $value = $this->database->fetchOne('
          SELECT max(period_end)
          FROM buyers_invoices
          WHERE buyer_id = ?
        ', array($buyer->id));

      if(empty($value))       
        $value = $this->database->fetchOne('
          SELECT min(channel_action_datetime)
          FROM buyers_leads_sellings
          WHERE buyer_id = ?
        ', array($buyer->id));

      if(empty($value))
        $value = $buyer->reg_date;

      if(empty($value))
        return false;

      return $value;



        /*$value = $this->database->fetchOne('
          SELECT min(action_datetime)
          FROM (
            SELECT action_datetime
            FROM buyers_leads_sellings
            WHERE buyer_id = ?
          UNION ALL
            SELECT action_datetime
            FROM buyers_leads_movements
            WHERE buyer_id = ?
          ) as tmp1
        ', array($buyerId, $buyerId));*/

    }

    public function updateBuyersInfo_Array($buyerId, $data){
      $this->database->update('users_company_buyer', $data, 'id = ' . $this->database->quote($buyerId));
    }

    public function archiveInvoice($invoiceId){

      return $this->database->query('update buyers_invoices set archived = 1 where was_overdue and id = ?', array($invoiceId));

    }

    public function unarchiveInvoice($invoiceId){

      return $this->database->query('update buyers_invoices set archived = 0 where was_overdue and id = ?', array($invoiceId));

    }

    public function getInvoiceFileLink($invoice, $forSending){

      $result = "/system/invoice-pdf.php?file={$invoice->file_title}&buyer_id={$invoice->buyer_id}";

      if($forSending){
        $result .= "&from_email=1&sending_number={$invoice->sendings_number}";
      }

      return $result;

    }

    public function getInvoicesNumbers(){

      $ar = $this->database->fetchAll("
        SELECT 'not_sent' AS t, COUNT(*) AS c, IFNULL(SUM(total_value), 0) AS s FROM buyers_invoices WHERE fully_paid = 0 AND sendings_number = 0
        UNION ALL
        SELECT 'not_paid_overdue' AS t, COUNT(*) AS c, IFNULL(SUM(total_value), 0) AS s FROM buyers_invoices WHERE fully_paid = 0 AND sendings_number > 0 AND was_overdue = 0
        UNION ALL


        SELECT 'overdue' AS t, COUNT(*) AS c, IFNULL(SUM(total_value), 0) AS s FROM buyers_invoices
        LEFT JOIN users_company_buyer ON buyers_invoices.buyer_id = users_company_buyer.id
        WHERE fully_paid = 0 AND sendings_number > 0 AND was_overdue = 1 AND !archived


        UNION ALL

        SELECT 'collections' AS t, COUNT(*) AS c, IFNULL(SUM(total_value), 0) AS s FROM buyers_invoices
        LEFT JOIN users_company_buyer ON buyers_invoices.buyer_id = users_company_buyer.id
        WHERE users_company_buyer.is_in_collections && buyers_invoices.fully_paid = 0 && buyers_invoices.sendings_number > 0
      ");

      $result = array();
      foreach($ar as $v)
        $result[$v['t']] = array(
          'count' => $v['c'],
          'sum' => is_numeric($v['s']) ? $v['s'] : 0,
         );

      return $result;

    }


    public function getInvoiceUnassignedReturns($invoiceId){
      return $this->database->fetchAll('
        select
          a.action_datetime,
          b.action_sum,
          a.lead_email,
          a.lead_ssn,
          a.lead_id,
          c.title as product_title
        from buyers_leads_movements a
        left join buyers_leads_sellings b
        on a.lead_id = b.lead_id
        left join leads_type c
        on a.lead_product = c.name
        where ifnull(a.invoice_id, 0) = 0 and
        b.invoice_id = ?
      ', array($invoiceId));

    }

    public function thereAreInvoiceUnassignedReturns($invoiceId){


      return $this->database->fetchOne('
        select count(*) from buyers_leads_movements a
        left join buyers_leads_sellings b
        on a.lead_id = b.lead_id
        where ifnull(a.invoice_id, 0) = 0 and
        b.invoice_id = ?
      ', array($invoiceId)) != 0;

    }


    public function getInvoicesLastNum($buyerId){
      return $this->database->fetchOne('
        select invoices_last_num
        from users_company_buyer
        where id = ?
      ', array($buyerId));
    }

    public function removeInvoice($id){
      $invoice = new T3Invoice();
      $invoice->fromDatabase($id);
      return $invoice->remove();
    }

    public function getInvoices_Array($conditions = array(), $order = array(), $limits = null, $onlyCount = false){
      $now = mySqlDateTimeFormat();
      if($onlyCount){
        $select = $this->database->select()->from('buyers_invoices', array('count(*)'));
        T3SimpleDbSelect::adjustStatic($select, $conditions, $order);
        return $this->database->fetchOne((string)$select);
      }else{
        $select = $this->database->select()
          ->from('buyers_invoices', array('*', 'overdue_days' => "datediff('$now', timely_limit)"))
          ->joinLeft('users_company_buyer', 'buyers_invoices.buyer_id = users_company_buyer.id', array('systemName', 'is_in_collections'));
        T3SimpleDbSelect::adjustStatic($select, $conditions, $order);
        if(!empty($limits)){
          $select->limit($limits['page_size'], ($limits['_page']-1)*$limits['page_size']);
        }
        return $this->database->query($select)->fetchAll();
      }
    }

    public function getSendings_Array($conditions = array(), $order = array()){
      $select = $this->database->select()
        ->from('buyers_invoices_sendings')
        ->joinLeft('buyers_invoices', 'buyers_invoices_sendings.invoice_id = buyers_invoices.id', array('fully_paid', 'was_overdue'/*, 'buyer_id'*/))
        ->joinLeft('users', 'buyers_invoices.buyer_id = users.company_id', 'nickname');
      T3SimpleDbSelect::adjustStatic($select, $conditions, $order);

     // vvv(array((string)$select, count($this->database->query($select)->fetchAll())));


      return $this->database->fetchAll(
        "select * from (".
              ((string)$select) .
              ") tmp group by tmp.id"
      );

     // return $this->database->query($select)->fetchAll();
    }

    public function getPayments_Array($conditions = array(), $order = array()){
      $select = $this->database->select()
        ->from('buyers_invoices_payments')
        ->joinLeft('buyers_invoices', 'buyers_invoices_payments.invoice_id = buyers_invoices.id', array('fully_paid', 'was_overdue', 'buyer_id', 'status'))
        ->joinLeft('users_company_buyer', 'buyers_invoices.buyer_id = users_company_buyer.id', 'systemName');
      T3SimpleDbSelect::adjustStatic($select, $conditions, $order);
      return $this->database->query($select)->fetchAll();
    }

    public function getInvoiceChannels_Array($invoiceId, $conditions = array(), $order = array()){
      $conditions['invoice_id'] = $invoiceId;
      $select = $this->database->select()
        ->from('buyers_invoices_channels')
        ->joinLeft('buyers_channels', 'buyers_invoices_channels.channel_id = buyers_channels.id', array('title'));
      T3SimpleDbSelect::adjustStatic($select, $conditions, $order);
      return $this->database->query($select)->fetchAll();
    }

    public function getBuyerInvoicesEmails($buyerId){
      $emails = $this->database->fetchOne('
        SELECT invoices_emails
        FROM users_company_buyer
        WHERE id = ?
      ', array($buyerId));
      return pregSplitNotEmpty("/[\s,]+/", $emails);
    }

    public function getLeadsForInvoice($invoiceId){
      return $this->database->fetchAll('
        select a.*, b.systemName as buyer_system_name, c.title as channel_title, d.title as product_title
        from buyers_leads_sellings a
        left join users_company_buyer b
        on a.buyer_id = b.id
        left join buyers_channels c
        on a.channel_id = c.id
        left join leads_type d
        on a.lead_product = d.name
        where a.invoice_id = ?
      ', array($invoiceId));
    }
    
    public function getLeadsWithoutInvoice(){
      return $this->database->fetchAll('
        select a.*, b.systemName as buyer_system_name, c.title as channel_title, d.title as product_title
        from buyers_leads_sellings a
        left join users_company_buyer b
        on a.buyer_id = b.id
        left join buyers_channels c
        on a.channel_id = c.id
        left join leads_type d
        on a.lead_product = d.name
        where (a.invoice_id is null or a.invoice_id = 0)
      ');
    }
    
    public function getLeadsWithoutInvoiceForBuyer($buyerId){
      return $this->database->fetchAll('
        select a.*, b.systemName as buyer_system_name, c.title as channel_title, d.title as product_title
        from buyers_leads_sellings a
        left join users_company_buyer b
        on a.buyer_id = b.id
        left join buyers_channels c
        on a.channel_id = c.id
        left join leads_type d
        on a.lead_product = d.name
        where (a.invoice_id is null or a.invoice_id = 0) and a.buyer_id = ?
      ', array($buyerId));
    }

    
    public function changeLeadActionSum($leadId, $actionSum, $table){

      $tables = array(
        'buyers_leads_sellings',
        'buyers_leads_movements',
        'buyers_invoices_addings',
      );

      if(!in_array($table,$tables))
        return false;

      try{

        $this->database->beginTransaction();

        $row = $this->database->fetchRow("
          select invoice_id, buyer_id, action_sum from $table
          where id = ?
        ", array($leadId));

        $invoiceId = $row['invoice_id'];
        $buyerId = $row['buyer_id'];
        $oldActionSum = $row['action_sum'];

        $this->database->query("
          update users_company_buyer
          set balance = balance + ? - ?
          where id = ?
        ", array($oldActionSum, $actionSum, $buyerId));

        $this->database->query("
          update $table
          set action_sum = ?
          where id = ?
        ", array($actionSum, $leadId));

        $this->recalcInvoice($invoiceId);

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }

      return true;

    }

    public function excludeFromInvoice($leadsArray){
      $tables = array(
        'buyers_leads_sellings',
        'buyers_leads_movements',
        'buyers_invoices_addings', 
      );
      $ids = array();
      foreach($leadsArray as $v){
        if(!in_array($v->item_type,$tables))
          continue;
        if(!isset($ids[$v->item_type]))
          $ids[$v->item_type] = array();
        $ids[$v->item_type][] = $v->item_id;
      }         

      try{

        $this->database->beginTransaction();

        $invoicesToRecalc = array();
        foreach($ids as $k => $v){
          $quotedList = dbQuote($this->database, $v);
          $invoicesToRecalc = array_merge($invoicesToRecalc, $this->database->fetchCol("
            select distinct invoice_id
            from {$k}
            where id in ($quotedList)
          "));
          $this->database->query("
            update {$k}
            set invoice_id = null
            where id in ($quotedList)
          ");
        }

        $invoicesToRecalc = array_unique($invoicesToRecalc);

        foreach($invoicesToRecalc as $id){
          $this->recalcInvoice($id);
        }

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }

    }
    
    public function includeToInvoice($leadsArray, $invoiceId){
      $tables = array(
        'buyers_leads_sellings',
        'buyers_leads_movements',
        'buyers_invoices_addings', 
      );
      $ids = array();
      foreach($leadsArray as $v){
        if(!in_array($v->item_type,$tables))
          continue;
        if(!isset($ids[$v->item_type]))
          $ids[$v->item_type] = array();
        $ids[$v->item_type][] = $v->item_id;
      }

      try{

        $this->database->beginTransaction();

        $invoicesToRecalc = array($invoiceId);

        foreach($ids as $k => $v){
          $quotedList = dbQuote($this->database, $v);
          $invoicesToRecalc = array_merge($invoicesToRecalc, $this->database->fetchCol("
            select distinct invoice_id
            from {$k}
            where id in ($quotedList)
          "));
          $this->database->query("
            update {$k}
            set invoice_id = ?
            where id in ($quotedList)
          ", array($invoiceId));
        }

        $invoicesToRecalc = array_unique($invoicesToRecalc);

        foreach($invoicesToRecalc as $id){
          $this->recalcInvoice($id);
        }

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }

    }


    public function deleteLeads($ids){
      $this->excludeFromInvoice($ids);
      $tables = array(
        'buyers_leads_sellings',
        'buyers_leads_movements',
        'buyers_invoices_addings', 
      );
      try{

        $this->database->beginTransaction();

        foreach($tables as $table){
          $idsForTable = array();
          foreach($ids as $v){
            if($v->item_type != $table)
              continue;
            $idsForTable[] = $v->item_id;
          }
          if(empty($idsForTable))
            continue;
          $idsForTableString = dbQuote($this->database, $idsForTable);

          $sums = $this->database->fetchAll("
            select buyer_id, sum(action_sum) as action_sum from $table where id in ($idsForTableString)
            group by buyer_id
          ");

          foreach($sums as $sumsv){

            $this->database->query("
              update users_company_buyer set balance = balance + ? where id = ?
            ", array($sumsv['action_sum'], $sumsv['buyer_id']));

          }

          $this->database->query("
            delete from $table where id in ($idsForTableString)
          ");

        }

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }
    }

    public function generateTestBuyer(){

      if(T3System::getValue('test_buyer_generated')){
        $this->deleteTestBuyer();
      }

      try{

        $this->database->beginTransaction();

        $b = new T3BuyerCompany();
        $b->fromArray(array (
          'id' => null,
          'systemName' => 'test_buyer',
          'companyName' => 'Test Buyer',
          'agent_id' => NULL,
          'timezone' => 'pst',
          'Country' => 'USA',
          'State' => 'DE',
          'City' => 'Claymont',
          'ZIP' => '12345',
          'Address' => NULL,
          'invoices_emails' => '0x6fwhite@gmail.com',
          'invoices_period_type' => 'weekly',
          'invoices_period' => '1',
          'invoices_term' => '10',
          'invoices_next_default' => '2010-04-12 00:00:00',
          'invoices_last_num' => '0',
          'agentID' => '0',
          'status' => 'activ',
          'balance' => '0.00',
          'reg_date' => '2010-03-06 10:52:02',
          'groupID' => '1',
        ));
        $b->insertIntoDatabase();
        $channelsIds = array();
        $channels = array();
        for($i = 0; $i < 5; $i ++){
          $channel = array(
            'id' => null,
            'product' => $i==0 ? 'ukpayday' :'payday',
            'title' => "test channel $i",
            'buyer_id' => $b->id,
            'status' => 'active',
            'email' => '0x6fwhite@gmail.com',
            'timezone' => rand(0,1)==0?'cst':'est',
            'minConstPrice' => 20.0,
            'duplicateDays' => 7,
            'settings' => 'a:2:{s:7:"Modules";a:3:{s:7:"Collect";s:22:"LoanModificationWebYes";s:4:"Send";s:8:"httpPOST";s:8:"Analysis";s:22:"LoanModificationWebYes";}s:4:"Data";a:5:{s:14:"collect_Action";s:1:"1";s:11:"collect_aid";s:1:"1";s:11:"collect_cid";s:1:"1";s:8:"send_URL";s:1:"1";s:12:"send_Timeout";s:2:"30";}}',
            'auto_on_off' => '0',
          );
          $this->database->insert('buyers_channels', $channel);
          $channelsIds[] = $this->database->lastInsertId();
          $channels[$this->database->lastInsertId()] = $channel;
        }
        $channelsTiers = array();
        foreach($channelsIds as $id){
          $n = rand(1, 3);
          $channelsTiers[$id] = array();
          for($i=0;$i<$n;$i++){
            $channelsTiers[$id][] = $i*20.0+30.0;
          }
        }
        $leadsSellingsIds = array();
        for($i = 0; $i < 100; $i ++){
          $channelId = $channelsIds[rand(0, count($channelsIds)-1)];
          $tier = $channelsTiers[$channelId][rand(0, count($channelsTiers[$channelId])-1)];
          $zd = new Zend_Date();
          $zd->subSecond(rand(10,600000));
          $this->database->insert('buyers_leads_sellings', array(
            'id' => null,
            'lead_id' => $i+1,
            'channel_id' => $channelId,
            'buyer_id' => $b->id,
            'invoice_id' => null,
            'posting_log_record_id' => null,
            'action_datetime' => $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
            'channel_action_datetime' => null,
            'action_sum' => $tier,
            'lead_email' => '0x6fwhite@gmail.com',
            'lead_ssn' => '99999999',
            'lead_home_phone' => null,
            'lead_product' => $channels[$channelId]['product'],
            'is_v1_lead' => 0,
            'temp_field1' => null,
            'syncId' => null,
          ));
          $leadsSellingsIds[] = $this->database->lastInsertId();
        }
        $leadsMovementsIds = array();
        for($i = 0; $i < 10; $i ++){
          $channelId = $channelsIds[rand(0, count($channelsIds)-1)];
          $tier = $channelsTiers[$channelId][rand(0, count($channelsTiers[$channelId])-1)];
          $zd = new Zend_Date();
          $zd->subSecond(rand(10,600000));
          $action_sum = 
          $this->database->insert('buyers_leads_movements', array(
            'id' => null,
            'action_type' => null,
            'lead_id' => $i+1,
            'channel_id' => $channelId,
            'buyer_id' => $b->id,
            'invoice_id' => null,
            'posting_log_record_id' => null,
            'action_datetime' => $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
            'channel_action_datetime' => null,
            'action_sum' => -$tier,
            'lead_email' => '0x6fwhite@gmail.com',
            'lead_ssn' => '99999999',
            'lead_home_phone' => null,
            'lead_product' => $channels[$channelId]['product'],
            'is_v1_lead' => null,
            'syncId' => null,
          ));
          $leadsMovementsIds[] = $this->database->lastInsertId();
        }
        $addingsIds = array();
        for($i = 0; $i < 2; $i ++){
          $zd = new Zend_Date();
          $zd->subSecond(rand(10,600000));
          $action_sum = rand(-3, 3)*200.0;
          if($action_sum == 0)
            $action_sum = 100.0;
          $this->database->insert('buyers_invoices_addings', array(
            'id' => null,
            'invoice_id' => null,
            'buyer_id' => $b->id,
            'action_datetime' => $zd->toString(MYSQL_DATETIME_FORMAT_ZEND),
            'action_sum' => $action_sum,
            'comment' => "some comment on test buyer adding $i. \n\n some comment on test buyer adding $i.",
          ));
          $addingsIds[] = $this->database->lastInsertId();
        }


        T3System::setValue('test_buyer_generated', 1);
        T3System::setValue('test_buyer_data', array(
          'buyer_id' => $b->id,
          'channels_ids' => $channelsIds,
          'leads_sellings_ids' => $leadsSellingsIds,
          'leads_movements_ids' => $leadsMovementsIds,
          'invoices_addings_ids' => $addingsIds,
        ));



        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }

    }

    public function getInvoceTiers($invoiceId){

      return $this->database->fetchCol('
        select distinct action_sum from (
          select distinct action_sum from buyers_leads_sellings where invoice_id = ?
          union
          select distinct action_sum from buyers_leads_movements where invoice_id = ?
          union
          select distinct action_sum from buyers_invoices_addings where invoice_id = ?
        ) tmp order by action_sum
      ', array($invoiceId,$invoiceId,$invoiceId,));

    }

    public function getTestBuyerData(){
      if(!T3System::getValue('test_buyer_generated'))
        return array();

      return T3System::getValue('test_buyer_data');

    }

    public function getArchivedInvoices_Array(){
      return $this->database->fetchAll('
        select a.*, b.systemName as buyer_systemName from buyers_invoices a
        left join users_company_buyer b
        on a.buyer_id = b.id
        where a.archived
        order by period_beg
      ');
    }

    public function getBuyerNonHistoryInvoices_Array($buyerId){
     
      return $this->database->fetchAll("
        select * from buyers_invoices
        where buyer_id = ? and status != 'fully_paid'
        order by creation_datetime desc
      ", array($buyerId));
     
    }

    public function getNormalizedSuccessiveId($successiveId){
      return sprintf('%03d', $successiveId);
    }

    public function getLastInvoice($buyerId){
      $invoice = new T3Invoice();
      $data = $this->database->fetchRow('
        select *
        from buyers_invoices
        where buyer_id = ?
        order by creation_datetime desc limit 1
      ', array($buyerId));
      if(empty($data))
        return false;
      $invoice->fromArray($data);
      return $invoice;
    }

    public function getCurrentInvoiceSuccessiveIdForBuyer($buyerId){
      return $this->database->fetchOne('
        select successive_id
        from buyers_invoices
        where buyer_id = ?
        order by creation_datetime desc limit 1
      ', array($buyerId));
    }

    public function deleteTestBuyer(){

      if(!T3System::getValue('test_buyer_generated'))
        return;

      try{

        $data = T3System::getValue('test_buyer_data');
        if(empty($data))
          return;

        $this->database->beginTransaction();      

        $this->database->query('
          delete from users_company_buyer
          where id = ?
        ', array($data['buyer_id']));

        $this->database->query('
          delete from buyers_channels
          where buyer_id = ?
        ', array($data['buyer_id']));

        $this->database->query('
          delete from buyers_leads_sellings
          where buyer_id = ?
        ', array($data['buyer_id']));

        $this->database->query('
          delete from buyers_leads_movements
          where buyer_id = ?
        ', array($data['buyer_id']));

        $this->database->query('
          delete from buyers_invoices_addings
          where buyer_id = ?
        ', array($data['buyer_id']));

        $this->database->query('
          delete from buyers_invoices
          where buyer_id = ?
        ', array($data['buyer_id']));     

        T3System::setValue('test_buyer_generated', 0);

        $this->database->commit();

      }catch(Exception $e){
        $this->database->rollBack();
        throw $e;
      }

    }



    public function sendWeeklyOverduesMails(){

      define('CRON_JOB_OVERDUES', true);

      $data = $this->database->fetchAll('

        SELECT bi.*, bis.*, ucb.invoices_emails FROM buyers_invoices bi
        LEFT JOIN
        (SELECT invoice_id, MAX(sending_datetime) AS max_sending_datetime
        FROM buyers_invoices_sendings GROUP BY invoice_id) bis
        ON bi.id = bis.invoice_id
        LEFT JOIN users_company_buyer ucb ON bi.buyer_id = ucb.id
        WHERE !bi.fully_paid AND bi.was_overdue AND !archived
        AND TO_DAYS(DATE(?)) - TO_DAYS(DATE(max_sending_datetime)) >= 7
        AND !ucb.is_in_collections

      ', array(mySqlDateTimeFormat()));


      foreach($data as $v){


        $invoice = new T3Invoice();
        $invoice->fromArray($v);
        
        if($invoice->total_value<=0)
          continue;

        $invoice->sendEmailWithDefaultContents(true, $v['invoices_emails'], true);

      }

    }


    public function fillTestInvoicesData(){

      for($i = 0; $i<2000; $i++){

        $invoice = new T3Invoice();

        $this->productsAr = array('payday');

        $invoice->buyer_id = rand(1, 100);
        $invoice->successive_id = rand(1, 20);

        $zd = new Zend_Date();
        $zd->subHour(rand(10, 1200));
        $invoice->creation_datetime = $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);

        $zd = new Zend_Date();
        $zd->subHour(rand(10, 1200));
        $invoice->node_creation_datetime = $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);
        $invoice->period_beg = null;
        $invoice->period_end = null;
        $invoice->created_by_default = null;
        $invoice->STATUS = null;
        $invoice->sendings_number = null;
        $invoice->fully_paid = rand(0, 1);
        $invoice->was_overdue = rand(0, 1);
        $invoice->last_payment_datetime = null;
        $invoice->timely_limit = null;
        $invoice->total_value = null;
        $invoice->paid_sum = null;
        $invoice->leads_number = null;
        $invoice->movements_number = null;
        $invoice->addings_number = null;
        $invoice->products = null;
        $invoice->file_title = null;
        $invoice->pdf_file_name = null;
        $invoice->pdf_file_name_link = null;
        $invoice->unique_key = null;
        $invoice->archived = null;
        
        $invoice->insertIntoDatabase();

      }


    }

    public function getOverduesSummaryData($alsoNotPaid = false){

      $data = $this->database->fetchAll('
        
        SELECT tmp2.buyer_id, ucb.systemName AS buyer_systemName,

        SUM(tmp2.total_value_0) AS total_value_0,
        SUM(tmp2.total_value_1) AS total_value_1,
        SUM(tmp2.total_value_2) AS total_value_2,
        SUM(tmp2.total_value_3) AS total_value_3,

        SUM(tmp2.total_value_0)+
        SUM(tmp2.total_value_1)+
        SUM(tmp2.total_value_2)+
        SUM(tmp2.total_value_3) AS total_value

        FROM (

                SELECT buyer_id,
                IF(c=0, total_value, 0) AS total_value_0,
                IF(c=1, total_value, 0) AS total_value_1,
                IF(c=2, total_value, 0) AS total_value_2,
                IF(c=3, total_value, 0) AS total_value_3


                 FROM (

                SELECT buyer_id,
                 IF(TO_DAYS(DATE(NOW())) - TO_DAYS(DATE(period_end))<=90,
                 FLOOR((TO_DAYS(DATE(NOW())) - TO_DAYS(DATE(period_end))) / 30),
                 3) AS c, SUM(total_value-ifnull(paid_sum, 0)) AS total_value
                 FROM buyers_invoices WHERE ' . ($alsoNotPaid ? '' : ' was_overdue && ') . ' !fully_paid && !archived
                GROUP BY buyer_id, c) tmp1
        ) tmp2

        LEFT JOIN users_company_buyer AS ucb
        ON tmp2.buyer_id = ucb.id

        GROUP BY tmp2.buyer_id

        ORDER BY buyer_systemName

     ');

      $result = array(
        'data' => $data,
        'totalValues' => array(
          'total_value_0' => 0,
          'total_value_1' => 0,
          'total_value_2' => 0,
          'total_value_3' => 0,
        ),
        'total' => 0
      );

      foreach($data as $v){
        $result['totalValues']['total_value_0'] += $v['total_value_0'];
        $result['totalValues']['total_value_1'] += $v['total_value_1'];
        $result['totalValues']['total_value_2'] += $v['total_value_2'];
        $result['totalValues']['total_value_3'] += $v['total_value_3'];
      }

      $result['total'] =
        $result['totalValues']['total_value_0']+
        $result['totalValues']['total_value_1']+
        $result['totalValues']['total_value_2']+
        $result['totalValues']['total_value_3'];
      
      return $result;

    }

    public function getOverduesIdsByDaysValue($buyerId, $daysValue, $alsoNotPaid = false){

      $buyerCondition = !empty($buyerId) ? "buyer_id = " . (int)$buyerId : '1';

      return $this->database->fetchCol("
        SELECT id FROM buyers_invoices WHERE $buyerCondition && " . ($alsoNotPaid ? '' : ' was_overdue && ') . " !fully_paid && !archived &&
        IF(TO_DAYS(DATE(NOW())) - TO_DAYS(DATE(period_end))<=90,
        FLOOR((TO_DAYS(DATE(NOW())) - TO_DAYS(DATE(period_end))) / 30), 3) = ?
      ", array($daysValue));
      
    }


    public function deleteAdding($invoiceId, $addingId){

      $id = $this->database->fetchOne('
        select id from buyers_invoices_addings where id = ? && invoice_id = ? limit 1
      ', array($addingId, $invoiceId));

      if(empty($id))
        return false;

      $this->database->query('
        delete from buyers_invoices_addings where id = ? && invoice_id = ?
      ', array($addingId, $invoiceId));

      $invoice = new T3Invoice();
      $invoice->fromDatabase($invoiceId);
      T3Invoices::getInstance()->recalcInvoice($invoice);
      $invoice->writePdfFile(false, $_title, $_fileName);

      return true;

    }


    public function changeAdding($invoiceId, $addingId, $newSum, $newComment){

      $id = $this->database->fetchOne('
        select id from buyers_invoices_addings where id = ? && invoice_id = ? limit 1
      ', array($addingId, $invoiceId));

      if(empty($id))
        return false;

      $this->database->query('
        update buyers_invoices_addings
        set
          action_sum = ?, comment = ?
        where id = ? && invoice_id = ?
      ', array(
        $newSum, $newComment,
        $addingId, $invoiceId,
      ));

      $invoice = new T3Invoice();
      $invoice->fromDatabase($invoiceId);
      T3Invoices::getInstance()->recalcInvoice($invoice);
      $invoice->writePdfFile(false, $_title, $_fileName);

      return true;

    }


    public function createAdding($invoiceId, $newSum, $newComment){

      $invoice = new T3Invoice();
      if($invoice->fromDatabase($invoiceId) === false)
        return false;

      $adding = new T3Invoice_Adding();
      $adding->invoice_id = $invoiceId;
      $adding->buyer_id = $invoice->buyer_id;
      $adding->action_datetime = mySqlDateTimeFormat();
      $adding->action_sum = $newSum;
      $adding->comment = $newComment;
      $adding->insertIntoDatabase();

      T3Invoices::getInstance()->recalcInvoice($invoice);
      $invoice->writePdfFile(false, $_title, $_fileName);

      return true;

    }
    
    /**
    * Функиця архивирования пачки законченных инвойсов
    * Перемещает детальные данные (лиды, мувменты, эддинги) завершенных 3 или более месяца назад инвосов ы архивную таблицу
    */
    static public function archive(){
        ini_set("memory_limit", "2048M");
        set_time_limit(600);
        
        if(!T3Db::api()->fetchOne("select id from buyers_invoices_arh_log where `status` = 'run'")){
        
            $start = microtime(1);
            
            $log = array(                           
                'start'     => date('Y-m-d H:i:s'),
                'end'       => '',
                'runtime'   => '',
                'memory'    => '',
                'date'      => '',
                'count'     => '',
                'invoices'  => '',
                'sellings'  => '',
                'movements' => '',
                'addings'   => '',  
                'reason'    => '',
            );
            T3Db::api()->insert("buyers_invoices_arh_log", $log);
            $logID = T3Db::api()->lastInsertId();
            
            T3Db::api()->beginTransaction();
            try{
                // максимальное количесво обрабатываемых за 1 раз транзакций
                $log['count'] = 100;
                
                $log['date'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 3, date('d'), date('Y')));
                
                $arhIds = T3Db::api()->fetchCol(
                    "select id from buyers_invoices where `status`=? and last_payment_datetime < ? and `archive` = 0 limit {$log['count']}",
                    array(
                        'fully_paid',
                        $log['date']    
                    )
                );
                
                $log['invoices'] = count($arhIds);
                
                if($log['invoices']){
                    // Если есть инвойсы для архивирования   
                    $tables = array(
                        array('buyers_invoices_addings',    'buyers_invoices_arh_addings',          'invoice_id', 'addings'),
                        array('buyers_leads_movements',     'buyers_invoices_arh_leads_movements',  'invoice_id', 'movements'),
                        array('buyers_leads_sellings',      'buyers_invoices_arh_leads_sellings',   'invoice_id', 'sellings'),
                    ); 
                    
                    foreach($tables as $tbl){
                        $all = T3Db::api()->fetchAll("select * from `{$tbl[0]}` where `{$tbl[2]}` in (" . implode(",", $arhIds) . ")");
                        
                        $log[$tbl[3]] = count($all);
                        
                        if($log[$tbl[3]]){
                            // скопировать, удалить
                            T3Db::api()->insertMulty($tbl[1], array_keys($all[0]), $all);
                            T3Db::api()->delete($tbl[0], "`{$tbl[2]}` in (" . implode(",", $arhIds) . ")");  
                        } 
                    } 
                    
                    // пометить как заархивированный
                    T3Db::api()->update("buyers_invoices", array(
                        'archive' => '1'
                    ), "id in (" . implode(",", $arhIds) . ")");   
                } 
                
                $log['memory']  = memory_get_usage()/1024/1024;
                $log['end']     = date('Y-m-d H:i:s');
                $log['runtime'] = microtime(1) - $start;
                $log['status']  = 'good';
                
                T3Db::api()->update("buyers_invoices_arh_log", $log, "id={$logID}");
                T3Db::api()->commit();
            }
            catch(Exception $e){
                T3Db::api()->rollBack();  
                
                $log['status']  = 'error';
                $log['reason']  = $e->getMessage() . " (" . $e->getLine() . ")\r\n" . $e->getTraceAsString();
                
                T3Db::api()->update("buyers_invoices_arh_log", $log, "id={$logID}");   
                
                echo "Error: \r\n\r\n" . $log['reason']; 
            } 
        
        }
        else {
            echo "Process locked";
        }                         
    }

    /**
     * Получить массив баеров с максимальными и и средними суммами по инвойсам за последние X месяцев
     *
     * @return array
     */
    static public function getAverageTotal($months = 4, $buyer_id = null){
        $result = array();

        $all = T3Db::apiReplicant()->fetchAll(
            "SELECT buyer_id, `total_value` FROM `buyers_invoices` WHERE `creation_datetime` > ?",
            date('Y-m-d', mktime(0, 0, 0, date('m') - $months))
        );

        if(count($all)){
            $index = array();

            foreach($all as $el){
                if(!isset($index[$el['buyer_id']])) $index[$el['buyer_id']] = array();
                $index[$el['buyer_id']][] = $el['total_value'];
            }

            foreach($index as $k => $v){
                $max = 0;
                $ttl = 0;

                foreach($v as $sum){
                    $ttl+= $sum;
                    if($sum > $max) $max = $sum;
                }

                $result[] = array(
                    'buyer_id'  => $k,
                    'average'   => round($ttl / count($v)),
                    'maximum'   => round($max),
                    'count'     => count($v),
                );
            }
        }

        return $result;
    }

    /**
     * Получить среднюю информацию по одному баеру
     *
     * @return array
     */
    static public function getAverageTotalForBuyer($buyer_id, $months = 4){
        $result[] = array(
            'average'   => 0,
            'maximum'   => 0,
            'count'     => 0,
        );

        $all = T3Db::apiReplicant()->fetchCol(
            "SELECT `total_value` FROM `buyers_invoices` WHERE `creation_datetime` > ? and buyer_id=?",
            array(
                date('Y-m-d', mktime(0, 0, 0, date('m') - $months)),
                (int)$buyer_id
            )
        );

        if(count($all)){
            $max = 0;
            $ttl = 0;

            foreach($all as $sum){
                $ttl+= $sum;
                if($sum > $max) $max = $sum;
            }

            $result = array(
                'average'   => round($ttl / count($all)),
                'maximum'   => round($max),
                'count'     => count($all),
            );
        }

        return $result;
    }

    /**
     * Получить рекомендованный депозит по средним значениям по инвойсам
     *
     * @param $averageArray дангные из функций T3Invoices::getAverageTotalForBuyer(BuyerID) и T3Invoices::getAverageTotal()
     * @return string
     */
    static public function getRecommendedDeposit($averageArray){
        $recommended = 0;

        if(isset($averageArray['maximum']) && $averageArray['maximum'] > 0){
            /*
            $recommended = (int)(substr($averageArray['maximum'], 0, 1) + 1) . str_repeat(0, strlen((int)$averageArray['maximum']) - 1);
            if($recommended > $averageArray['maximum'] * 1.3){
                $recommended = (int)(substr($averageArray['maximum'], 0, 2) + 2) . str_repeat(0, strlen((int)$averageArray['maximum']) - 2);
            }
            */
            $recommended = (int)(0.8 * $averageArray['maximum']);
            if($recommended > 10){
                $recommended = (int)(substr($recommended, 0, 2) + 1) . str_repeat(0, strlen($recommended) - 2);
            }
        }

        return $recommended;
    }

    static protected $depositInvoicesNumbers = array();

    /**
     * Получить номер инвойса для баера
     *
     * @param $buyerID
     */
    static public function getNextInvoiceNumberForDeposit($buyerID, $num = false){
        if(!isset(self::$depositInvoicesNumbers[$buyerID])){
            self::$depositInvoicesNumbers[$buyerID] = T3Db::api()->fetchOne(
                "SELECT `security_deposit_invoice_number` FROM `users_company_buyer` WHERE `id`=?", (int)$buyerID
            );
            self::$depositInvoicesNumbers[$buyerID]++;
        }

        if($num) return self::$depositInvoicesNumbers[$buyerID];

        return $buyerID . "-" . self::$depositInvoicesNumbers[$buyerID];
    }

    /**
     * Увеличить номер инвойса для заданного баера
     *
     * @param $buyerID
     */
    static public function addInvoiceNumberForDeposit($buyerID){
        T3Db::api()->update("users_company_buyer", array(
            'security_deposit_invoice_number' => new Zend_Db_Expr("security_deposit_invoice_number+1")
        ), "id=" . (int)$buyerID);

        if(isset(self::$depositInvoicesNumbers[$buyerID])){
            self::$depositInvoicesNumbers[$buyerID]++;
        }
    }

    /**
     * Рендер инвойса в формате HTML
     *
     * @param $buyerID
     * @param $deposit_set
     * @param null $total
     * @return mixed|string
     */
    static public function getDepositAdd_HTML($buyerID, $deposit_set = null, $total = null, $deposit_balance = null){
        $text = file_get_contents(T3SYSTEM_CLASSES . "/T3Invoice/templates/deposit_plus.html");

        $i = T3Db::apiReplicant()->fetchRow(
            "SELECT `companyName`, `Country`, `State`, `City`, `ZIP`, `Address`, `security_deposit_balance`, `security_deposit_sum` " .
            "FROM `users_company_buyer` WHERE id=?", $buyerID
        );

        if($i){
            if(is_null($deposit_set))       $deposit_set        = round($i['security_deposit_sum'], 2);
            if(is_null($deposit_balance))   $deposit_balance    = round($i['security_deposit_balance'], 2);
            if(is_null($total))             $total              = round($deposit_set - $deposit_balance, 2);

            $params = array(
                'invoice_no'        => self::getNextInvoiceNumberForDeposit($buyerID),
                'company_name'      => $i['companyName'],
                'company_address'   => "{$i['Address']}, {$i['City']}, {$i['State']}, {$i['ZIP']}, {$i['Country']}",
                'date'              => date('M d Y'),
                'deposit_set'       => $deposit_set,
                'deposit_balance'   => $deposit_balance,
                'total'             => $total,
            );

            if(isset($params) && is_array($params) && count($params)){
                foreach($params as $name => $value){
                    $text = str_replace('{' . $name . '}', $value, $text);
                }
            }
        }

        return $text;
    }

    /**
     * Рендер инвойса в формате HTML
     *
     * @param $buyerID
     * @param $deposit_set
     * @param null $total
     * @return mixed|string
     */
    static public function getPartiallyPaid_HTML($buyerID, $payment_amount, $invoice_total = null, $deposit_balance = null){
        $text = file_get_contents(T3SYSTEM_CLASSES . "/T3Invoice/templates/deposit_partially_paid.html");

        $i = T3Db::apiReplicant()->fetchRow(
            "SELECT `companyName`, `Country`, `State`, `City`, `ZIP`, `Address`, `security_deposit_balance`, `security_deposit_sum` " .
            "FROM `users_company_buyer` WHERE id=?", $buyerID
        );

        if($i){
            $deposit_set        = round($i['security_deposit_sum'], 2);

            if(is_null($deposit_balance))   $deposit_balance    = round($i['security_deposit_balance'], 2);
            if(is_null($invoice_total))     $invoice_total      = round($deposit_set - $deposit_balance, 2);

            $params = array(
                'payment_amount'    => $payment_amount,
                'payment_date'      => date("M d, Y, H:i ") . "PST",

                'invoice_no'        => self::getNextInvoiceNumberForDeposit($buyerID),
                'company_name'      => $i['companyName'],
                'company_address'   => "{$i['Address']}, {$i['City']}, {$i['State']}, {$i['ZIP']}, {$i['Country']}",
                'date'              => date('M d Y'),
                'deposit_set'       => $deposit_set,
                'deposit_balance'   => $deposit_balance,
                'total'             => $invoice_total,
            );

            if(isset($params) && is_array($params) && count($params)){
                foreach($params as $name => $value){
                    $text = str_replace('{' . $name . '}', $value, $text);
                }
            }
        }

        return $text;
    }

    static protected function createPDF($text, $filename_html, $filename_pdf){
        file_put_contents($filename_html, $text);

        if($_SERVER['HTTP_HOST'] == 't3.lh'){
            $command = "xvfb-run -a -s \"-screen 0 640x480x16\" wkhtmltopdf {$filename_html} {$filename_pdf}";
        }
        else {
            $command = "wkhtmltopdf {$filename_html} {$filename_pdf}";
        }

        // we use proc_open with pipes to fetch error output
        $descriptors = array(
            2   => array('pipe','w'),
        );

        $process = proc_open($command, $descriptors, $pipes, null, null, array('bypass_shell' => true));
        $error = null;

        if(is_resource($process)) {
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $result = proc_close($process);

            if($result!==0){
                if (!file_exists($filename_pdf) || filesize($filename_pdf)===0){
                    $error = "Could not run command $command:\n" . $stderr;
                }
                else
                    $error = "Warning: an error occured while creating the PDF.\n" . $stderr;
            }
        }
        else {
            $error = "Could not run command $command";
        }

        //varExport($command);
        //varExport($result);
        //varExport($error);
        // unlink("{$filename}.html");

        return is_file($filename_pdf) ? $filename_pdf : null;
    }

    /**
     * Получить путь к файлу с PDF или null в случае неудачи
     *
     * @param $buyerID
     * @param $deposit_set
     * @return null|string
     */
    static public function getDepositAdd_PDF($buyerID, $deposit_set){
        $filename = "/tmp/deposit-invoice-" . self::getNextInvoiceNumberForDeposit($buyerID) . "-" . date('Y-m-d');

        return self::createPDF(
            self::getDepositAdd_HTML($buyerID, $deposit_set),
            "{$filename}.html",
            "{$filename}.pdf"
        );
    }

    static public function getPartiallyPaid_PDF($buyerID, $payment_amount, $invoice_total = null, $deposit_balance = null){
        $filename = "/tmp/deposit-invoice-" . self::getNextInvoiceNumberForDeposit($buyerID) . "-" . date('Y-m-d');

        return self::createPDF(
            self::getPartiallyPaid_HTML($buyerID, $payment_amount, $invoice_total, $deposit_balance),
            "{$filename}.html",
            "{$filename}.pdf"
        );
    }


    /*****************************************************************************************************/

    const securityDepositLogType_changeDeposit  = 1; // изменяеться размер депозита
    const securityDepositLogType_addPayment     = 2; // баер платит нам
    const securityDepositLogType_payToBuyer     = 3; // мы платим баеру

    static protected function securityDepositLog_Abstract(
        $type, $buyerID, $description = null, $invoice_num = null, $invoice_sum = null, $payment_value
    ){
        $i = T3Db::api()->fetchRow(
            "SELECT `security_deposit_sum`, `security_deposit_balance` FROM `users_company_buyer` WHERE id=?", $buyerID
        );

        T3Db::api()->insert("buyers_invoices_security_deposit_log", array(
            'buyer_id'      => $buyerID,
            'type'          => $type,
            'deposit_set'   => ifset($i['security_deposit_sum'], 0),
            'deposit_sum'   => ifset($i['security_deposit_balance'], 0),
            'description'   => $description,
            'user'          => T3Users::getCUser()->id,
            'invoice_num'   => $invoice_num,
            'invoice_sum'   => $invoice_sum,
            'payment_value' => $payment_value,
        ));
    }

    /**
     * Записать в лог информацию о изменнеие депозита
     * Использовать эту функцию после всех изменений с базой данных
     *
     * @param $buyerID
     * @param $description
     */
    static public function securityDepositLog_changeDeposit($buyerID, $description, $invoice_num = null, $invoice_sum = null){
        self::securityDepositLog_Abstract(
            self::securityDepositLogType_changeDeposit,
            $buyerID,
            $description,
            $invoice_num,
            $invoice_sum
        );
    }

    static public function securityDepositLog_PaymentIn($buyerID, $description, $payment_value, $invoice_num = null, $invoice_sum = null){
        self::securityDepositLog_Abstract(
            self::securityDepositLogType_addPayment,
            $buyerID,
            $description,
            $invoice_num,
            $invoice_sum,
            $payment_value
        );
    }
}

T3Invoices::getInstance();