<?php



class T3WebmasterCompanys {
    protected static $_instance = null;
    static protected $companies = array();
    
    /**
    * Проверяет есть ли вебмастер с таким ID
    * Опционально можно передать ID агента, для того что бы проверить, если ли такой вебмастер у этого агента
    * 
    * @param mixed $id
    * @param mixed $agentId
    * @return bool
    */
    static public function isWebmaster($id, $agentId = null){
        if($agentId) return (bool)T3Db::api()->fetchOne('select count(*) from users_company_webmaster where id=? and agentID=?', array((int)$id, (int)$agentId));     
        return (bool)T3Db::api()->fetchOne('select count(*) from users_company_webmaster where id=?', (int)$id);
    }
    
    
    /**
    * Возвращает объект класса T3WebmasterCompanys
    * @return T3WebmasterCompanys
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
    * УСТАРЕВШАЯ, ну возможно где то используется, нет времени првоерять!!
    * Загрузить объект вебмастера (компании)
    * 
    * @param int $id
    * @return T3WebmasterCompany
    */
    public function getCompany_Not_Static($id){
        self::getCompany($id);   
    }
    
    
    /**
    * Грапповая загрузка вебмастров
    * Групповое получение данных из базы 
    * 
    * @param mixed $ids array(webmasterID_1, $webmasterID_2, ...)
    */
    static public function loadCompanys($ids){
        if(is_array($ids) && count($ids)){
            $all = T3Db::api()->fetchAll("SELECT * FROM users_company_webmaster WHERE id in ('" . implode("','", $ids) . "')");

            if(count($all)){
                foreach($all as $el){
                    self::$companies[$el['id']] = new T3WebmasterCompany();
                    
                    self::$companies[$el['id']]->fromArray($el);
                    self::$companies[$el['id']]->existsInDatabase = true;    
                }
            }
        }
    }
    
    /**
    * Загрузить объект вебмастера (компании)
    * 
    * @param int $id
    * @return T3WebmasterCompany
    */
    static public function getCompany($id){
        if(!isset(self::$companies[$id])){
            self::$companies[$id] = new T3WebmasterCompany();
            self::$companies[$id]->fromDatabase($id);
        } 
        
        return self::$companies[$id];  
    }
    
    /**
    * Получить Ref AffID для определенного вебмастера
    * 
    * @param mixed $webmasterID
    * @return mixed
    */
    static public function getReferalID($webmasterID){
        return self::getInstance()->getCompany($webmasterID)->refaffid;    
    } 
    
    /**
    * Получить Ref AffID для определенного вебмастера
    * 
    * @param mixed $webmasterID
    * @return mixed
    */
    static public function getAgentID($webmasterID){
        return self::getInstance()->getCompany($webmasterID)->agentID;    
    } 
    
    
    
    static public function channelTypeNewToOld($new){
        $new = (string)$new;
        
        $array = array(
            'js_form'       => 'feed',
            'post_channel'  => 'posting',
            ''              => 'all'  
        );
        
        return isset($array[$new]) ? $array[$new] : '';   
    }
    
    static public function channelTypeOldToNew($old){
        $array = array(
            'all'       => null,
            'feed'      => 'js_form',
            'posting'   => 'post_channel',  
        );
        
        return isset($array[$old]) ? $array[$old] : '';   
    }
    
    static public function getChannels($webmaster, $all = null){
        $result = array();
        
        if(!is_null($all)){
            $result[''] = $all;    
        }
        
        if($webmaster){
            $all = T3Db::api()->fetchAll("select id, title, channel_type, product from channels where company_id=? order by channel_type desc, product, title", $webmaster);    
            
            if(count($all)){
                foreach($all as $el){
                    if($el['channel_type'] == 'js_form'){
                        $channel_type_str = "JS Form";    
                    }
                    else if($el['channel_type'] == 'post_channel'){
                        $channel_type_str = "Server POST";     
                    }
                    
                    $prod = T3Products::getTitle($el['product']);
                    
                    $result["{$channel_type_str} - {$prod}"][$el['id']] = "{$el['title']}";
                }   
            }
        }
        
        return $result;
    }
    
    public static $lastEncruptionsIDs = array();
    
    static public function getChannels_ByWMArray($webmasters, $all = null, $encryptionID = false, $public = false){
        if($encryptionID){
            self::$lastEncruptionsIDs = array();    
        }
        
        if(T3Users::getCUser()->isRoleWebmaster()){
            $webmasters = array(T3Users::getCUser()->company_id);  
        }
        
        $result = array();
        
        if(!is_null($all)){
            $result[''] = $all;    
        }
        
        if(T3Users::getCUser()->isRoleAdmin() && is_array($webmasters) && $public){
            $webmasters[] = 28806;
        }
        
        if(is_array($webmasters) && count($webmasters)){

            
            $all = T3Db::api()->fetchAll("select id, title, channel_type, product, company_id from channels where company_id in ('" . implode("','", $webmasters) . "') order by company_id, channel_type desc, product, title");    
            
            if(count($all)){
                foreach($all as $el){
                    if($el['channel_type'] == 'js_form'){
                        $channel_type_str = "JS Form";    
                    }
                    else if($el['channel_type'] == 'post_channel'){
                        $channel_type_str = "Server POST";     
                    }
                    
                    $prod = T3Products::getTitle($el['product']);  
                    
                    $publisher = T3Cache_Publisher::get($el['company_id'], false);  
                    
                    if($encryptionID)   $newID = IdEncryptor::encode($el['id']);
                    else                $newID = $el['id'];
                    
                    if($encryptionID){
                        self::$lastEncruptionsIDs[$el['id']] = $newID;    
                    }
                    
                    if(count($webmasters) == 1){ 
                        $result["{$channel_type_str} - {$prod}"][$newID] = "{$el['title']}";     
                    }
                    else {
                        $result["{$publisher} - {$channel_type_str} - {$prod}"][$newID] = "{$el['title']}";
                    }
                }   
            }
        }
        
        return $result;
    }
    
    
    static public function reindexProducts(){
        /*
        $all = T3Db::api()->fetchAll("select * from ((select company_id as cid, product from channels group by company_id, product order by company_id) UNION
        (select userid as cid, product from cache_summary_days_details where `date` > '" . 
        date("Y-m-d", mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"))) . "' group by userid, product order by userid)) as a group by cid, product");  
        */
        
        $all = T3Db::api()->fetchAll("
            select 
                userid as cid, 
                product 
            from 
                cache_summary_days_details 
            where 
                `date` > '" . date("Y-m-d", mktime(0, 0, 0, date("m") - 2, date("d"), date("Y"))) . "' 
            group by 
                userid, product 
            order by userid
        ");  
        
        $webmasters = array();
        foreach($all as $el){
            if(!isset($webmasters[$el['cid']])) $webmasters[$el['cid']] = array();
            $webmasters[$el['cid']][] = $el['product'];
        }
        
        varExport(count($all));
        varExport(count($webmasters));
        
        T3Db::api()->update("users_company_webmaster", array('products' => ""), "1=1");    
        foreach($webmasters as $cid => $products){
            T3Db::api()->update("users_company_webmaster", array('products' => implode(",", $products)), "id={$cid}");
        }   
    }


    static public function getInvalidBalances(){
        $allData = array();
        $temp = T3Db::api()->fetchAll("select balance, webmaster_id, total_sum, delta from webmaster_payments_current_dept");
        if(count($temp)){
            foreach($temp as $el){
                $allData[$el['webmaster_id']] = $el;
            }
        }

        // Найти все неоплаченные пейменты
        $temp = T3Db::api()->fetchAll("select id, webmaster_id, total_value from webmasters_payments where fully_paid='0'");

        // полная сумма неоплаченных пейментов, индексированная по WebmasterID
        $notPaidValues = array();

        $notPaidPayments_Pays = array();

        if(count($temp)){
            // ID пейментов
            $notPaidPaymentsIDs = array();


            foreach($temp as $el){
                // Индекс по Payment ID
                $notPaidPaymentsIDs[] = $el['id'];

                // Индекс полных сумм неоплаченных пейментов по WebmasterID
                if(!isset($notPaidValues[$el['webmaster_id']])){
                    $notPaidValues[$el['webmaster_id']] = 0;
                }
                $notPaidValues[$el['webmaster_id']] += $el['total_value'];
            }


            if(count($notPaidPaymentsIDs)){
                // найти все частичные оплаты неоплаченных пейментов, индексированные по вебмастру
                $notPaidPayments_Pays = T3Db::api()->fetchPairs(
                    "select webmaster_id, sum(`value`) as paid from webmasters_payments_pays where payment_id in ('" .
                    implode("','", $notPaidPaymentsIDs) . "') group by webmaster_id"
                );
            }
        }

        // Объединить данные о Payments с $allData
        if(count($allData)){
            foreach($allData as $k => $v){
                $allData[$k]['not_paid']    = 0;
                $allData[$k]['total_debt']  = 0;
                $allData[$k]['delta2']      = 0;
            }
        }

        if(count($notPaidValues)){
            foreach($notPaidValues as $wm => $npValue){
                if(!isset($allData[$wm])){
                    $allData[$wm] = array(
                        'balance'       => T3Db::api()->fetchOne("select balance from users_company_webmaster where id=?", $wm),
                        'webmaster_id'  => $wm,
                        'total_sum'     => '0',
                        'delta'         => '0',
                        'not_paid'      => '0',
                        'total_debt'    => '0',
                        'delta2'        => '0',
                    );
                }

                $payNotPaid = isset($notPaidPayments_Pays[$wm]) ? $notPaidPayments_Pays[$wm] : 0;
                $allData[$wm]['not_paid']     =   round($npValue - $payNotPaid, 2);
            }
        }

        if(count($allData)){
            foreach($allData as $wm => $v){
                $allData[$wm]['total_debt']   =   round($allData[$wm]['not_paid'] + $allData[$wm]['total_sum'], 2);
                $allData[$wm]['delta2']       =   round($allData[$wm]['total_debt'] - $allData[$wm]['balance'], 2);

                if($allData[$wm]['delta2'] == 0){
                    unset($allData[$wm]);
                }
            }
        }

        return $allData;
    }
}

