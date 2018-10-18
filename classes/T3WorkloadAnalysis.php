<?php

// workload_by_interval

class T3WorkloadAnalysis {

    public static $Lambda = 60;

    static public function getLoadingValues(){
        return array(
            'system'    => Zend_Json::decode(file_get_contents("http://system.t3leads.com/system/loading/run.php")),
            'site'      => Zend_Json::decode(file_get_contents("http://t3leads.com/system/loading/run.php")),
            'altohost'  => Zend_Json::decode(file_get_contents("http://f.t3leads.com/system/loading/run.php")),
        );    
    }
    
    public static function CheckWorkload($startInterval, $endInterval)
    {
        /*
         * Собираем информацию о нагрузке системы
         * 1) Нагрука базы
         * 2) Кол-во обращений к разделу статистики
         * 3) Кол-во полученых лидов
         *
         * Все это за определенный интервал времени
         *
         */

        return T3System::getConnectCache()->fetchAll("select * from workload_by_interval WHERE (start>='".$startInterval."') and (start<='".$endInterval."')");
    }
    
    public static function GetLastInterval()
    {
        // Возвращает значение последнего интервала
        return T3System::getConnectCache()->fetchRow("select * from workload_by_interval order by start desc limit 1");
    }

    public static function GetMySQLWorkload($debug = false)
    {
        $r = array();
        $a = T3WorkloadAnalysis::getLoadingValues();

        foreach ($a['site'] as $_var => $_value)
        {
            $r['site_'.$_var] = $_value;
        }

        foreach ($a['altohost'] as $_var => $_value)
        {
            $r['altohost_'.$_var] = $_value;
        }

        foreach ($a['system'] as $_var => $_value)
        {
            $r['system_'.$_var] = $_value;
        }
        
        $q = "select
        count(*) as LeadsPings,
        max(sum_without_send) as allMax, round(sum(sum_without_send)/count(*),5) as allAvg,
        max(sum_p_all_without_send) as postMax, round(sum(sum_p_all_without_send)/count(*),5) as postAvg
        from seconds
        where datetime_end BETWEEN '".
        /*
        date("Y-m-d H:i:s", mktime(date("H"), date("i")-1, 0, date("m"), date("d"), date("Y")))."' and '".
        date("Y-m-d H:i:s", mktime(date("H"), date("i")-1, 59, date("m"), date("d"), date("Y")))."'";
        */
        date("Y-m-d H:i:S", mktime(date("H"), date("i")-1, date('s')+1, date("m"), date("d"), date("Y")))."' and '".
        date("Y-m-d H:i:S", mktime(date("H"), date("i"), date('s'), date("m"), date("d"), date("Y")))."'";

        $row = T3System::getConnectCache()->fetchRow($q);

        $r['allMax'] = $row['allMax'];
        $r['allAvg'] = $row['allAvg'];
        $r['postMax'] = $row['postMax'];
        $r['postAvg'] = $row['postAvg'];
        $r['LeadsPings'] = $row['LeadsPings'];
        
        if($r['allAvg'] > 10){
            T3FatalMessage::sendMessage("warningLeadRunTime", array(
                'allAvg' => $r['allAvg']
            )); 
            /*
            $mess = new T3Mail_Message();
            $mess->addTo("79510900000@sms.ulgsm.ru");
            $mess->setSubject("T3. lead avg time = {$r['allAvg']}. ULGSM.ru");
            $mess->SendMail();
            */
        }

        if ($debug)
        {
            print_r($row);
            print_r($r);
            print_r($q);
        }

        return $r;
    }

    public static function CalcNewInterval($params, $debug = false)
    {
        
        $currentTime = mktime();
        $prevInterval = T3WorkloadAnalysis::GetLastInterval();
        // Собираем данные о нагрузке
        $_wl = T3WorkloadAnalysis::GetMySQLWorkload($debug);

        foreach ($_wl as $_var => $_value)
        {
            $params[$_var] = $_value;
        }
/*
        $params['cpu'] = $_wl[0];
        $params['mem'] = $_wl[1];
*/
        if (empty($prevInterval) || (($currentTime - $prevInterval['start']) > T3WorkloadAnalysis::$Lambda))
        {
            /*
             * Это значит нужно создавать новый интервал
             * Я считаю что не следует создавать пустые интервалы. Это может произойти если между двумя интервалами был разрыв в значение больше чем $Lambda
             */
            if (empty($prevInterval))
            {
                // Создаем самый первй интервал в таблице
                T3WorkloadAnalysis::CreateNewInterval($currentTime, $params);
            }
            else
            {
                $d = $currentTime - $prevInterval['start'];

                $icount = (int)($d / T3WorkloadAnalysis::$Lambda);
                // Возможно придется заполнять пустоты
                $newStart = $prevInterval['start']  + T3WorkloadAnalysis::$Lambda * $icount;
                if ($icount > 1)
                {
                    // Заполняем пустоты
                    for ($i = 1; $i < $icount; $i++)
                    {
                        T3WorkloadAnalysis::CreateNewInterval($prevInterval['start']  + T3WorkloadAnalysis::$Lambda * $i, array());
                    }                    
                }
                T3WorkloadAnalysis::CreateNewInterval($newStart, $params);
            }
        }
        else
        {
            // Делаем Update
            T3WorkloadAnalysis::UpdateInterval($prevInterval['start'], $params);
        }

    }

    public static function UpdateInterval($intStartTime, $params)
    {
        $_sq = "";
        $_i = 0;
        foreach ($params as $_var => $_value)
        {
            $_sq.=($_i>0?", ":"").$_var."=".$_var."+'".$_value."'";
            $_i++;
        }
        $_sq.=", requests_count=requests_count+'1'";
        $Q = "update workload_by_interval SET ".$_sq." WHERE start='".$intStartTime."'";
        //var_dump($Q, false);
        // update('workload_by_interval', array());
        $res = T3System::getConnectCache()->query($Q);
    }

    public static function CreateNewInterval($intStartTime, $params)
    {

        $params['start'] = $intStartTime;
        
        if (count($params) > 1)
        {
            $params['requests_count'] = '1';
        }

        if ((isset($params['system_mysql_t3api_Table_locks_immediate'])) && (isset($params['system_mysql_t3api_Table_locks_waited'])))
        {
            // Get prev value            
            $r = T3System::getConnectCache()->fetchRow("select * from workload_by_interval WHERE (start<'".$intStartTime."') order by start desc limit 1");
            if (isset($r['system_mysql_t3api_Table_locks_immediate'])  &&  isset($r['system_mysql_t3api_Table_locks_waited']))
            {
                $params['d_system_mysql_t3api_Table_locks_immediate'] = $params['system_mysql_t3api_Table_locks_immediate'] - $r['system_mysql_t3api_Table_locks_immediate'];
                $params['d_system_mysql_t3api_Table_locks_waited'] = $params['system_mysql_t3api_Table_locks_waited'] - $r['system_mysql_t3api_Table_locks_waited'];
            }
            
        }


        T3System::getConnectCache()->insert('workload_by_interval', $params);
    }

}