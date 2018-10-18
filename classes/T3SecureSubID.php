<?php

class T3SecureSubID {
    static protected $insertModeRealTime = true;
    static protected $commitArray = array();
    
    /**
    * Длинна нового SSID
    * 
    * @var int
    */
    static public $len_ssID = 7;
    
    /**
    * Минимальный и максимальный приоритет 
    * 
    * @var mixed
    */
    static public $minMaxRate = array('min' => 10, 'max' => 200);
                                                
    static protected $ssids = array();
    
    
    
    static public function setInsertModeRealTime($flag){
        self::$insertModeRealTime = (bool)$flag;    
    }
    
    /**
    * Сохранить накопленные данные в базу
    */
    static public function commit($end = true){
        // Запись всей очереди в базу   
        if(count(self::$commitArray)){  
            T3Db::api()->insertMulty("leads_ssids", array('ssid', 'lead', 'buyer'), self::$commitArray); 
            self::$commitArray = array();
        }
        
        if($end) self::setInsertModeRealTime(true);
    }
    
    static public function createNewSSID($webmasterID, $groupID, $count = 1, $creator = null){
        $allSSID = array();
        
        if(is_null($creator)){
            // если создатель не задан, пытаемся взять его из объекта пользователя
            if(T3Users::getInstance(0)->getCurrentUserId() > 0){
                $creator = T3Users::getInstance(0)->getCurrentUserId();       
            }
        }
        
        $creator = (int)$creator;
        
        for($i = 0; $i < $count; $i++){
            try {
                $new_ssid = rand(1 . str_repeat(0, self::$len_ssID - 1), str_repeat(9, self::$len_ssID));
                $new_rand = rand(self::$minMaxRate['min'], self::$minMaxRate['max']);
                
                T3Db::api()->insert("ssIDusers", array(
                    'iduser'        => $webmasterID,
                    'ssIDgroup'     => $groupID,
                    'ssID'          => $new_ssid,
                    'status'        => 'activ',
                    'rate'          => $new_rand, 
                    'creator'       => $creator,
                    'create_date'   => date('Y-m-d H:i:s'),
                )); 
                
                $allSSID[$new_ssid] = $new_rand;  
            }
            catch(Exception $e){
                $i--;
            }   
        }  
        
        return $allSSID;      
    }
    
    /**
    * Получить ssid для данного лида + канала
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $channel
    */
    static public function get_ssID(T3Lead $lead, T3BuyerChannel $channel){
        if($lead->affid == '26046' && $lead->product == 'ukpayday' && ($channel->buyer_id == '10007' || $channel->buyer_id == '33205')){
            return '5443935';
        }

        if($lead->affid == '30164' && $lead->product == 'ukpayday' && ($channel->buyer_id == '10007' || $channel->buyer_id == '33205')){
            return '9511825';
        }

        /**
        * 1. проверить был ли уже получен ID на этого баера
        * 2. если не был, получить все возможные на него ID + фильтры всех каналов по этим IDS
        * 3. исключить те ID, которые филтруются во всех кналах и понизить приоритет тех, которые фильтуются в некоторых каналах
        * 4. выбрать подходящий ssid
        */

        /**
         * План №2
         * 1. проверить был ли уде получен ID для этого баера, если был то взять его (ssid сохраняется в базу данных в таблицу leds_ssids)
         * 2. получить все ids этого баера + все постинги
         * 3. проверить какие постинги подходят под фильтры (не считая филтра по ssid)
         * 4. на оставшихся постингах найти такой ssid который подходит всем, если такого нет, сделать новый (кроме случая если есть каналы only included)
         * 5. усе (логировать создаение новых + логировать отфильтрованные)
         * 6. при проверке каналов на фильтры, сделать кеш проверок,Ж что бы второй раз не проверть тоже самое!!
         *
         * Логирование:
         * Отфильтрованные лиды по причине ssid (их долно быть мало и только для тех баеров у которых настроен фильтр "Включать только")
         */

        if(!isset(self::$ssids[$lead->id][$channel->buyer_id])){
            // получение значения из базы данных
            self::$ssids[$lead->id][$channel->buyer_id] = T3Db::api()->fetchOne("SELECT ssid FROM `leads_ssids` WHERE `lead`=? AND `buyer`=?", array($lead->id, $channel->buyer_id));

            if(true || !self::$ssids[$lead->id][$channel->buyer_id]){
                // выбор подходящего ssid
                $allSSID = array();

                // выбор группы
                $group = T3BuyerCompanys::getCompany($channel->buyer_id)->groupID;
                if($group != 1){
                    // если группа не первая, получаем все id для неё
                    $allSSID = T3Db::api()->fetchPairs("SELECT ssID, rate FROM `ssIDusers` WHERE `iduser`=? AND `ssIDgroup`=? AND `status`='activ'", array($lead->affid, $group));
                    
                    // если для этой группы нет, по переключаемся в группу по умолчанию
                    if(!count($allSSID)){
                        $group = 1; 
                    }
                }
                
                if($group == 1){
                    $allSSID = T3Db::api()->fetchPairs("SELECT ssID, rate FROM `ssIDusers` WHERE `iduser`=? AND `ssIDgroup`=? AND `status`='activ'", array($lead->affid, $group));
                    
                    // если в группе по умолчанию нет IDS, деам их для неё 1 ssid (что бы баеры не могли просеч эту схему делая новых вебмастров)
                    if(!count($allSSID)){
                        $allSSID = self::createNewSSID($lead->affid, '1');
                    }
                }
                
                // получить какие постинги этого баера исключают какие IDS
                $postings = T3Db::api()->fetchCol(
                    "SELECT id FROM `buyers_channels` WHERE `buyer_id`=? AND `status`='active' AND product=? AND filter_datetime=1",
                    array($channel->buyer_id, $channel->product)
                );

                /*
                    varExport($allSSID);
                    echo "--";
                    varExport($postings);
                    echo "--";
                */

                // Проверка каналов на фильтры, исключая фильтр SecureSubID, для которого пока еще недостаточно данных и который приведет к зацикливанию
                if(count($postings)){
                    $postingsNew = array();
                    foreach($postings as $posting){
                        // T3BuyerChannels::getChannel($posting)->

                        $filter = T3BuyerFilters::getInstance()->getFilter($posting, true, true);
                        $filterResult = $filter->acceptsLead($lead, array('SecureSubID'));

                        if(!$filterResult->isError()) {
                            $postingsNew[] = $posting;
                        }
                        else {
                            // varExport($posting);
                            // varExport($filter->getTextReport());
                        }
                    }

                    $postings = $postingsNew;
                }

                // varExport($postings);

                if(count($postings)){
                    // получить информацию по фильтру ssid для каналов, котоыре подходят под фильтры
                    $temp = T3Db::api()->fetchAll(
                        "SELECT channel_id, `affirmative`, `misc` FROM `buyers_filters_conditions` " . 
                        "WHERE channel_id IN (" . implode(",", $postings) .") AND `type_name`='SecureSubID' AND `works`=1"
                    ); 
                    
                    $index = array();

                    $indexTypes = array(
                        0 => array(), // без фильтров
                        1 => array(), // excluded
                        2 => array(), // included
                    );
                    
                    foreach($postings as $pid){
                        $index[$pid] = array(
                            null, // не фильтруется
                        );     
                    }

                    if(count($temp)){
                        foreach($temp as $el){         
                            $fids = array();
                            $fids_temp = explode(",", $el['misc']);
                            if(count($fids_temp)){
                                foreach($fids_temp as $fid){
                                    $fid = (int)$fid; 
                                    
                                    if($fid){
                                        $fids[] = $fid;
                                    }
                                }
                            } 
                            
                            if(count($fids)){
                                $index[$el['channel_id']] = array(
                                    ($el['affirmative']) ? 2 : 1,
                                    $fids
                                );    
                            }      
                        }
                    }

                    foreach($index as $pid => $el){
                        if($el[0] == null)  $indexTypes[0][] = $pid;
                        if($el[0] == 1)     $indexTypes[1][] = $pid;
                        if($el[0] == 2)     $indexTypes[2][] = $pid;
                    }

                    // varExport($index);

                    $allSSID_temp  = $allSSID;

                    /*
                     *
                     * 1. есть только excluded каналы - оставляем только те которые есть везде, если ничего не осталось создать новый
                     * 2. есть только included каналы или есть разные каналы - выбор от значимости канала
                     *
                     */

                    $getIDFinish = false;

                    // 1. Если у баера есть каналы с excluded, ну нет с included и возможно есть такие, которые не фильтрруются
                    if(count($indexTypes[1]) && !count($indexTypes[2])){
                        $getIDFinish = true;

                        foreach($index as $el){
                            if($el[0] == '1' && count($el[1])){
                                foreach($el[1] as $ssid_del){
                                    unset($allSSID[$ssid_del]);
                                }
                            }
                        }

                        if(!count($allSSID)){
                            $allSSID = self::createNewSSID($lead->affid, $group, 1);

                            T3Db::api()->insert("leads_ssids_potencial_create", array(
                                'create'    => date('Y-m-d H:i:s'),
                                'type'      => 'only_include',
                                'webmaster' => $lead->affid,
                                'lead'      => $lead->id,
                                'buyer'     => $channel->id,
                                'data'      => var_export($allSSID_temp, 1) . "\r\n\r\n" . var_export($index, 1) . "\r\n\r\n" . var_export($allSSID, 1),
                            ));
                        }
                    }

                    // 2. Если у баера есть included и excluded каналы, но не один id под них не подходит под included каналы, то идет обработка как в пункте 1
                    if(count($indexTypes[1]) && count($indexTypes[2])){
                        $allSSID_temp = $allSSID;
                        $includedSuccess = false;

                        foreach($index as $el){
                            if($el[0] == '2'){
                                if(count($el[1])){
                                    foreach($el[1] as $ssid_incl){
                                        if(isset($allSSID[(int)$ssid_incl])){
                                            $includedSuccess = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if($includedSuccess == false){
                            $getIDFinish = true;

                            foreach($index as $el){
                                if($el[0] == '1' && count($el[1])){
                                    foreach($el[1] as $ssid_del){
                                        unset($allSSID[$ssid_del]);
                                    }
                                }
                            }

                            if(!count($allSSID)){
                                $allSSID = self::createNewSSID($lead->affid, $group, 1);

                                T3Db::api()->insert("leads_ssids_potencial_create", array(
                                    'create'    => date('Y-m-d H:i:s'),
                                    'type'      => 'only_include',
                                    'webmaster' => $lead->affid,
                                    'lead'      => $lead->id,
                                    'buyer'     => $channel->id,
                                    'data'      => var_export($allSSID_temp, 1) . "\r\n\r\n" . var_export($index, 1) . "\r\n\r\n" . var_export($allSSID, 1),
                                ));
                            }
                        }
                    }

                    // Если есть, такой(такие) id который подходит под все каналы (included и excluded)
                    if(!$getIDFinish){
                        $allSSID_temp = $allSSID;

                        foreach($index as $el){
                            if($el[0] == '1' && count($el[1])){
                                foreach($el[1] as $ssid_del){
                                    unset($allSSID_temp[$ssid_del]);
                                }
                            }
                            else if($el[0] == '2' && count($el[1])){
                                foreach($allSSID_temp as $k => $v){
                                    if(!in_array($k, $el[1])){
                                        unset($allSSID_temp[$k]);
                                    }
                                }
                            }
                        }

                        if(count($allSSID_temp)){
                            $allSSID = $allSSID_temp;
                            $getIDFinish = true;
                        }
                    }

                    // Стандартный механизм выбора ID на основе приоритетов
                    if(!$getIDFinish){
                        // подсчет значимости ssid
                        $rates = array();
                        $allPostings = count($index);

                        $add_new = false;
                        foreach($allSSID as $ssid => $rate){
                            $r = 0;
                            foreach($index as $el){
                                // если добавление нового ID исправит ситуацию
                                if($el[0] === null || $el[0] == 1) $add_new = true;

                                // если подходит под фильтр канала, то +1
                                if(
                                    $el[0] === null ||
                                    ($el[0] == 1 && !in_array($ssid, $el[1])) ||
                                    ($el[0] == 2 && in_array($ssid, $el[1]))
                                ) $r++;
                            }

                            // высчитываем на какую долю каналов подходит
                            $rates[$ssid] = $r / $allPostings;
                        }

                        // корекция приоритетов ssids
                        foreach($allSSID as $ssid => $rate){
                            $allSSID[$ssid] = ceil($rates[$ssid] * $rate);
                            if($allSSID[$ssid] == 0) unset($allSSID[$ssid]);
                        }

                        if(!count($allSSID)){
                            if($add_new){
                                // не осталось ssid, сделать новый в этой группе и использовать его
                                $allSSID = self::createNewSSID($lead->affid, $group, 1);

                                T3Db::api()->insert("leads_ssids_potencial_create", array(
                                    'create' => date('Y-m-d H:i:s'),
                                    'type'      => 'standart',
                                    'webmaster' => $lead->affid,
                                    'lead'      => $lead->id,
                                    'buyer'     => $channel->id,
                                    'data'   => var_export($allSSID_temp, 1) . "\r\n\r\n" . var_export($index, 1) . "\r\n\r\n" . var_export($rates, 1) . "\r\n\r\n" . var_export($allSSID, 1),
                                ));
                            }
                            else {
                                $allSSID = $allSSID_temp;
                            }
                        }
                    }
                }
                 
                $data = array();
                $add = (int)date("d") + ceil(date("s") / 3) + ceil(date("i") / 3) + (int)date("m");
                foreach($allSSID as $ssid => $rate){
                    $data[] = array($ssid, $rate + $add);    
                }
                
                // выбор из массива одного ssid и запись его в базу и оперативку И базу     
                self::$ssids[$lead->id][$channel->buyer_id] = self::randomSelect($data);
                
                if(self::$insertModeRealTime){
                    // сохранение в базу
                    try{
                        T3Db::api()->insert("leads_ssids", array(
                            'ssid'  => self::$ssids[$lead->id][$channel->buyer_id],
                            'lead'  => $lead->id,
                            'buyer' => $channel->buyer_id,
                        ));
                    }
                    catch(Exception $e){

                    }
                }
                else {
                    self::$commitArray[] = array(
                        'ssid'  => self::$ssids[$lead->id][$channel->buyer_id],
                        'lead'  => $lead->id,
                        'buyer' => $channel->buyer_id,
                    );   
                }
            }   
        }
        
        return self::$ssids[$lead->id][$channel->buyer_id];
    }

    public static function checkIfUserHaveSsidInGroup($userID, $groupID){
        $select = T3Db::api()->select()->from("ssIDusers")->where('iduser=?', (int) $userID)->where('ssIDgroup=?', (int) $groupID)->where("status='activ'");
        $r = $select->query()->fetchColumn();

        if ($r !== false){
            return true;
        }
        else{
            return false;
        }
    }

    public static function generate(T3Lead $lead, T3BuyerCompany $company){

        $select = T3Db::api()->select()->from("ssIDusers")->where('iduser=?', (int) $lead->affid)->where('ssIDgroup=?', (int) $company->groupID)->where("status='activ'");
        $result = $select->query()->fetchAll();
        
        if (count($result) == 0){
            $generated = array();
            for ($j = 0; $j < 5; $j++){
                $generated[] = self::createNew($lead->affid, $company->groupID);
            }
            return $generated[rand(0, 4)];
        }
        else{
            $add = (int)date("d") + ceil(date("s") / 3) + ceil(date("i") / 3) + (int)date("m");
            $data = array();
            foreach ($result as $v){
                $data[] = array($v['ssID'], $v['rate'] + $add);
            }
            $ssID = self::randomSelect($data);
        }
        return $ssID;
    }

    public static function selectFromGroup($userID, $groupID){
        $select = T3Db::api()->select()->from("ssIDusers")->where('iduser=?', (int) $userID)->where('ssIDgroup=?', (int) $groupID)->where("status='activ'");
        $result = $select->query()->fetchAll();

        $add = (int)date("d") + ceil(date("s") / 3) + ceil(date("i") / 3) + (int)date("m");
        $data = array();
        
        foreach ($result as $v){
            $data[] = array($v['ssID'], $v['rate'] + $add);
        }
        
        $ssID = self::randomSelect($data);

        return $ssID;
    }

    public static function createNew($webmasterID, $groupID){
        /*
        $i = true;
        while ($i){
            $min_ssID = "1" . str_repeat("0", self::$len_ssID - 1);
            $max_ssID = str_repeat("9", self::$len_ssID);
            $rand = mt_rand($min_ssID, $max_ssID);

            # поиск нового ID в базе данных
            $select = T3Db::api()->select()->from('ssIDusers', array('count(*)'))->where('ssID=?', (int) $rand);
            $result = $select->query()->fetchColumn();
            if ($result == 0){
                $i = false;
            }
        }
        # сохранить в бд
        $insert = T3Db::api()->insert('ssIDusers', array(
            'iduser' => $webmasterID, 
            'ssIDGroup' => $groupID, 'ssID' => $rand, 'status' => 'activ', 'rate' => rand(self::$minMaxRate['min'], self::$minMaxRate['max'])));
        */
        
        $r = self::createNewSSID($webmasterID, $groupID, 1);
        $r = array_keys($r);
        
        return $r[0];
    }

    public static function delete($id){
        // $select = T3Db::api()->select()->from('ssIDusers', array('iduser'))->where('ssID=?', (int) $id);
        // $result = $select->query()->fetchColumn();
        // $idUser = (int) $result;
        T3Db::api()->update('ssIDusers', array('status' => 'delete'), 'ssID=' . $id);
    }

    public function randomSelect($array){
        $sum = 0; # сумма приоритетов
        for ($i = 0; $i < count($array); $i++){
            $sum+= $array[$i][1];
        }

        $rand = rand(1, $sum);

        $sum = 0; # сумма приоритетов
        $n = count($array);
        for ($i = 0; $i < $n; $i++){
            if ($sum < $rand && $sum + $array[$i][1] >= $rand){
                return $array[$i][0];
            }
            $sum+= $array[$i][1];
        }

        return null;
    }

    public function testFunction(){
        $lead = new T3Lead();
        $channel = new T3BuyerChannel();

        $lead->fromDatabase(array('id' => '143'));
        $channel->fromDatabase(array('id' => '10055'));
        vvv(self::get_ssID($lead, $channel));
    }

    public static function getGroups(){
        $select = T3Db::api()->select()->from('ssIDgroups');
        return $select->query()->fetchAll();
    }

    public static function getBySSID($ssid){
        $result = T3Db::api()->select()->from('ssIDUsers')->where('ssID=?', (int) $ssid)->query()->fetchAll();
        return $result;
    }

    public static function changeSSID($oldssid){
        $i = true;
        $rand = 0;

        while ($i)
        {
            $min_ssID = "1" . str_repeat("0", self::$len_ssID - 1);
            $max_ssID = str_repeat("9", self::$len_ssID);
            $rand = mt_rand($min_ssID, $max_ssID);

            # поиск нового ID в базе данных
            $select = T3Db::api()->select()->from('ssIDusers', array('count(*)'))->where('ssID=?', (int) $rand);
            $result = $select->query()->fetchColumn();
            if ($result == 0)
            {
                $i = false;
            }
        }
        T3Db::api()->update('ssIDusers', array('ssID' => $rand), 'ssID=' . $oldssid);
    }

    public static function createNewGroup($name){
        T3Db::api()->insert('ssIDgroups', array('groupname' => $name));
        return T3Db::api()->lastInsertId();
    }

    public function createAndModify($name, $buyerID){
        $id = self::createNewGroup($name);
        $company = new T3BuyerCompany();
        $company->fromDatabase(array('id' => $buyerID));
        $company->groupID = $id;
        $company->saveToDatabase();
        return $id;
    }

    public function checkGroup($name){
        $select = T3Db::api()->select()->from('ssIDgroups')->where('groupname=?', $name);
        $result = $select->query()->fetchAll();
        if (count($result) == 0)
        {
            return true;
        }
        return false;
    }

    public static function getGroupByName($name){
        $select = T3Db::api()->select()->from('ssIDgroups')->where('groupname=?', $name);
        $result = $select->fetchColumn();
        return $result;
    }

    public static function getWebmasterBySID($ssid){
        $id = T3Db::api()->select()->from('ssIDusers', array('iduser'))->where('ssID=?', $ssid)->query()->fetchColumn();
        return (int) $id;
    }
}