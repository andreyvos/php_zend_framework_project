<?php
/*
* Р С™Р В»Р В°РЎРѓРЎРѓ Р пїЅР Р…Р Р†Р С•Р в„–РЎРѓР В° (Р РЋРЎвЂЎР ВµРЎвЂљР В° Р В·Р В° Р С—РЎР‚Р ВµР Т‘Р С•РЎРѓРЎвЂљР В°Р Р†Р В»Р ВµР Р…Р Р…РЎвЂ№Р Вµ РЎС“РЎРѓР В»РЎС“Р С–Р С‘)
* 
* Р В§Р ВµРЎР‚Р ВµР В· Р Р…Р ВµР С–Р С• Р С�Р С•Р Р…Р С• Р С—Р С•Р В»РЎС“РЎвЂЎР С‘РЎвЂљРЎРЉ Р С‘Р Р…РЎвЂћР С•РЎР‚Р С�Р В°РЎвЂ Р С‘РЎР‹ Р Т‘Р ВµРЎвЂљР В°Р В»РЎРЏРЎвЂ¦ РЎРѓРЎвЂЎР ВµРЎвЂљР В°:
*   1. Р Р…Р В° Р С•РЎРѓР Р…Р С•Р Р†Р В°Р Р…Р С‘Р С‘ РЎвЂЎР ВµР С–Р С• Р В±РЎвЂ№Р В» РЎРѓРЎвЂћР С•РЎР‚Р С�Р С‘РЎР‚Р С•Р Р†Р В°Р Р… РЎРѓРЎвЂЎР ВµРЎвЂљ (Р вЂќР В°Р Р…Р Р…РЎвЂ№Р Вµ Р С—Р С•Р В»РЎС“РЎвЂЎР В°РЎР‹РЎвЂљРЎРѓРЎРЏ Р С‘Р В· Р С•Р В±РЎР‰Р ВµР С”РЎвЂљР С•Р Р† Р С”Р В°Р Р…Р В°Р В»Р С•Р Р†, Р Т‘Р В»РЎРЏ Р С”Р С•РЎвЂљР С•РЎР‚РЎвЂ№РЎвЂ¦ РЎвЂћР С•РЎР‚Р С�Р С‘РЎР‚Р С•Р В°Р В»РЎРѓРЎРЏ Р С‘Р Р…Р Р†Р С•Р в„–РЎРѓ)
*   2. Р С”Р С•Р С–Р Т‘Р В° Р В±РЎвЂ№Р В» Р Р†РЎвЂ№РЎРѓРЎвЂљР В°Р Р†Р В»Р ВµР Р… 
*   3. Р Р…Р В° Р С”Р В°Р С”Р С•Р в„– РЎРѓРЎвЂљР В°Р Т‘Р С‘Р С‘ Р Р…Р В°РЎвЂ¦Р С•Р Т‘Р С‘РЎвЂљРЎРѓРЎРЏ РЎРѓР ВµР в„–РЎвЂЎР В°РЎРѓ
*   4. Р С”Р В°Р С” Р С•Р С—Р В»Р В°РЎвЂЎР С‘Р Р†Р В°Р В»РЎРѓРЎРЏ (Р С›Р В±РЎР‰Р ВµР С”РЎвЂљРЎвЂ№ Р С•Р С—Р В»Р В°РЎвЂљРЎвЂ№)
*   5. ...
* 
* Р СћР В°Р С”Р В¶Р Вµ РЎвЂЎР ВµРЎР‚Р ВµР В· Р Р…Р ВµР С–Р С• Р С�Р С•Р В¶Р Р…Р С• Р С—РЎР‚Р С•Р Р†Р С•Р Т‘Р С‘РЎвЂљРЎРЉ Р Т‘Р ВµРЎРѓРЎвЂљР Р†Р С‘РЎРЏ Р Р…Р В°Р Т‘ РЎРѓРЎвЂЎР ВµРЎвЂљР С•Р С�:
*   1. Р В Р ВµР С–Р С‘РЎРѓРЎвЂљРЎР‚Р С‘РЎР‚Р С•Р Р†Р В°РЎвЂљРЎРЉ Р С•Р С—Р В»Р В°РЎвЂљРЎС“
*   2. Р вЂ”Р В°Р С”РЎР‚РЎвЂ№Р Р†РЎвЂљРЎРЉ РЎРѓРЎвЂЎР ВµРЎвЂљ
*   3. ...
*/


if(CRON_JOB_RUN && defined('CRON_JOB_OVERDUES') && CRON_JOB_OVERDUES){
  class MyZend_View_Helper_Currency{

    protected static $currency;

      public function currency($value, $options = array()){
        if(!isset(self::$currency)){
          self::$currency = new Zend_Currency('en_US');
        }
        return self::$currency->toCurrency($value, $options);
      }
  }
}

  
TableDescription::addTable('buyers_invoices', array(
  'id',                       //  int(11)
  'buyer_id',                 //  int(11)
  'current_buyer_systemName',
  'current_buyer_companyName',
  'successive_id',            //  int(11)
  'creation_datetime',        //  datetime
  'node_creation_datetime',
  'period_beg',               //  datetime
  'period_end',               //  datetime
  'created_by_default',       //  tinyint(1)
  'status',                   //  enum('was_overdue','not_paid','fully_paid')
  'sendings_number',          //  int(11)
  'fully_paid',               //  tinyint(1)
  'was_overdue',              //  tinyint(1)
  'last_payment_datetime',    //  datetime
  'timely_limit',             //  datetime
  'total_value',              //  decimal(10,2)
  'paid_sum',                 //  decimal(10,2)
  'leads_number',             //  int(11)
  'movements_number',         //  int(11)
  'products',                 //  varchar(255)
  'file_title',
  'pdf_file_name',            //  text
  'pdf_file_name_link',       //  text
  'unique_key',               //  varchar(250)
  'archived',
  'is_custom',
  'currency',
  'custom_invoice_data',
  'is_average_lead_price',
  'template',
));

class T3Invoice extends DbSerializable {

  const STATUS_FUNCTION = "IF(fully_paid,'fully_paid',IF(was_overdue, 'was_overdue', 'not_paid'))";
  const DYNAMIC_PRICE_LIMIT = 5;
  const DYNAMIC_PRICE_TITLE = 'Dynamic Price';

  public $id;
  public $buyer_id;
  public $current_buyer_systemName;
  public $current_buyer_companyName;
  public $successive_id;
  public $creation_datetime;
  public $node_creation_datetime;
  public $period_beg;
  public $period_end;
  public $created_by_default = 1;
  public $status;
  public $sendings_number = 0;
  public $fully_paid = 0;
  public $was_overdue = 0;
  public $last_payment_datetime;
  public $timely_limit;
  public $total_value;
  public $paid_sum = 0;
  public $leads_number = 0;
  public $movements_number = 0;
  public $addings_number = 0;
  public $products;
  public $file_title;
  public $pdf_file_name;
  public $pdf_file_name_link;
  public $unique_key;
  public $archived = 0;
  public $is_custom = 0;
  public $currency = 'USD';
  public $custom_invoice_data;
  public $is_average_lead_price = 0;
  public $template = T3Invoices::INVOICE_TEMPLATE_STANDARD;



  protected $actualSellingsIds;
  protected $actualMovementsIds;
  protected $actualAddingsIds;

  protected $content;

  protected $channels;
  protected $payments;
  protected $sendings;

  public $productsAr;

  public $buyer;


  public $periodType;
  public $period;

  public function GetCustomInvoiceData(){
    /*if(!$this->is_custom)
      return false;*/
    $result = unserialize($this->custom_invoice_data);
    if(empty($result))
      return array();
    return $result;
  }

  public function SetCustomInvoiceData($value){
    $this->custom_invoice_data = serialize($value);
  }

  public function __construct($id = null) {

    parent::__construct();

    $this->tables = array('buyers_invoices');

    $this->id = $id;

    $this->unique_key = randomString(40);

  }

  public function isEmpty(){
    return $this->total_value == 0;
  }

  public function getBuyer($lazy = true){
    if($lazy && $this->buyer !== null)
      return $this->buyer;

    $this->buyer = new T3BuyerCompany();
    $this->buyer->fromDatabase($this->buyer_id);

    return $this->buyer;

  }

  public function getPeriodEndMinusDay(){
    $zd = new Zend_Date();
    $zd->set($this->period_end, MYSQL_DATETIME_FORMAT_ZEND);
    $zd->subDay(1);
    return $zd->toString(MYSQL_DATETIME_FORMAT_ZEND);
  }

  public function getChannels($lazy = true){

    if($lazy && $this->channels !== null)
      return $this->channels;

    if($this->existsInDatabase){

      $ar = $this->database->fetchAll('
        SELECT *
        FROM buyers_invoices_channels
        WHERE invoice_id = ?
      ', array($this->id));

    }else{

      $ar = $this->database->fetchAll(
        $this->getChannelsSelectQuery()
      , array($this->id));

    }

    $this->channels = array();
    foreach($ar as $k => $v){
      $obj = new T3Invoice_Channel();
      $obj->fromArray($v);
      $this->channels[$k] = $obj;
    }

    return $this->channels;

  }

  public function getPayments($lazy = true){

    if($lazy && $this->payments !== null)
      return $this->payments;

    $ar = $this->database->fetchAll('
      SELECT *
      FROM buyers_invoices_payments
      WHERE invoice_id = ?
      ORDER BY pay_datetime
    ', array($this->id));

    $this->payments = array();
    foreach($ar as $k => $v){
      $obj = new T3Invoice_Payment();
      $obj->fromArray($v);
      $this->payments[$k] = $obj;
    }

    return $this->payments;

  }

  public function getProducts($lazy = true){
    
    if($lazy && $this->productsAr !== null){
      return $this->productsAr;
    }

    $quotedList1 = dbQuote($this->database, $this->getActualSellingsIds());
    $quotedList2 = dbQuote($this->database, $this->getActualMovementsIds());
    $quotedList1Cond = ($quotedList1===false ? "0" : "id IN ($quotedList1)");
    $quotedList2Cond = ($quotedList2===false ? "0" : "id IN ($quotedList2)");

    return $this->productsAr = $this->database->fetchCol("
      select lead_product from buyers_leads_sellings where $quotedList1Cond
      union 
      select lead_product from buyers_leads_movements where $quotedList2Cond
    ");
    
  }

  public function getSendings($lazy = true){

    if($lazy && $this->sendings !== null)
      return $this->sendings;

    $ar = $this->database->fetchAll('
      SELECT *
      FROM buyers_invoices_sendings
      WHERE invoice_id = ?
    ', array($this->id));

    $this->sendings = array();
    foreach($ar as $k => $v){
      $obj = new T3Invoice_Sending();
      $obj->fromArray($v);
      $this->sendings[$k] = $obj;
    }

    return $this->sendings;

  }


  public function getStructuredContent($buyersTime = true){

    $b = $this->getBuyer();

    $productsMap = T3Products::getProducts_MiniArray();
    $productsTitlesArray = array();

    foreach(array_unique($this->productsAr) as $v1)
      $productsTitlesArray[] = $productsMap[$v1];
    $productsTexts = implode(', ', $productsTitlesArray);


    $date1 = DateFormat::dateOnly($this->period_beg);
    $date2 = DateFormat::dateOnly($this->getPeriodEndMinusDay());

    $items = array();

    $sum = 0;
    $sum_total_num_leads  = 0;
    $sum_total_num_credits  = 0;
    $sum_gross_amount = 0;
    $sum_credit_amount = 0;
    $sum_net_amount = 0;


    
    $addingsData = $this->database->fetchAll('
      select *
      from buyers_invoices_addings
      where invoice_id = ?
    ', array($this->id));

    $addings = array();
    $addings_total_amount = 0;
    foreach($addingsData as $v){

      $addings[] = array(
        'date' => DateFormat::dateOnly($v['action_datetime']),
        'comment' => $v['comment'],
        'action_sum' => simpleCurrency($v['action_sum'], $this->currency),
      );

      $sum += $v['action_sum'];
      $addings_total_amount += $v['action_sum'];

    }


    $paysData = $this->database->fetchAll("
      select pay_datetime, pay_sum from buyers_invoices_payments
      where invoice_id = ?
      order by pay_datetime
    ", array($this->id));
    $paysFormattedData = array();
    foreach($paysData as $v){
      $paysFormattedData[] = array(
        'date' => DateFormat::dateOnly($v['pay_datetime']),
        'sum' => simpleCurrency($v['pay_sum'], $this->currency),
      );
    }

    if(!$this->is_custom){

      $ar = $this->database->fetchAll('
        SELECT
          if(bc.title is not null, bc.title, channel_id) as channel_title,
          timezone,
          action_sum,
          count(sel_type) as sel_c,
          count(mov_type) as mov_c,
          sum(sel_action_sum) as sel_sum,
          sum(mov_action_sum) as mov_sum,
          sum(sel_action_sum-mov_action_sum) as all_sum
        FROM (
          SELECT
            invoice_id,
            channel_id,
            action_sum as sel_action_sum,
            0 as mov_action_sum,
            action_sum, 1 as sel_type,
            null as mov_type
          FROM buyers_leads_sellings
          WHERE invoice_id = ?
          UNION ALL
          SELECT
            invoice_id,
            channel_id,
            0 as sel_action_sum,
            -action_sum as mov_action_sum,
            -action_sum as action_sum,
            null as sel_type,
            1 as mov_type
          FROM buyers_leads_movements
          WHERE invoice_id = ?
        ) AS tmp1
        LEFT JOIN buyers_channels AS bc
        ON tmp1.channel_id = bc.id
        WHERE invoice_id = ?
        GROUP BY channel_id, action_sum
        ORDER BY channel_id, action_sum DESC
      ', array($this->id, $this->id, $this->id));

      $renderable = new Renderable();

      if(!empty($ar)){

        $ar = groupBy($ar, 'channel_title');

        foreach($ar as $channelTitle => &$content){

          $aa = reset($content);
          $channelTitleTz = "$channelTitle ({$aa['timezone']})";

          if(isset($items[$channelTitleTz]))
            $channelTitleTz .= ' ';

          $items[$channelTitleTz] = array();
          $ar2 = & $items[$channelTitleTz];

          if(count($content) > self::DYNAMIC_PRICE_LIMIT){

            $dynamicTotalNumLeads = 0;
            $dynamicTotalNumCredits = 0;
            $dynamicGrossAmount = 0;
            $dynamicCreditsAmount = 0;
            $dynamicNetAmount = 0;

            $minDynamicPrice = null;
            $maxDynamicPrice = null;

            foreach($content as $k => &$v){

              if($minDynamicPrice === null || $v['action_sum']<$minDynamicPrice)
                $minDynamicPrice = $v['action_sum'];

              if($maxDynamicPrice === null || $v['action_sum']>$maxDynamicPrice)
                $maxDynamicPrice = $v['action_sum'];

              $dynamicTotalNumLeads += $v['sel_c'];
              $dynamicTotalNumCredits += $v['mov_c'];
              $dynamicGrossAmount += $v['sel_sum'];
              $dynamicCreditsAmount += $v['mov_sum'];
              $dynamicNetAmount += $v['all_sum'];

              $sum += $v['all_sum'];
              $sum_total_num_leads += $v['sel_c'];
              $sum_total_num_credits += $v['mov_c'];
              $sum_gross_amount += $v['sel_sum'];
              $sum_credit_amount += $v['mov_sum'];
              $sum_net_amount += $v['all_sum'];

            }

            $ar2[] = array(
              'tiers' => "Dynamic: " .
                simpleCurrency($minDynamicPrice, $this->currency) . ' - ' .
                simpleCurrency($maxDynamicPrice, $this->currency),
              'total_num_leads' => $dynamicTotalNumLeads,
              'total_num_credits'	=> $dynamicTotalNumCredits,
              'gross_amount' => $dynamicGrossAmount,
              'credit_amount' => $dynamicCreditsAmount,
              'net_amount' => $dynamicNetAmount,
            );

          }else{

            $i = 1;
            foreach($content as $k => &$v){

              $ar2[] = array(
                'tiers' => "Tier $i: " . simpleCurrency($v['action_sum'], $this->currency),
                'total_num_leads' => $v['sel_c'],
                'total_num_credits'	=> $v['mov_c'],
                'gross_amount' => simpleCurrency($v['sel_sum'], $this->currency),
                'credit_amount' => simpleCurrency($v['mov_sum'], $this->currency),
                'net_amount' => simpleCurrency($v['all_sum'], $this->currency),
              );
              $i++;

              $sum += $v['all_sum'];
              $sum_total_num_leads += $v['sel_c'];
              $sum_total_num_credits += $v['mov_c'];
              $sum_gross_amount += $v['sel_sum'];
              $sum_credit_amount += $v['mov_sum'];
              $sum_net_amount += $v['all_sum'];

            }

          }

        }

      }


    }


    
    $customInvoiceData = $this->GetCustomInvoiceData();

    if(!empty($customInvoiceData)){

      $channelsIds = array();
      foreach($customInvoiceData as $v){
        $channelsIds[] = (int)($v['channel']);
      }

      if(!empty($channelsIds)){

        $channelsIdsString = implode(', ', $channelsIds);

        $channelsDataRaw = $this->database->fetchAll("
          select id, title, timezone from buyers_channels where id in ($channelsIdsString)
        ");

      }else{

        $channelsDataRaw = array();

      }

      $channelsData = array();
      foreach($channelsDataRaw as $v){
        $channelsData[$v['id']] = $v;
      }


      foreach($customInvoiceData as $v){

        $channelTitle = $channelsData[$v['channel']]['title'];
        $channelTimezone = $channelsData[$v['channel']]['timezone'];

        $titleTz = "$channelTitle ($channelTimezone)";

        if(!isset($items[$titleTz]))
          $items[$titleTz] = array();

        switch($this->template){

          case T3Invoices::INVOICE_TEMPLATE_STANDARD:

            $currentTierNumber = count($items[$titleTz]) + 1;

            $items[$titleTz][] = array(
              'tiers' => "Tier $currentTierNumber: " . simpleCurrency($v['tier'], $this->currency, true),
              'total_num_leads' => $v['leads_count'],
              'total_num_credits' => $v['credits_count'],
              'gross_amount' => simpleCurrency($v['leads_count']*$v['tier'], $this->currency),
              'credit_amount' => simpleCurrency($v['credits_count']*$v['tier'], $this->currency),
              'net_amount' => simpleCurrency(($v['leads_count']-$v['credits_count'])*$v['tier'], $this->currency),
            );

            $sum_gross_amount += $v['leads_count']*$v['tier'];
            $sum_credit_amount += $v['credits_count']*$v['tier'];
            $sum_net_amount += ($v['leads_count']-$v['credits_count'])*$v['tier'];
            $sum += ($v['leads_count']-$v['credits_count'])*$v['tier'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_GROSS_FACTOR_NET:

            $items[$titleTz][] = array(
              'tiers' => "",
              'gross_amount' => simpleCurrency($v['gross'], $this->currency),
              'factor' => (int)($v['factor'] * 100) . '%',
              'net_amount' => simpleCurrency($v['gross']*$v['factor'], $this->currency),
            );

            $sum_gross_amount += $v['gross'];
            $sum_net_amount += $v['gross']*$v['factor'];
            $sum += $v['gross']*$v['factor'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_RECORDS_PRICE_NET:

            $items[$titleTz][] = array(
              //'tiers' => "",
              'price' => simpleCurrency($v['tier'], $this->currency, true),
              'total_num_leads' => $v['leads_count'],
              'gross_amount' => simpleCurrency($v['leads_count']*$v['tier'], $this->currency),
              'net_amount' => simpleCurrency($v['leads_count']*$v['tier'], $this->currency),
            );

            $sum_gross_amount += $v['leads_count'] * $v['tier'];
            $sum_net_amount += $v['leads_count'] * $v['tier'];
            $sum += $v['leads_count'] * $v['tier'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_LEADS_COST_NET:

            $items[$titleTz][] = array(
              'tiers' => "",
              'cost' => simpleCurrency($v['tier'], $this->currency, true),
              'total_num_leads' => $v['leads_count'],
              'total_num_credits' => $v['credits_count'],
              'gross_amount' => simpleCurrency($v['leads_count']*$v['tier'], $this->currency),
              'credit_amount' => simpleCurrency($v['credits_count']*$v['tier'], $this->currency),
              'net_amount' => simpleCurrency(($v['leads_count']-$v['credits_count'])*$v['tier'], $this->currency),
            );

            $sum_gross_amount += $v['leads_count']*$v['tier'];
            $sum_credit_amount += $v['credits_count']*$v['tier'];
            $sum_net_amount += ($v['leads_count']-$v['credits_count'])*$v['tier'];
            $sum += ($v['leads_count']-$v['credits_count'])*$v['tier'];


            break;

          case T3Invoices::INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE:

            $items[$titleTz][] = array(
              'tiers' => "Variable lead price",
              'total_num_leads' => $v['leads_count'],
              'total_num_credits' => $v['credits_count'],
              'gross_amount' => simpleCurrency($v['leads_cost'], $this->currency),
              'credit_amount' => simpleCurrency($v['credits_cost'], $this->currency),
              'net_amount' => simpleCurrency($v['leads_cost']-$v['credits_cost'], $this->currency),
            );

            $sum_gross_amount += $v['leads_cost'];
            $sum_credit_amount += $v['credits_count'];
            $sum_net_amount += $v['leads_cost']-$v['credits_cost'];
            $sum += $v['leads_cost']-$v['credits_cost'];


            break;

        }

        $sum_total_num_leads += $v['leads_count'];
        $sum_total_num_credits += $v['credits_count'];


      }

    }

    $headData = array(
      'id'            => $this->getNormalizedSuccessiveId(),
      'product'       => $productsTexts,
      'date'          => "$date1 - $date2",
      'company_name'  => $this->current_buyer_companyName,
      'company_address' => "{$b->Address} {$b->City} {$b->State} {$b->ZIP}",
      'total'         => simpleCurrency($sum, $this->currency),
      'paid_sum'      => simpleCurrency($this->paid_sum, $this->currency),
      'remains_to_pay'=> simpleCurrency($this->total_value-$this->paid_sum, $this->currency),
      'table_data'    => array('items' => $items),
      'sum_total_num_leads' => $sum_total_num_leads,
      'sum_total_num_credits' => $sum_total_num_credits,
      'sum_gross_amount' => simpleCurrency($sum_gross_amount, $this->currency),
      'sum_credit_amount' => simpleCurrency($sum_credit_amount, $this->currency),
      'sum_net_amount' => simpleCurrency($sum_net_amount, $this->currency),
      'due_date' => DateFormat::dateOnly($this->timely_limit),
      'addings' => $addings,
      'addings_total_amount' => simpleCurrency($addings_total_amount, $this->currency),
      'pay_arr' => $paysFormattedData,
      'currency' => $this->currency,
    );

    return $headData;

  }

  public function changeSuccessiveId($newId){
    if($this->successive_id == $newId)
      return;

    try{

      $this->database->beginTransaction();

      $this->successive_id = $newId;
      $this->database->query('
        update buyers_invoices
        set successive_id = ?
        where id = ?
      ', array($this->successive_id, $this->id));
      $this->database->query('
        update users_company_buyer
        set invoices_last_num = (
        select max(successive_id)
        from buyers_invoices
        where buyer_id = ?)
        where id = ?
      ', array($this->buyer_id, $this->buyer_id));

      $this->writePdfFile(false, $_title, $_fileName);

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
    }


  }

  public function getHtmlDocumentLink(){
    return T3Invoices::getInstance()->getInvoiceHtmlDocumentLink($this->id, $this->unique_key);
  }

  public function writePdfFile($forSending, &$_title, &$_fileName){

    $d1 = date("mdY", strtotime($this->period_beg));
    $d2 = date("mdY", strtotime($this->getPeriodEndMinusDay()));

    $normalizedSuccessiveId = $this->getNormalizedSuccessiveId();


    $title = "T3Leads_Invoice_Num{$normalizedSuccessiveId}_{$d1}-{$d2}";
    if(!$forSending){
      //$fileName = "T3Leads_Invoice_Num{$normalizedSuccessiveId}_{$d1}-{$d2}.pdf";
      $fileName = "invoice_id_{$this->id}.pdf";
    }else{
      //$fileName = "T3Leads_Invoice_Num{$this->id}_{$d1}-{$d2}.pdf";
      $fileName = "invoice_id_{$this->id}_sending_{$this->sendings_number}.pdf";
    }

    $this->file_title = $title;
    $this->pdf_file_name = T3Invoices::DIRECTORY . DS . $fileName;
    $this->pdf_file_name_link =  T3Invoices::getInstance()->getInvoiceFileLink($this, false);

    if(!(CRON_JOB_RUN && defined('CRON_JOB_OVERDUES') && CRON_JOB_OVERDUES)){
      $fileName = T3Invoices::DIRECTORY . DS . $fileName;
    }else{
      $fileName = T3Invoices::DIRECTORY_CRON_JOB_OVERDUES . DS . $fileName;
    }
    
    if(!$forSending){
      $this->database->query("
        UPDATE buyers_invoices
        SET
          file_title = ?,
          pdf_file_name = ?,
          pdf_file_name_link = ?
        WHERE
          id = ?
      ", array($this->file_title, $this->pdf_file_name, $this->pdf_file_name_link, $this->id));    
    }

    if(is_file($fileName))
      unlink($fileName);


    switch($this->template){

      case T3Invoices::INVOICE_TEMPLATE_STANDARD:

        if($this->currency == 'GBP'){
          $doc = new SenZend_Pdf_InvoiceUk();
        }else{
          $doc = new SenZend_Pdf_Invoice();
        }

        break;

      case T3Invoices::INVOICE_TEMPLATE_GROSS_FACTOR_NET:

        $doc = new SenZend_Pdf_InvoiceTypeC();

        break;

      case T3Invoices::INVOICE_TEMPLATE_RECORDS_PRICE_NET:

        $doc = new SenZend_Pdf_InvoiceTypeB();

        break;

      case T3Invoices::INVOICE_TEMPLATE_LEADS_COST_NET:

        $doc = new SenZend_Pdf_InvoiceUkTypeB();

        break;

      case T3Invoices::INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE:

        $doc = new SenZend_Pdf_Invoice();

        break;
      
    }
    


    $doc->setInvoiceData($this->getStructuredContent());

    $doc->writeData();

    $fileName = $doc->saveTofile($fileName, true);

    $_title = $title;
    $_fileName = $fileName;
    
  }

  public function sendEmailWithDefaultContents($automatic, $emails, $isReminding){

    return $this->sendEmail($automatic, $emails, $isReminding,
      $isReminding ?
        $this->getRemindingEmailSubject() :
        $this->getSimpleEmailSubject(),
      $isReminding ?
        $this->getRemindingEmailText() :
        $this->getSimpleEmailText()
    );

  }



  public function getSimpleEmailSubject(){
    return 'T3Leads Invoice Num ' . $this->getNormalizedSuccessiveId();
  }

  public function getRemindingEmailSubject(){
    return 'Reminder Letter - T3Leads Invoice Num ' . $this->getNormalizedSuccessiveId();
  }

  public function getProductsTitlesString(){

    if(empty($this->productsAr))
      return '';

    $productsString = dbQuote($this->database, $this->productsAr);

    return implode(', ',$this->database->fetchCol("
      select title from leads_type where name in ($productsString)
    "));

  }

  public function getSimpleEmailText(){
    $messageObj = new T3Mail_Message();
    $messageObj->loadMessage('invoice');
    return $messageObj->templateMessage->render(array (
      'Invoice_Number' => $this->getNormalizedSuccessiveId(),
      'invoice_link' => $this->getHtmlDocumentLink(),
      'products' => $this->getProductsTitlesString(),
    ));

  }

  public function getRemindingEmailText(){

    $messageObj = new T3Mail_Message();
    $messageObj->loadMessage('invoice_reminder');
    return $messageObj->templateMessage->render(array (
      'Invoice_Num' => $this->getNormalizedSuccessiveId(),
      'invoice_link' => $this->getHtmlDocumentLink(),
      'products' => $this->getProductsTitlesString(),
    ));

  }

  public function sendEmail($automatic, $emails, $isReminding, $subject, $text){


    if($this->isEmpty())return;

    try{

      $this->database->beginTransaction();

      $core = T3Invoices::getInstance();
      $b = $this->getBuyer();

      if(!is_array($emails))
        $emails = EmailsParser::toArray($emails);

      $bccEmails = T3Invoices::getInstance()->getAccountingEmails();


      /*$emailData = array(
        'Invoice_Number' => $this->getNormalizedSuccessiveId(),
        'invoice_link' => $this->getHtmlDocumentLink(),
      );*/

      $messageObj = new T3Mail_Message();
      $messageObj->loadMessage('invoice');
      $messageObj->templateMessage->subject = $subject;
      $messageObj->templateMessage->text = $text;
      //$messageObj->setMessageParams($emailData);
      $messageObj->renderMessage();

      $title = '';
      $fileName = '';
      $this->writePdfFile(true, $title, $fileName);



      //$prettyFileName = T3Invoices::DIRECTORY . DS . $title . '.pdf';

      if(!(CRON_JOB_RUN && defined('CRON_JOB_OVERDUES') && CRON_JOB_OVERDUES)){
        $prettyFileName = T3Invoices::DIRECTORY . DS . $title . '.pdf';
      }else{
        $prettyFileName = T3Invoices::DIRECTORY_CRON_JOB_OVERDUES . DS . $title . '.pdf';
      }


      copy($fileName, $prettyFileName);

      $messageObj->addAttachments(array($prettyFileName));

      /*if(CRON_JOB_RUN && defined('CRON_JOB_OVERDUES') && CRON_JOB_OVERDUES){
        $emails = array('0x6fwhite@gmail.com');
        $bccEmails = array('0x6fwhite@gmail.com');      
      }*/

      if(!empty($emails))
        $messageObj->addToArray($emails);

      if(!empty($bccEmails))
        $messageObj->addBccArray($bccEmails);


      /*vvv(array(
        'email sending',
        $emails,$bccEmails,$subject,$text,
        array($title,$fileName)
      ));*/

      $messageObj->SendMail();



      $sending = new T3Invoice_Sending();
      $sending->invoice_id = $this->id;
      $sending->buyer_id = $this->buyer_id;
      $sending->author_id = T3Users::getInstance()->getCurrentUserId();
      $sending->is_reminding = (int)$isReminding;
      $sending->automatic = (int)$automatic;
      $sending->sending_datetime = mySqlDateTimeFormat();
      $sending->email = implode(', ', array_merge($emails, $bccEmails));
      $sending->sending_text = $messageObj->messageText;
      $sending->attached_file_title = $title;
      $sending->attached_file_name = $fileName;
      $sending->attached_file_name_link = T3Invoices::getInstance()->getInvoiceFileLink($this, true);
      $sending->insertIntoDatabase();

      $this->database->query('
        UPDATE buyers_invoices
        SET sendings_number = sendings_number + 1
        WHERE id = ?
      ', array($this->id));

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
    }

  }

  public function payFull($payDateTime){
    return $this->pay($this->total_value - $this->paid_sum, $payDateTime);
  }

  public function pay($sum, $payDateTime){

    if(empty($this->id)){return false;}// throw new Exception('Not Implemented');


    if($this->fully_paid){return false; throw new Exception('Not Implemented');}

    $remain = $this->total_value-$this->paid_sum;

    if($remain<=0){return false; throw new Exception('Not Implemented');}

    if($remain-$sum<0){return false; throw new Exception('Not Implemented');}

    try{

      $this->database->beginTransaction();
      $this->database->insert('buyers_invoices_payments', array(
        'invoice_id' => $this->id,
        'pay_datetime' => $payDateTime ,
        'pay_sum' => $sum,
        'comment' => '',// TODO
      ));
      
      $this->paid_sum += $sum;
      $this->fully_paid = (int)($this->paid_sum >= $this->total_value);
      $this->last_payment_datetime = $payDateTime;

      $this->saveToDatabase();

      $this->database->query('
        UPDATE users_company_buyer
        SET balance = balance + ?
        WHERE id = ?
      ', array($sum, $this->buyer_id));

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
    }

  }

  public function makeOverdue(){
    $this->was_overdue = 1;
    //$this->sendEmailWithDefaultContents(true, $this->getBuyer()->invoices_emails, true);
    $this->database->query("
      UPDATE buyers_invoices
      SET 
        was_overdue = 1,
        status = 'was_overdue'
      WHERE id = ?
    ", array($this->id));
  }

  public function toArray($tables = null){
    $this->status = $this->fully_paid ? 'fully_paid' : ($this->was_overdue ? 'was_overdue' : 'not_paid');
    $this->products = implode(", ", $this->productsAr);
    return parent::toArray($tables);
  }

  public function getNormalizedSuccessiveId(){
    return T3Invoices::getInstance()->getNormalizedSuccessiveId($this->successive_id);
  }

  public function fromArray(&$array){

    parent::fromArray($array);

    $this->productsAr = explode(", ", $this->products);
    
  }

  public function InitializeCustomInvoice(
    $buyerId,
    $startDate,
    $finishDate, 
    $timelyLimit,
    $currency,
    $invoiceNumber,
    $customInvoiceData,
    $invoiceTemplate
  ){

    $this->is_custom = 1;

    $this->buyer_id = $buyerId;

    $buyer = new T3BuyerCompany();
    if($buyer->fromDatabase($buyerId) !== false){
      $this->current_buyer_systemName = $buyer->systemName;
      $this->current_buyer_companyName = $buyer->companyName;
    }

    $this->period_beg = $startDate;
    $this->period_end = $finishDate;

    $this->currency = $currency;
    $this->successive_id = $invoiceNumber;

    $this->SetCustomInvoiceData($customInvoiceData);

    $this->creation_datetime = mySqlDateTimeFormat();

    $this->node_creation_datetime = mySqlDateTimeFormat();

    $this->created_by_default = 0;

    $this->status = 'not_paid';

    $this->timely_limit = $timelyLimit;

    $this->total_value = 0;
    $this->leads_number = 0;
    $this->movements_number = 0;

    $this->is_average_lead_price = (int)($invoiceTemplate == T3Invoices::INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE);

    $this->template = $invoiceTemplate;

    $channelsIds = array();

    foreach($customInvoiceData as $v){

      $channelsIds[] = (int)($v['channel']);

      switch($invoiceTemplate){

        case T3Invoices::INVOICE_TEMPLATE_STANDARD:

          $this->total_value += ($v['leads_count'] - $v['credits_count']) * $v['tier'];
          $this->leads_number += $v['leads_count'];
          $this->movements_number += $v['credits_count'];

          break;

        case T3Invoices::INVOICE_TEMPLATE_GROSS_FACTOR_NET:

          $this->total_value += $v['gross'] * $v['factor'];

          break;

        case T3Invoices::INVOICE_TEMPLATE_RECORDS_PRICE_NET:

          $this->total_value += $v['leads_count'] * $v['tier'];
          $this->leads_number += $v['leads_count'];

          break;

        case T3Invoices::INVOICE_TEMPLATE_LEADS_COST_NET:

          $this->total_value += ($v['leads_count'] - $v['credits_count']) * $v['tier'];
          $this->leads_number += $v['leads_count'];
          $this->movements_number += $v['credits_count'];

          break;

        case T3Invoices::INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE:

          $this->total_value += $v['leads_cost'] - $v['credits_cost'];
          $this->leads_number += $v['leads_count'];
          $this->movements_number += $v['credits_count'];

          break;

      }

    }


    if(!empty($channelsIds)){
      $channelsIdsString = implode(', ', $channelsIds);
      $products = $this->database->fetchCol("
        select distinct product from buyers_channels where id in ($channelsIdsString)
      ");
    }else{
      $products = array();
    }

    $this->productsAr = array_unique($products);

  }

  public function initializeByPreviousInvoice($buyerId, $timelyLimitDays, $periodEnd = null){

    if(empty($timelyLimitDays))
      return false;

    $this->buyer_id = $buyerId;

    $buyer = new T3BuyerCompany();
    if($buyer->fromDatabase($buyerId) !== false){
      $this->current_buyer_systemName = $buyer->systemName;
      $this->current_buyer_companyName = $buyer->companyName;
    }

    $this->node_creation_datetime = T3Invoices::getInstance()->getInvoiceNextDefaultForBuyer($this->getBuyer());

    $lastInvoicePeriodEnd = T3Invoices::getInstance()->getLastInvoiceDateTime($this->getBuyer());

    if(empty($lastInvoicePeriodEnd)){
      return false;
    }

    $date1 = new Zend_Date($lastInvoicePeriodEnd, MYSQL_DATETIME_FORMAT_ZEND);
    $date1->addSecond(1);
    $date1->setTime("00:00:00");

    if($periodEnd !== null)
      $periodEnd = mySqlDateTimeFormat(strtotime($periodEnd));

    $now = mySqlDateTimeFormat();

    $nowDate = new Zend_Date();
    $nowDate->setTime("00:00:00");

    $this->period_beg = $date1->toString(MYSQL_DATETIME_FORMAT_ZEND);
    $this->period_end = $periodEnd === null ? $nowDate->toString(MYSQL_DATETIME_FORMAT_ZEND) : $periodEnd;


    ////// Р Р…Р В° РЎРѓР В»РЎС“РЎвЂЎР В°Р в„–, Р ВµРЎРѓР В»Р С‘ РЎвЂљР В°Р С” Р С—Р С•Р В»РЎС“РЎвЂЎР С‘Р В»Р С•РЎРѓРЎРЉ, РЎвЂЎРЎвЂљР С• Р Т‘Р Р†РЎС“РЎвЂ¦Р Р…Р ВµР Т‘Р ВµР В»РЎРЉР Р…Р С•Р С�РЎС“ Р В±Р В°Р ВµРЎР‚РЎС“ РЎРѓР С•Р В·Р Т‘Р В°Р ВµРЎвЂљРЎРѓРЎРЏ Р Р…Р ВµР Т‘Р ВµР В»РЎРЉР Р…РЎвЂ№Р в„– Р С‘Р Р…Р Р†Р С•Р в„–РЎРѓ
    ////// РЎвЂљР С•Р С–Р Т‘Р В° Р Т‘Р В°РЎвЂљР В° invoice_next_default РЎРѓР В°Р С�Р В° Р С‘РЎРѓР С—РЎР‚Р В°Р Р†Р В»РЎРЏР ВµРЎвЂљРЎРѓРЎРЏ (РЎС“Р Р†Р ВµР В»Р С‘РЎвЂЎР С‘Р Р†Р В°Р ВµРЎвЂљРЎРѓРЎРЏ), Р С‘ Р С‘Р Р…Р Р†Р С•Р в„–РЎРѓ Р С•РЎвЂљР С”Р В»Р В°Р Т‘РЎвЂ№Р Р†Р В°Р ВµРЎвЂљРЎРѓРЎРЏ
   /* $prevDefaultDate = T3Invoices::getInstance()->getPrevDefaultInvoiceDate($this->period_end, $this->periodType, $this->period);

    if($this->period_beg>$prevDefaultDate){
      $nextPlus = T3Invoices::getInstance()->getNextDefaultInvoiceDate($this->period_end, $this->periodType, $this->period);

      $this->database->query('
        UPDATE users_company_buyer
        SET invoices_next_default = ?
        WHERE id = ?
      ', array($nextPlus, $this->buyer_id));

      return false;
    }*/

    ////////////////////////////////////////////////////////////////////////////////////////////////

    $this->creation_datetime = $now;

    $timelyLimit = new Zend_Date();
    $timelyLimit->addDay($timelyLimitDays);
    $this->timely_limit = $timelyLimit->toString(MYSQL_DATETIME_FORMAT_ZEND);


    $this->calcSums(false);

    return $this;

  }

  public function calcSums($recalc = true){

    $this->total_value = 0;
    $this->leads_number = 0;
    $this->movements_number = 0;
    $this->addings_number = 0;    

    if(!$this->is_custom){

      if(!$recalc){
        $quotedList1 = dbQuote($this->database, $this->getActualSellingsIds());
        $quotedList2 = dbQuote($this->database, $this->getActualMovementsIds());
        $quotedList3 = dbQuote($this->database, $this->getActualAddingsIds());
      }else{

        $this->actualSellingsIds = $this->database->fetchCol(
          "select id from buyers_leads_sellings where invoice_id = ?", array($this->id));
        $quotedList1 = dbQuote($this->database, $this->actualSellingsIds);

        $this->actualMovementsIds = $this->database->fetchCol(
          "select id from buyers_leads_movements where invoice_id = ?", array($this->id));
        $quotedList2 = dbQuote($this->database, $this->actualMovementsIds);

        $this->actualAddingsIds = $this->database->fetchCol(
          "select id from buyers_invoices_addings where invoice_id = ?", array($this->id));
        $quotedList3 = dbQuote($this->database, $this->actualAddingsIds);

      }

      $sums = $this->database->fetchCol("
        SELECT sum(action_sum) FROM buyers_leads_sellings WHERE  ". ($quotedList1===false ? "0" : "id IN ($quotedList1)") . "
        UNION ALL
        SELECT sum(action_sum) FROM buyers_leads_movements WHERE ". ($quotedList2===false ? "0" : "id IN ($quotedList2)") . "
        UNION ALL
        SELECT sum(action_sum) FROM buyers_invoices_addings WHERE ". ($quotedList3===false ? "0" : "id IN ($quotedList3)") . "
      ");

      $this->getProducts(false);

      $this->total_value += $sums[0] + $sums[1] + $sums[2];
      $this->leads_number += count($this->actualSellingsIds);
      $this->movements_number += count($this->actualMovementsIds);
      $this->addings_number += count($this->actualAddingsIds);

    }else{

      $quotedList3 = dbQuote($this->database, $this->getActualAddingsIds());
      $addingsSumCount = $this->database->fetchRow("
        SELECT sum(action_sum) as s, count(*) as c FROM buyers_invoices_addings WHERE invoice_id = ?
      ", array($this->id));

      $this->total_value += $addingsSumCount['s'];
      $this->addings_number += count($addingsSumCount['c']);

    }


    $customInvoiceData = $this->GetCustomInvoiceData();

    if(!empty($customInvoiceData)){

      $channelsIds = array();

      foreach($customInvoiceData as $v){

        $channelsIds[] = (int)($v['channel']);

        switch($this->template){

          case T3Invoices::INVOICE_TEMPLATE_STANDARD:

            $this->total_value += ($v['leads_count'] - $v['credits_count']) * $v['tier'];
            $this->leads_number += $v['leads_count'];
            $this->movements_number += $v['credits_count'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_GROSS_FACTOR_NET:

            $this->total_value += $v['gross'] * $v['factor'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_RECORDS_PRICE_NET:

            $this->total_value += $v['leads_count'] * $v['tier'];
            $this->leads_number += $v['leads_count'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_LEADS_COST_NET:

            $this->total_value += ($v['leads_count'] - $v['credits_count']) * $v['tier'];
            $this->leads_number += $v['leads_count'];
            $this->movements_number += $v['credits_count'];

            break;

          case T3Invoices::INVOICE_TEMPLATE_AVERAGE_LEAD_PRICE:

            $this->total_value += $v['leads_cost'] - $v['credits_cost'];
            $this->leads_number += $v['leads_count'];
            $this->movements_number += $v['credits_count'];

            break;

        }

      }

      if(!empty($channelsIds)){
        $channelsIdsString = implode(', ', $channelsIds);
        $products = $this->database->fetchCol("
          select distinct product from buyers_channels where id in ($channelsIdsString)
        ");
      }else{
        $products = array();
      }

      if(empty($this->productsAr))
        $this->productsAr = array_unique($products);
      else{
        $this->productsAr = array_unique(array_merge($this->productsAr, $products));
      }

    }
    
  }

  protected function getActualSellingsIds($lazy = true){
    if($lazy && $this->actualSellingsIds !== null)
      return $this->actualSellingsIds;
    $this->actualSellingsIds = $this->selectActualLeads('buyers_leads_sellings', true);
    return $this->actualSellingsIds;
  }

  protected function getActualMovementsIds($lazy = true){
    if($lazy && $this->actualMovementsIds !== null)
      return $this->actualMovementsIds;
    $this->actualMovementsIds = $this->selectActualLeads('buyers_leads_movements', true);
    return $this->actualMovementsIds;
  }

  protected function getActualAddingsIds($lazy = true){
    if($lazy && $this->actualAddingsIds !== null)
      return $this->actualAddingsIds;
    $this->actualAddingsIds = $this->selectActualLeads('buyers_invoices_addings', false);
    return $this->actualAddingsIds;
  }

  public function make($periodType, $period){
    try{
      $this->database->beginTransaction();

      if($this->is_custom || !$this->isEmpty()){
        if(!$this->is_custom)
          $this->successive_id = $this->getBuyer()->invoices_last_num + 1;
        $this->database->query('
          UPDATE users_company_buyer
          SET invoices_last_num = ifnull(invoices_last_num, 0) + 1
          WHERE id = ?
        ', array($this->buyer_id));
        $this->insertIntoDatabase();
        if(!$this->is_custom){
          $this->updateRelatedTables($periodType, $period);
        }
      }

      $this->updateNextDefaultInvoiceDate($periodType, $period);
      if($this->is_custom || !$this->isEmpty()){
        $title = '';
        $fileName = '';
        $this->writePdfFile(false, $title, $fileName);
      }
      $this->database->commit();
    }catch(Exception $e){
      $this->database->rollBack();
      //throw $e;
      // Р В·Р В°Р С—Р С‘РЎРѓР В°РЎвЂљРЎРЉ Р Р† Р С•РЎв‚¬Р С‘Р В±Р С”Р С‘
    }
  }

  public function remove(){
    if(!$this->canBeRemoved())
      return false;
    try{
      $this->database->beginTransaction();
      $this->cleanRelatedTables();
      $this->rollBackNextDefaultInvoiceDate();
      $this->deleteFromDatabase();
      $this->database->commit();
    }catch(Exception $e){
      $this->database->rollBack();
      throw $e;
      // Р В·Р В°Р С—Р С‘РЎРѓР В°РЎвЂљРЎРЉ Р Р† Р С•РЎв‚¬Р С‘Р В±Р С”Р С‘
    }
    return true;
  }

  public function canBeRemoved(){

    /*if($this->sendings_number!=0)
      return false;*/

    $paymentsExist = $this->database->fetchOne('
      SELECT count(*)
      FROM buyers_invoices_payments
      WHERE invoice_id = ?
    ', array($this->id));

    if($paymentsExist != 0)
      return false;
      
    return true;

  }

  public function cleanRelatedTables(){

    $prevLastNumber = $this->database->fetchOne('
      select ifnull(max(successive_id),-1) from buyers_invoices where buyer_id=? and id!=?
    ', array($this->buyer_id, $this->id));


    if($prevLastNumber>=0){
      $this->database->query('
        UPDATE users_company_buyer
        SET invoices_last_num = ?
        WHERE id = ?
      ', array($prevLastNumber, $this->buyer_id));          
    }else{
      $this->database->query('
        UPDATE users_company_buyer
        SET invoices_last_num = ifnull(invoices_last_num, 0) - 1
        WHERE id = ?
      ', array($this->buyer_id));
    }

    $this->database->query('
      DELETE FROM buyers_invoices
      WHERE id = ?
    ', array($this->id));
    
    $this->database->query('
      DELETE FROM buyers_invoices_channels
      WHERE invoice_id = ?
    ', array($this->id));

    $this->database->query('
      UPDATE buyers_leads_movements
      SET invoice_id = NULL
      WHERE invoice_id = ?
    ', array($this->id));

    $this->database->query('
      UPDATE buyers_leads_sellings
      SET invoice_id = NULL
      WHERE invoice_id = ?
    ', array($this->id));

    $this->database->query('
      UPDATE buyers_invoices_addings
      SET invoice_id = NULL
      WHERE invoice_id = ?
    ', array($this->id));
    
  }
  
  // for buyers_leads_sellings and buyers_leads_movements
  protected function selectActualLeads($table, $byChannelTime){
    
    /*$ar = $this->database->fetchAll("
      SELECT id, invoice_id
      FROM `$table`
      WHERE buyer_id = ? AND action_datetime >= ? AND action_datetime <= ?
    ", array($this->buyer_id, $this->period_beg, $this->period_end));*/

    if($byChannelTime){
      $ar = $this->database->fetchAll("
        SELECT id, invoice_id
        FROM `$table`
        WHERE ((invoice_id is null) or (invoice_id = 0)) and buyer_id = ?  AND channel_action_datetime <= ?
      ", array($this->buyer_id, $this->period_end));
    }else{
      $ar = $this->database->fetchAll("
        SELECT id, invoice_id
        FROM `$table`
        WHERE ((invoice_id is null) or (invoice_id = 0)) and buyer_id = ?  AND action_datetime <= ?
      ", array($this->buyer_id, $this->period_end));
    }

    $i = 0;
    $ids = array();

    foreach($ar as $v)
      if(empty($v['invoice_id'])){
        $i++;
        $ids[] = $v['id'];
      }else{
        // throw new Exception('Not Implemented');
        // Р В·Р Т‘Р ВµРЎРѓРЎРЉ Р С”Р В°Р С”Р В°РЎРЏ РЎвЂљР С• Р С•РЎв‚¬Р С‘Р В±Р С”Р В°, Р Р…РЎС“Р В¶Р Р…Р С• Р С•Р В± РЎРЊРЎвЂљР С• РЎРѓР С•Р В±РЎвЂ°Р В°РЎвЂљРЎРЉ. Р С—Р С•РЎвЂљР С•Р С�РЎС“ РЎвЂЎРЎвЂљР С• Р Р†РЎРѓР Вµ Р Т‘Р С•Р В»Р В¶Р Р…РЎвЂ№ Р В±РЎвЂ№РЎвЂљРЎРЉ empty      
      }

    return $ids;

  }

  public function getContent($lazy = true){
    if($lazy && $this->content !== null)
      return $this->content;

    $ar = $this->database->fetchAll('
      SELECT un.*, bc.title as channel_title FROM (
          SELECT channel_id, action_datetime, action_sum, lead_product, lead_id
          FROM buyers_leads_sellings
          WHERE invoice_id = ?
        UNION ALL
          SELECT channel_id, action_datetime, action_sum, lead_product, lead_id
          FROM buyers_leads_movements
          WHERE invoice_id = ?
      ) as un
      LEFT JOIN buyers_channels as bc ON un.channel_id = bc.id
      ORDER BY action_datetime
    ', array($this->id, $this->id));

    $this->content = groupBy($ar, 'channel_id');

    return $this->content;
    
  }

  // for buyers_leads_sellings and buyers_leads_movements
  protected function updateFinanceTable($table, &$ids){
    $quotedList = dbQuote($this->database, $ids);
    $this->database->query("
      UPDATE `$table`
      SET invoice_id = ?
      WHERE ". ($quotedList===false ? "0" : "id IN ($quotedList)") . "
    ", array($this->id));

  }

  protected function getChannelsSelectQuery(){
    $quotedList1 = dbQuote($this->database, $this->getActualSellingsIds());
    $quotedList2 = dbQuote($this->database, $this->getActualMovementsIds());
    return "
      SELECT
        0 AS id,
        ? AS invoice_id,
        channel_id,
        sum(leads_number) AS leads_number,
        sum(movements_number) AS movements_number,
        sum(total_value) AS total_value
      FROM (
          SELECT
            count(*) AS leads_number,
            0 AS movements_number,
            sum(action_sum) AS total_value,
            channel_id
          FROM buyers_leads_sellings
          WHERE ". ($quotedList1===false ? "0" : "id IN ($quotedList1)") . "
          GROUP BY channel_id
        UNION ALL
          SELECT
            0 AS leads_number,
            count(*) AS movements_number,
            sum(action_sum) AS total_value,
            channel_id
          FROM buyers_leads_movements
          WHERE ". ($quotedList2===false ? "0" : "id IN ($quotedList2)") . "
          GROUP BY channel_id
      ) AS temp_table
      GROUP BY channel_id
    ";
  }

  public function updateNextDefaultInvoiceDate($periodType, $period){

    if(!$this->created_by_default)
      return false;

    if(!empty($periodType)){
    
      $date = T3Invoices::getInstance()->getNextDefaultInvoiceDate($this->period_end, $periodType, $period);

      if($date === false){
        // TODO Р Р† Р С•РЎв‚¬Р С‘Р В±Р С”Р С‘ Р Т‘Р С•Р В±Р В°Р Р†Р В»РЎРЏР ВµР С�
        return false;
      }

      $this->database->query('
        UPDATE users_company_buyer
        SET invoices_next_default = ?
        WHERE id = ?
      ', array($date, $this->buyer_id));

    }

    return true;
    
  }

  public function rollBackNextDefaultInvoiceDate(){
    $this->database->query("update users_company_buyer set invoices_next_default = ? where id = ?",
      array($this->node_creation_datetime, $this->buyer_id));
  }

  // must be within a transaction
  public function updateRelatedTables($periodType, $period){

    $this->updateFinanceTable('buyers_leads_sellings', $this->getActualSellingsIds());
    $this->updateFinanceTable('buyers_leads_movements', $this->getActualMovementsIds());
    $this->updateFinanceTable('buyers_invoices_addings', $this->getActualAddingsIds());

    $this->database->query("
      REPLACE INTO buyers_invoices_channels
      " . $this->getChannelsSelectQuery() . "
    ", array($this->id));

  }

  public function getDetailsTableItemsQuery($query, $forCount = false){
    $selects = array();
    $tables = array(
      'buyers_leads_sellings',
      'buyers_leads_movements',
    );

    foreach($tables as $table){

      $select = $this->database->select();
      $selects[] = $select;

      if(!$forCount){

        $select->from($table, array(
          'channel_id',
          'action_datetime',
          'channel_action_datetime',
          'action_sum',
          'lead_email',
          'lead_ssn',
          'lead_home_phone',
          'lead_product',
          'lead_is_from_v1' => 'is_v1_lead',
          'lead_id',
        ));

        $select->joinLeft(
          'leads_data',
          "$table.lead_id=leads_data.id",
          array('lead_webmaster_id' => 'affid')
        );

        $select->joinLeft(
          'leads_type',
          "$table.lead_product=leads_type.name",
          array('lead_product_title' => 'title')
        );

      }else{
        $select->from($table, array('c' => 'count(*)'));
      }

      foreach(T3invoices::$invoiceDetailsConditions as $key => $value){

        if(is_numeric($key))
          $type = $value;
        else
          $type = $key;

        $fieldName = $value;

        if($type == 'action_datetime')
          continue;

        if(!isset($query['conditions'][$type]) || $query['conditions'][$type]['type'] == 'all')
          continue;

        if($query['conditions'][$type]['type'] == 'certain')
          $select->where("$fieldName = ?", $query['conditions'][$type]['certain']);
        else if($query['conditions'][$type]['type'] == 'values' && !empty($query['conditions'][$type]['values'])){
          $values = dbQuote($this->database, $query['conditions'][$type]['values']);
          $select->where("$fieldName in ($values)");
        }

      }


      if(!empty($query['start_datetime']))
        $select->where("action_datetime >= ?", $query['start_datetime']);

      if(!empty($query['end_datetime']))
        $select->where("action_datetime <= ?", $query['end_datetime']);


      $select->where('invoice_id = ?', $query['invoice_id']);

    }


    $unitedSelect = $this->database->select();
    $unitedSelect->union($selects, Zend_Db_Select::SQL_UNION_ALL);
    $unionTable = array('united' => new Zend_Db_Expr('('.(string)$unitedSelect.')'));
    $select = $this->database->select();

    if(!$forCount){
      $select->from($unionTable);
    }else{
      $select->from($unionTable, array('c' => "sum(united.c)"));
    }

    if(!$forCount){
      $select->limit($query['page_size'], ($query['_page']-1)*$query['page_size']);
      $select->order('united.action_datetime desc');
    }

    return $select;

  }

  public function & getDetailsTableItems($query){

    $select = $this->getDetailsTableItemsQuery($query, false);
    $result = $this->database->query($select)->fetchAll();
    return $result;

  }

  public function getDetailsTableItemsCount($query){

    $select = $this->getDetailsTableItemsQuery($query, true);
    $result = $this->database->query($select)->fetchAll();
    return empty($result) ? 0 : $result[0]['c'];

  }
                
}


