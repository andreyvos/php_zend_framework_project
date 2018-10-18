<?


class T3CacheSummary {

    /* Рассмотрим критерии которые могут влият на результат:
     *
     * по умолчанию мы работаем с таблицей cache_summary_days_all
     *
     * Period - никак не влияет на таблицу, cache_summary_days_all
     * Agent - cache_summary_days_all, если также выбран WebMaster, то Agent отключается
     * Channel Type - detail
     * Products - detail
     * WebMaster - короче если есть вебмастер, то убираем all
     * Channel URL - channelid, detail
     *
     */
    public static function GetTableByParams($arr)
    {
        $isdetail = false;
        $isall = true;
        
        //array("period", "product", "agent", "wm", "ctype", "curl");        

        if (isset($arr["agent"]))
        {
            $isall = true;
        }
        if (isset($arr["ctype"]))
        {            
            $isdetail = true;
        }
        if (isset($arr["product"]))
        {
             $isdetail = true;
        }
        if (isset($arr["wm"]))
        {
             $isall = false;
        }
        if (isset($arr["curl"]))
        {
            $isdetail = true;
        }

        $tablename = "cache_summary_days".($isdetail?"_details":"").($isall?"_all":"");        

        // cache_summary_days
        // cache_summary_days_all
        // cache_summary_days_details
        // cache_summary_days_details_all

        return array(
            'isdetail'=>$isdetail,
            'isall'=>$isall,
            'tablename'=>$tablename);
        
    }

    public function GetDataFromTable($tn, $arr)
    {
        $database = T3Db::api();        
        
        $where = '';
        if ($tn['isdetail'])
        {
            if (isset($arr['product']))
            {
                $where.=' AND product = "'.$arr['product'].'"';
            }
            if (isset($arr['curl']))
            {
                $where.=' AND channel_id = "'.$arr['curl'].'"';
            }
            if (isset($arr['ctype']))
            {
                if ($arr['ctype'] == 'js_form')
                    $where.=' AND channel_type = "feed"';
                elseif ($arr['ctype'] == 'posting')
                    $where.=' AND channel_type = "posting"';
            }
        }

        if (!$tn['isall'])
        {
            if (isset($arr['wm']))
            {
                $where.=' AND userid = "'.$arr['wm'].'"';
            }
        }
        else
        {
            if (isset($arr['agent']))
            {                
                if ($arr['agent'] == '-')
                    $where.=' AND `for` = 0';
                elseif (is_numeric($arr['agent']))
                    $where.=' AND `for` = "'.$arr['agent'].'"';
            }
            else {
                $where.=' AND `for` = 0';     
            }
        }
        
        // select * from cache_summary_days_all where `date` between date_sub(now(),interval 7 day) and now()
        if (isset($arr['start_date']) && isset($arr['end_date']))
        {
            $arr['start_date'] = date("d.m.Y", strtotime($arr['start_date']));
            $arr['end_date'] = date("d.m.Y", strtotime($arr['end_date']));
            
            $where.=' AND (`date` >= str_to_date("'.($arr['start_date']).'", "%d.%m.%Y")) AND (`date` <= str_to_date("'.($arr['end_date']).'", "%d.%m.%Y"))';
            /*
            if ($arr['period'] == '7days')
            {
                $where.=' AND `date` between date_sub(now(),interval 7 day) and now()';
            }
            elseif ($arr['period'] == '30days')
            {
                $where.=' AND `date` between date_sub(now(),interval 30 day) and now()';
            }
            elseif ($arr['period'] == '60days')
            {
                $where.=' AND `date` between date_sub(now(),interval 60 day) and now()';
            }
            elseif ($arr['period'] == '3month')
            {
                $where.=' AND `date` between date_sub(now(),interval 90 day) and now()';
            }
            */
        }
        else
        {
            //$where.=' AND `date` between date_sub(now(),interval 7 day) and now()';
        }
        
        $sqlreq =
            'SELECT `date`, `id`, '.
            ' sum(uniqueClicks) as uniqueClicks, '.
            ' sum(all_leads) as all_leads, '.
            ' sum(sold_leads) as sold_leads, '.
            ' sum(moneyWM) as moneyWM, '.
            ' sum(moneyAgent) as moneyAgent, '.
            ' sum(moneyRef) as moneyRef, '.
            ' sum(moneyTTL) as moneyTTL, '.
            ' sum(return_leads) as return_leads, '.
            ' sum(moneyWMReturns) as moneyWMReturns '.
            ' FROM '.$tn['tablename'].
            ' WHERE 1'.$where.
            ' GROUP by `date` '.
            ' ORDER by `date` desc';
        
        //varDump($sqlreq, false);
        
        $ar = $database->fetchAll($sqlreq);
        $result = array();
        foreach($ar as $k => $v)
          $result[$v['id']] = & $ar[$k];
          
        return $result;
    }

}