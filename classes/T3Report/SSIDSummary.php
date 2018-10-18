<?php

class T3Report_SSIDSummary extends AP_Report_SummaryCache { 
    protected $table = 'cache_ssid_summary';                           
    
    static protected $allKeys = array('date', 'ssid', 'posting');                             
    static protected $allValues = array('filtered', 'notfiltered', 'post', 'sold', 'value');   
    
    /**************************************************************************************
    * Получение объектов для записи кешей в базу
    */
    
    /**
    * @param T3Lead $lead
    * @param T3BuyerChannel $posting
    * @return self
    */
    static public function load(T3Lead $lead, T3BuyerChannel $posting){   
        return self::instance()->keys(__CLASS__, self::$allKeys, array(
            'date'      => substr($lead->datetime, 0, 10), // получаем только дату, без времени
            'ssid'      => T3SecureSubID::get_ssID($lead, $posting),
            'posting'   => $posting->id,
        ));
    }
    
    
    /**************************** Обновление данных *******************************/
    
    /**
    * Лид отфильтрован по SSID
    * 
    * @return T3Report_SSIDSummary
    */
    public function filtered(){
        $this->update(self::$allValues, array(
            'filtered' => 1,     
        ));
        return $this;
    } 
    
    /**
    * Лид не отфильтрован по SSID
    * 
    * @return T3Report_SSIDSummary
    */
    public function notfiltered(){
        $this->update(self::$allValues, array(
            'notfiltered' => 1,      
        ));
        return $this;
    } 
    
    /**
    * Пошел на продажу
    * 
    * @return T3Report_SSIDSummary
    */
    public function post(){
        $this->update(self::$allValues, array(
            'post' => 1,     
        ));
        return $this;
    } 
    
    /**
    * Продан
    * 
    * @return T3Report_SSIDSummary
    */
    public function sold($value){
        $this->update(self::$allValues, array(
            'sold'  => 1, 
            'value' => round($value, 2),   
        ));
        return $this;
    }     
    
    /**
    * Показать статистику для вебмастра
    * id вебмастра будет передаваться в гет переменной "id", это удобно для вставки его на страницу вебмастра
    * 
    * @param mixed $webmaster - если передать вебмастра, то статистика будет показана для этого вебмастра
    */
    static public function renderReportForWebmaster($webmaster = null){
        self::jsInterfaceUpdateSSIDFiltered();
        
        // подключение к базе (поменять на репликанта)
        $db = T3Db::apiReplicant(); 
        
        $form = new Form(
            "SSID Report" . 
            (strlen(T3WebmasterCompanys::getCompany($webmaster)->systemName) ? 
                "for " . T3WebmasterCompanys::getCompany($webmaster)->systemName : 
                ""
            )
        );   
        $form->setDescription("
            Что бы включить фильтрацию SecureSubID на определенный постинг, поставьте галочку в соответсвующее поле.
        ");
        $form->setMethodGet();
                                        
        $form->addElement_Webmaster("id", "Webmaster", 1)->setValue($webmaster);
        $form->addElementDateRange("from", "till", "-1M");
        $form->addElement_Buyer('buyer');
        
        $form->addElementSelect("type", "Type", array(
            'convert'   => 'Convert: (Sold/Posted) / Posted : Sold',
            'filtering' => 'Filtering: Filtered / Not Filtered',
        ))->setValue('convert');
        
        // Создание таблицы  /////////////////////////////////////////
        $table = new Table();
        $table->setForm($form);  // прикрепляет форму к таблице
         
        // Обработка формы ////////////////////////////////////////////
        $form->isValidNotErrors(); // проверка полей формы, если в поле ошибка, то ставим в него значение по умолчанию
        
        
        /*********************************/
        
        if(!$form->getValue("id")){     
            // Если не выбран вебмастер
            $table->setMessagesNotData("Select Webmaster");
            
            if(isset($_GET['id'])) $form->getElement('id')->addError('Webmaster is Required');
        }
        else {
            $ssids = $db->fetchCol("SELECT ssID FROM `ssIDusers` WHERE iduser=?", $form->getValue("id"));
            if(count($ssids)){
                $select = $db->select();
                
                if($form->getValue('type') == 'convert'){
                    $select->from("cache_ssid_summary", array(                     
                        'ssid',
                        'posting',
                        'sold'          => new Zend_Db_Expr("SUM(sold)"),   
                        'post'          => new Zend_Db_Expr("SUM(post)"),     
                    ));
                }
                else {
                    $select->from("cache_ssid_summary", array(                     
                        'ssid',
                        'posting',   
                        'filtered'      => new Zend_Db_Expr("SUM(filtered)"),   
                        'notfiltered'   => new Zend_Db_Expr("SUM(notfiltered)"),   
                    ));    
                }
                
                $select
                ->where("ssid IN (" . implode(", ", $ssids) . ")")
                ->where("`date` between '" . $form->getValue('from') . "' and '" . $form->getValue('till') . "'")
                ->where("ssid > 0")
                ->group(array('ssid', 'posting')); 
                
                if($form->getValue('buyer')){
                    $postings = $db->fetchCol("SELECT `id` FROM `buyers_channels` WHERE `buyer_id`=?", $form->getValue('buyer'));
                    
                    if(count($postings)){
                        $select->where("posting in (" . implode(", ", $postings) . ")"); 
                    }
                    else {
                        $select->where("1=0");    
                    }
                }
                
                $all = $db->fetchAll($select);
                
                
                if(count($all)){
                    // получить все постинги, сгрупированные по баерам
                    // собрать индекс
                    $postings = array();
                    $index = array();   
                    foreach($all as $el){
                        if(!in_array($el['posting'], $postings))$postings[] = $el['posting'];
                        $index[$el['ssid']][$el['posting']] = $el; 
                    }
                    
                    $postings = $db->fetchCol("SELECT `id` FROM `buyers_channels` WHERE `id` IN (" . implode(", " , $postings) . ") order by buyer_id");
                    
                    /****************************************
                    * получить настройки фильтров
                    * 
                    * 1. выключено (и возможно настроенно, но выключено и значит можно все удалить нафиг)
                    * 2. включено в режиме исключения
                    * 3. включено в режиме включения
                    */
                    $temp = $db->fetchAll(
                        "SELECT `channel_id`,`affirmative`,`misc` FROM `buyers_filters_conditions` WHERE `channel_id` in (" . 
                            implode(",", $postings) . 
                        ") AND `type_name`='SecureSubID' AND works=1"
                    );
                    $filters = array();
                    if(count($temp)){
                        foreach($temp as $el){
                            $filters[$el['channel_id']] = array(
                                'type' => $el['affirmative'], // 0 - Excluded (оно же по умолчанию), 1 - Only
                                'ssids' => explode(",", trim($el['misc'])),
                            );    
                        }
                    }
                        
                    /******************************/
                    
                    $use_ssid = array(); // массив всех используемых ssid
                    $data = array();
                    foreach($postings as $posting){
                        $add = array('posting' => $posting);
                        
                        foreach($ssids as $ssid){
                            if($form->getValue('type') == 'convert'){
                                // конверт, продаж к постам
                                if(isset($index[$ssid][$posting])){
                                    $add['ssid' . $ssid] = sprintf("%.1f" ,($index[$ssid][$posting]['post'] ? round($index[$ssid][$posting]['sold'] / $index[$ssid][$posting]['post'], 3)*100 : 0)) . "<span style='color:#999'> %</span>";
                                }
                                else {
                                    $add['ssid' . $ssid] = 0;       
                                }
                                   
                                $add['ssid' . $ssid] .= " <span style='color:#999'>/ " . 
                                    ifset($index[$ssid][$posting]['post'], 0) . " : " . ifset($index[$ssid][$posting]['sold'], 0)  . 
                                "</span>";
                                
                                $attr = new AP_UI_Html_Attribs();
                                $attr->setAttrib('type', 'checkbox');
                                $attr->setAttrib('id',  "cb_{$ssid}_{$posting}");    
                                $attr->setAttrib('onchange', "document.updateSSIDFiltering('{$ssid}', '{$posting}', this.checked)"); 
                                
                                if(isset($filters[$posting])){
                                    if(
                                        ($filters[$posting]['type'] == 0 &&  in_array($ssid, $filters[$posting]['ssids'])) ||
                                        ($filters[$posting]['type'] == 1 && !in_array($ssid, $filters[$posting]['ssids']))
                                    ){
                                        $attr->setAttrib('checked',  'checked');       
                                    }
                                }
                                
                                $add["btn{$ssid}"] = "<input " . $attr->render() . ">";
                                
                                // использование ssid
                                if(ifset($index[$ssid][$posting]['post'], 0) > 0 || ifset($index[$ssid][$posting]['sold'], 0)) $use_ssid[$ssid] = true;
                            }
                            else {
                                // проходжение фильтров `filtered` `notfiltered`  
                                $add['ssid' . $ssid] = ifset($index[$ssid][$posting]['filtered'], 0) . " / " . ifset($index[$ssid][$posting]['notfiltered'], 0);
                                
                                // использование ssid
                                if(ifset($index[$ssid][$posting]['filtered'], 0) > 0 || ifset($index[$ssid][$posting]['notfiltered'], 0)) $use_ssid[$ssid] = true;
                                
                            }
                        } 
                        
                        $data[] = $add;       
                    }
                    
                    // настройка таблицы - добавляем только используемые ssid
                    $table->addField_Posting("posting", "Posting");
                    foreach($ssids as $ssid){
                        if(isset($use_ssid[$ssid])){
                            $table->addField("btn{$ssid}", "")->setStyleTD('width', '1px');
                            $table->addField('ssid' . $ssid, $ssid)->setStyleTD('white-space', 'nowrap')->setSortCurrency();
                        }
                    }
                    
                    $table->setData($data);
                    
                }
            }
        }
        
        return $table->render();
    }
    
    
    /**
    * Показать статистику для баера
    * id баера будет передаваться в гет переменной "id", это удобно для вставки его на страницу баера (но недопустимо для вставки на страницу постинга)
    * 
    * @param mixed $webmaster - если передать баера, то статистика будет показана для этого баера
    */
    static public function renderReportForBuyer($buyer = null){
        self::jsInterfaceUpdateSSIDFiltered();
        
        // подключение к базе (поменять на репликанта)
        $db = T3Db::apiReplicant(); 
        
        $form = new Form(
            "SSID Report" . 
            (strlen(T3BuyerCompanys::getCompany($buyer)->systemName) ? 
                "for " . T3BuyerCompanys::getCompany($buyer)->systemName : 
                ""
            )
        );
        $form->setDescription("
            Что бы включить фильтрацию SecureSubID на определенный постинг, поставьте галочку в соответсвующее поле.
        ");
        $form->setMethodGet();
                                        
        $form->addElement_Buyer("id", "Buyer", 1)->setValue($buyer);
        $form->addElementDateRange("from", "till", "-1M");
        
        $form->addElement_Webmaster("webmaster", "Webmaster (Optional)", 0);
        
        $form->addElementSelect("type", "Type", array(
            'convert'   => 'Convert: (Sold/Posted) / Posted : Sold',
            'filtering' => 'Filtering: Filtered / Not Filtered',
        ))->setValue('convert');
        
        // Создание таблицы  /////////////////////////////////////////
        $table = new Table();
        $table->setForm($form);  // прикрепляет форму к таблице
         
        // Обработка формы ////////////////////////////////////////////
        $form->isValidNotErrors(); // проверка полей формы, если в поле ошибка, то ставим в него значение по умолчанию
        
        
        /*********************************/
        
        if(!$form->getValue("id")){     
            // Если не выбран вебмастер
            $table->setMessagesNotData("Select Buyer");
            
            if(isset($_GET['id'])) $form->getElement('id')->addError('Buyer is Required');
        }
        else {
            $postings = $db->fetchCol("SELECT `id` FROM `buyers_channels` WHERE `buyer_id`=?", $form->getValue('id'));
            
            if(count($postings)){            
                /****************************************
                * получить настройки фильтров
                * 
                * 1. выключено (и возможно настроенно, но выключено и значит можно все удалить нафиг)
                * 2. включено в режиме исключения
                * 3. включено в режиме включения
                */
                $temp = $db->fetchAll(
                    "SELECT `channel_id`,`affirmative`,`misc` FROM `buyers_filters_conditions` WHERE `channel_id` in (" . 
                        implode(",", $postings) . 
                    ") AND `type_name`='SecureSubID' AND works=1"
                );
                $filters = array();
                if(count($temp)){
                    foreach($temp as $el){
                        $filters[$el['channel_id']] = array(
                            'type' => $el['affirmative'], // 0 - Excluded (оно же по умолчанию), 1 - Only
                            'ssids' => explode(",", trim($el['misc'])),
                        );    
                    }
                }
                
                // получить данные
                $select = $db->select();
                
                if($form->getValue('type') == 'convert'){
                    $select->from("cache_ssid_summary", array(                     
                        'ssid',
                        'posting',
                        'sold'          => new Zend_Db_Expr("SUM(sold)"),   
                        'post'          => new Zend_Db_Expr("SUM(post)"),     
                    ));
                }
                else {
                    $select->from("cache_ssid_summary", array(                     
                        'ssid',
                        'posting',   
                        'filtered'      => new Zend_Db_Expr("SUM(filtered)"),   
                        'notfiltered'   => new Zend_Db_Expr("SUM(notfiltered)"),   
                    ));    
                }
                
                $select
                ->where("posting in (" . implode(", ", $postings) . ")")
                ->where("`date` between '" . $form->getValue('from') . "' and '" . $form->getValue('till') . "'")
                ->where("ssid > 0")
                ->group(array('ssid', 'posting')); 
                
                if($form->getValue("webmaster")){
                    $ssids = $db->fetchCol("SELECT `ssID` FROM `ssIDusers` WHERE `iduser`=?", $form->getValue("webmaster"));
                    if(count($ssids)){
                        $select->where("ssid in (" . implode(",", $ssids) . ")");
                    }
                    else {
                        $select->where("1=0");    
                    }
                }
                
                $all = $db->fetchAll($select);
                
                if(count($all)){      
                    // собрать массиву всех ssid                     
                    // собрать индекс   
                    $index = array();
                    $ssids = array();   
                    foreach($all as $el){                                                    
                        if(!in_array($el['ssid'], $ssids))$ssids[] = $el['ssid'];
                        $index[$el['ssid']][$el['posting']] = $el; 
                    }
                    
                    $ssid_webmasters = $db->fetchPairs("SELECT `ssID`, `iduser` FROM `ssIDusers` WHERE `ssID` IN (" . implode(",", $ssids) . ")");
                       
                    
                    /******************************/
                    
                    $use_postings = array(); // массив всех используемых постингов
                    $data = array();
                    
                    foreach($ssids as $ssid){ 
                        $add = array(
                            'ssid'          => $ssid,
                            'webmaster'     => isset($ssid_webmasters[$ssid]) ? $ssid_webmasters[$ssid] : 0,
                        );
                                
                        foreach($postings as $posting){
                            if($form->getValue('type') == 'convert'){
                                // конверт, продаж к постам
                                if(isset($index[$ssid][$posting])){
                                    $add['posting' . $posting] = sprintf("%.1f" ,($index[$ssid][$posting]['post'] ? round($index[$ssid][$posting]['sold'] / $index[$ssid][$posting]['post'], 3)*100 : 0)) . " <span style='color:#999'>%</span>";
                                }
                                else {
                                    $add['posting' . $posting] = 0;       
                                }   
                                
                                $add['posting' . $posting].= " <span style='color:#999'>/ " . 
                                    ifset($index[$ssid][$posting]['post'], 0) . " : " . ifset($index[$ssid][$posting]['sold'], 0) . 
                                "</span>";
                                
                                $attr = new AP_UI_Html_Attribs();
                                $attr->setAttrib('type', 'checkbox');
                                $attr->setAttrib('id',  "cb_{$ssid}_{$posting}");    
                                $attr->setAttrib('onchange', "document.updateSSIDFiltering('{$ssid}', '{$posting}', this.checked)"); 
                                
                                if(isset($filters[$posting])){
                                    if(
                                        ($filters[$posting]['type'] == 0 &&  in_array($ssid, $filters[$posting]['ssids'])) ||
                                        ($filters[$posting]['type'] == 1 && !in_array($ssid, $filters[$posting]['ssids']))
                                    ){
                                        $attr->setAttrib('checked',  'checked');       
                                    }
                                }
                                
                                $add["btn{$posting}"] = "<input " . $attr->render() . ">";
                                
                                // использование ssid
                                if(ifset($index[$ssid][$posting]['post'], 0) > 0 || ifset($index[$ssid][$posting]['sold'], 0)) $use_postings[$posting] = true;
                            }
                            else {
                                // проходжение фильтров `filtered` `notfiltered`  
                                $add['posting' . $posting] = ifset($index[$ssid][$posting]['filtered'], 0) . " / " . ifset($index[$ssid][$posting]['notfiltered'], 0);
                                
                                // использование ssid
                                if(ifset($index[$ssid][$posting]['filtered'], 0) > 0 || ifset($index[$ssid][$posting]['notfiltered'], 0)) $use_postings[$posting] = true;
                                
                            }
                        } 
                        
                        $data[] = $add;       
                    }
                    
                    // настройка таблицы - добавляем только используемые ssid
                    $table->addField("ssid", "SSID");
                    $table->addField_Webmaster("webmaster", "Webmaster")->setSortText();
                    
                    foreach($postings as $posting){
                        if(isset($use_postings[$posting])){
                            $table->addField("btn{$posting}", "")->setStyleTD('width', '1px');
                            $table->addField("posting{$posting}", T3Cache_BuyerChannel::get($posting))->setStyleTD('white-space', 'nowrap')->setSortCurrency();
                        }
                    }
                    
                    $table->setData($data);
                    
                }
            }
        }
        
        return $table->render();
    }
    
    /**
    * Интерфейс для обновления фильтров по ssid
    * 
    * На странице, на которой он загружается, надо добавить дополнительные GET переменные: 
    * jsonInterface     = 'updateSSIDFiltering',
    * posting           = (int), 
    * ssid              = (int),
    * filtering         = (0|1)
    * 
    * в ответ вернеться json: {'status':'ok'}
    * 
    * Также на этой странице будет доступна JS функция: document.updateSSIDFiltering = function(ssid, posting, filtering), которой можно проводить изменения со страницы
    */
    static public function jsInterfaceUpdateSSIDFiltered(){
        // обновление фильтра
        if(isset($_GET['jsonInterface']) && $_GET['jsonInterface'] == 'updateSSIDFiltering'){
            if(isset($_GET['posting']) && isset($_GET['ssid']) && isset($_GET['filtering'])){
                self::updateSSIDFiltering($_GET['ssid'], $_GET['posting'], $_GET['filtering']);
                header('Content-type: application/json');
                die("{'status':'ok'}");
            }
        }
        
        AP_Site::addJSCode("
            document.updateSSIDFiltering = function(ssid, posting, filtering){
                jQuery('#cb_' + ssid + '_' + posting).attr('disabled', true);
                if(filtering == false) filtering = 0;
                if(filtering != 0) filtering = 1;
                
                jQuery.get(
                    document.location.href,
                    {
                        jsonInterface:  'updateSSIDFiltering',
                        ssid:           ssid,
                        posting:        posting,
                        filtering:      filtering     
                    },
                    function(data){
                        jQuery('#cb_' + ssid + '_' + posting).removeAttr('disabled');   
                    }  
                )
            }
        ");
    }
    
    /**
    * Изменнеие фильтра одного ssid на 1 постинг
    * 
    * @param mixed $ssid
    * @param mixed $posting
    * @param mixed $filtering
    */
    static public function updateSSIDFiltering($ssid, $posting, $filtering = true){
        $ssid = (int)$ssid;
        $posting = (int)$posting;
        $filtering = (bool)$filtering;
        /****************************************
        * получить настройки фильтров
        * 
        * 1. выключено (и возможно настроенно, но выключено и значит можно все удалить нафиг)
        * 2. включено в режиме исключения
        * 3. включено в режиме включения
        */   
        $filter = T3Db::api()->fetchRow("SELECT * FROM `buyers_filters_conditions` WHERE `type_name`='SecureSubID' AND `channel_id`=?", $posting);  

        if(is_array($filter)){   
            $ssids       = explode(",", trim($filter['misc']));
            $affirmative = $filter['affirmative'];
            $works       = $filter['works'];
            
            // если фильтр сохранен (даже если не включен)
            if($works){
                // если фильтр включен
                if($affirmative == 0){
                    // фильтр работает по схеме исключения
                    if($filtering){
                        if(!in_array($ssid, $ssids)){
                            $ssids[] = $ssid;    
                        } 
                    }
                    else {
                        // исключить их массива текущий ssid (на всякий случай предусматриваем баг что ssid может быть несколько раз)
                        $temp = array();
                        foreach($ssids as $el){
                            if($el != $ssid) $temp[] = $el;     
                        }
                        $ssids = $temp;
                    }
                }
                else {
                    // фильтр работает по схеме - ONLY
                    if($filtering){
                        $temp = array();
                        foreach($ssids as $el){
                            if($el != $ssid) $temp[] = $el;     
                        }
                        $ssids = $temp;       
                    } 
                    else {
                        if(!in_array($ssid, $ssids)){
                            $ssids[] = $ssid;    
                        }    
                    }     
                }                                     
            }
            else {
                // если фильтр выключен (перенастраиваем его на 1 значение)
                if($filtering){
                    $works = 1;
                    $affirmative = 0;
                    $ssids = array($ssid);
                }
                else {
                    // Если фильтр выключен и его не надо включать, то ничего не меняем
                }
            }
            
            T3Db::api()->update("buyers_filters_conditions", array(
                'works'         => $works,
                'affirmative'   => $affirmative,
                'misc'          => implode(",", $ssids) . "\n\n", // нашел такую фишку в фильтрах, как я понял она отделяет простое сравнение от сравнениям по регуляркам
            ), "`id`='{$filter['id']}'");
        }  
        else {                
            if($filtering){
                // если фильтра нет и нужно фильтровать
                T3Db::api()->insert("buyers_filters_conditions", array(
                    'channel_id'    => $posting,
                    'type_name'     => 'SecureSubID',
                    'affirmative'   => '0',
                    'works'         => '1',
                    'misc'          => $ssid . "\n\n", // нашел такую фишку в фильтрах, как я понял она отделяет простое сравнение от сравнениям по регуляркам
                ));
            }
        }
    }  
}