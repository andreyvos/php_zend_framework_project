<?php

TableDescription::addTable('tracking_items', array(
    'id',
    'type',
    'create_date',
    'webmaster_id',
    'channel_id',
    'lead_id',
    'product',
    'comment',
    'subaccount',
    'price',
    'post_start_date',
    'post_status',
    'post_errors_count',
));          


class T3Tracking_Item extends DbSerializable {

    public $id;
    public $type; // newLead, leadAddPrice, leadReturn, newBonus, test
    public $create_date;
    public $webmaster_id; 
    public $channel_id;
    public $lead_id;
    public $product;
    public $subaccount; 
    public $comment; 
    public $price;
    public $post_start_date;
    public $post_status = 'free'; // ok, brone, free
    public $post_errors_count = 0;

    public function __construct() {
        parent::__construct();
        $this->tables = array('tracking_items');
        $this->readNewIdAfterInserting = false;
    }
    
    protected $reason;
    
    public function getReason(){
        return $this->reason;    
    }
    
    public function post($isTest = false, $testURL = ''){
        $result = array(
            'success'           => false,
            'reason'            => '',
            'request_header'    => '',
            'response_header'   => '',
            'response'          => '',
        );
        
        /**
        * @var T3Tracking_Setting
        */
        $setting = T3Tracking_Settings::getSetting($this->webmaster_id);
        
        //varExport($setting->getParams());
        
        if($setting->isRun($this->type) || $isTest){
            $a = array();

            $getID = new T3Leads_GetID($this->lead_id, false);
            
            $leadID = T3Db::api()->fetchOne("select id from leads_data where `affid`=? and `num`=?", array(
                $getID->wm,
                $getID->num,   
            ));

            list($num,$affid) = explode('.',$this->lead_id);

            if ($affid == 44475 && $this->product == 'call'){
                $a['{phone}']  = urlencode(T3Db::api()->fetchOne("SELECT phone FROM leads_data_call WHERE id='$leadID'"));
            }
                        
            $a['{track_id}']      = urlencode($this->id);
            $a['{type}']          = urlencode($this->type); 
            $a['{create_date}']   = urlencode($this->create_date); 
            $a['{webmaster_id}']  = urlencode($this->webmaster_id); 
            $a['{channel_id}']    = urlencode($this->channel_id); 
            $a['{lead_id}']       = urlencode($this->lead_id); 
            $a['{product}']       = urlencode($this->product); 
            $a['{subaccount}']    = urlencode($this->subaccount); 
            $a['{comment}']       = urlencode($this->comment); 
            $a['{price}']         = urlencode($this->price);
            $a['{click_id}']      = urlencode(T3Clickid::getClickIdByLeadId($leadID));
            $a['{email}']         = urlencode(T3Db::api()->fetchOne("SELECT data_email FROM leads_data WHERE num='$num' AND affid='$affid'"));
            
            $url = $setting->url;
            if($isTest) $url = $testURL;
            
            $url = str_replace(array_keys($a), array_values($a), $url);            
            
            //varExport($url);
              
            $head = array(
                'Expect: ',
            );
               
            // настрйока отправки  
            $ch = curl_init();

            curl_setopt(    $ch,    CURLOPT_URL,               $url                            );
            curl_setopt(    $ch,    CURLOPT_POST,              0                               );    
            curl_setopt(    $ch,    CURLOPT_HTTPHEADER,        $head                           ); 
            curl_setopt(    $ch,    CURLOPT_FAILONERROR,       1                               );
            curl_setopt(    $ch,    CURLOPT_HEADER,            1                               );
            curl_setopt(    $ch,    CURLOPT_RETURNTRANSFER,    1                               );
            curl_setopt(    $ch,    CURLOPT_SSL_VERIFYPEER,    false                           );
            curl_setopt(    $ch,    CURLOPT_SSL_VERIFYHOST,    false                           );
            curl_setopt(    $ch,    CURLOPT_TIMEOUT,           120                             );
            curl_setopt(    $ch,    CURLINFO_HEADER_OUT,       true                            );
            curl_setopt(    $ch,    CURLOPT_HTTP_VERSION,      CURL_HTTP_VERSION_1_1           );

            $startDate = date('Y-m-d H:i:s');
            
            // отправка данных, получение ответа, отделение header от body($return)
            $output                     = curl_exec($ch);
            $header_size                = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $result['response_header']  = substr($output, 0, $header_size - 4);
            $result['response']         = substr($output, $header_size);

            $result['request_header']   = curl_getinfo($ch, CURLINFO_HEADER_OUT);     
            
            $result['reason'] = "";
            
            // анализ отправки
            if(curl_errno($ch)){              
                // Ошибка
                $result['reason'] = curl_error($ch);
                $response_status = 'bad'; 
            }
            else {                                 
                // Отправка завершена успешно   
                if(!$setting->checkResponce || strtolower(trim($result['response'])) == strtolower('TrackAccepted')){
                    // ответ подходящий
                    $result['success'] = true;
                    $response_status = 'good'; 
                }   
                else {
                    // ответ не подходящий
                    $addDots = "";
                    $showResponse = trim($result['response']);
                    if(strlen($showResponse) > 100){
                        $showResponse = substr($showResponse, 0, 100);
                        $addDots = "...";    
                    }
                    
                    $showResponse = '"' . nl2br(htmlspecialchars($showResponse)) . '"' . $addDots;
                    
                    $result['success'] = false;
                    $result['reason'] = "Response not \"TrackAccepted\". Response: {$showResponse}"; 
                    $response_status = 'bad';      
                }   
                curl_close($ch);
            }

            T3Db::api()->insert("tracking_log", array(
                'send_date'         => new Zend_Db_Expr("NOW()"),
                'webmaster'         => $this->webmaster_id,
                'lead_id'           => $this->lead_id,
                'url'               => $url,
                'response_status'   => $response_status,
                'response'          => $result['response'],
                'log'               => "Response Headers:\r\n" . $result['response_header'] . 
                                        "\r\n\r\nRequest Headers:\r\n" . $result['request_header'] . 
                                        "\r\n\r\nReason:\r\n" . $result['reason'],
            ));   
        }
        else {
            $result['success'] = null;   
        }
        
        
        
        return $result;    
    }

}