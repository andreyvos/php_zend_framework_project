<?php

class T3Report_PostingsConvert {
    static public function reindexAll($date){
        $postings = T3Db::api()->fetchAll("select id from buyers_channels");

        foreach($postings as $posting){
            self::reindex($date, $posting['id']);        
        }    
    }
    
    static protected function calculatePrecent($sold, $send){
        if($send == 0){
            return "0";
        }
        else {
            return round($sold/$send, 4)* 100;        
        }    
    }
    
    static public function reindex($date, $posting){
        $filtered = 0;
        $duplicated = 0; 
        //$filtered       =   self::getValue($posting, $date, 'isFiltered');
        //$duplicated     =   self::getValue($posting, $date, 'isDuplicated');
        $send           =   self::getValue($posting, $date, 'isSend');
        $sold           =   self::getValue($posting, $date, 'isSold'); 
        
        $persents = self::calculatePrecent($sold, $send);
        
        $array = array(
            'filtered'   => $filtered,
            'duplicated' => $duplicated,
            'send'       => $send,
            'sold'       => $sold,
            'persents'   => $persents,
        );
        
        try{
            T3Db::cache()->insert("buyer_channel_convert", array('date' => $date, 'channel_id' => $posting) + $array );
        }
        catch(Exception $e){
            T3Db::cache()->update("buyer_channel_convert", $array, "`date` = '{$date}' and channel_id = '{$posting}'");    
        } 
        
        self::merged($date, $posting, $array);  
    }
    
    static protected function merged($date, $posting, $array){
        $old = T3Db::cache()->fetchRow("select * from buyer_channel_convert_v1_sync where `date`='{$date}' and channel_id='{$posting}'");
        
        if($old){
            $array = array(
                'filtered'   => $array['filtered'] + $old['filtered'],
                'duplicated' => $array['duplicated'] + $old['duplicated'],
                'send'       => $array['send'] + $old['send'],
                'sold'       => $array['sold'] + $old['sold'],
                'persents'   => self::calculatePrecent($array['sold'] + $old['sold'] , $array['send'] + $old['send']),
            );    
        }  
        
        try{
            T3Db::cache()->insert("buyer_channel_convert_merge", array('date' => $date, 'channel_id' => $posting) + $array );
        }
        catch(Exception $e){
            T3Db::cache()->update("buyer_channel_convert_merge", $array, "`date` = '{$date}' and channel_id = '{$posting}'");    
        }   
    }
    
    static protected function getValue($channelID, $date, $Status){        
        if($Status == 'isSold'){
            return (int)T3Db::api()->fetchOne(
                "select sum(records_count) from buyers_statistics_grouped where buyer_channel_id=? and record_date=? and post_result_status='Sold'",
                array($channelID, $date)
            );    
        }
        else if($Status == 'isSend'){
              return (int)T3Db::api()->fetchOne(
                "select sum(records_count) from buyers_statistics_grouped where buyer_channel_id=? and record_date=? and post_result_status in ('Sold', 'Rejected')",
                array($channelID, $date)
            );
        }
        
        return 0;  
    }
    
    static public function v1sync($date){
        $allPostings = T3Db::v1()->fetchAll("select idposting,idBuyerNewT3Leads,title,`types` from posting where idBuyerNewT3Leads>0");
        
        $startID  = T3Db::v1()->fetchOne("select idlead from stat where TO_DAYS(stat.`leaddatetime`) = TO_DAYS('{$date}') order by idlead asc limit 1");
        $finishID = T3Db::v1()->fetchOne("select idlead from stat where TO_DAYS(stat.`leaddatetime`) = TO_DAYS('{$date}') order by idlead desc limit 1");
        
        foreach($allPostings as $posting){
            self::v1sync_One($date, $posting['idposting'], $posting['idBuyerNewT3Leads'], $posting['title'], $posting['types'], $startID, $finishID);
        }        
    }
    
    static protected function v1sync_One($date, $postingOldId, $postingNewId, $oldTitle, $oldProduct, $startID, $finishID){
        $filtered       =   self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'filter'");
        $duplicated     =   self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'duplicate'"); 
        $send           =   self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'sold','pending','reject'"); 
        $sold           =   self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'sold'");  

        $persents = self::calculatePrecent($sold, $send);
        
        try{
            T3Db::cache()->insert("buyer_channel_convert_v1_sync", array(
                'date'       => $date,
                'channel_id' => $postingNewId,
                'filtered'   => $filtered,
                'duplicated' => $duplicated,
                'send'       => $send,
                'sold'       => $sold,
                'persents'   => $persents,
            ));
        }
        catch(Exception $e){
            varExport($e->getMessage());
            
            T3Db::cache()->update("buyer_channel_convert_v1_sync", array(
                'filtered'   => $filtered,
                'duplicated' => $duplicated,
                'send'       => $send,
                'sold'       => $sold,
                'persents'   => $persents,
            ), "`date` = '{$date}' and channel_id = '{$postingNewId}'");    
        }   
    }
    
    static protected function v1getValue($product, $startID, $finishID, $titlePosting, $status){
        return T3Db::v1()->fetchOne("SELECT
count(*)
FROM
    stat
    INNER JOIN posting_cache 
        ON (stat.idlead = posting_cache.idlead)
where
stat.`idlead` >= {$startID} and
stat.`idlead` <= {$finishID} and
stat.type = '{$product}' and
posting_cache.posting = '{$titlePosting}' and
posting_cache.status in ({$status})");    
    }
    
    
    
    static public function mainStat($product, $table, $dateRange = null, $buyer = null, $forCalendar = false, $showType = 'full'){
        if(is_object($dateRange) && $dateRange instanceof T3MyValidator_DateRange){
            
        }
        else {
            $dateRange = new T3MyValidator_DateRange();
            $dateRange->from_delta_days = -11;
            $dateRange->till_delta_days = -1;
            $dateRange->setDefault();   
        }
        
        /** @var Zend_Db_Select */
        $selectPostings = T3Db::cache()->select();
        $selectPostings
        ->from('buyers_channels', array(
            'id', 
            new Zend_Db_Expr("(select users_company_buyer.systemName from users_company_buyer where users_company_buyer.id=buyers_channels.buyer_id) as Buyer"),
            'Posting' => 'title', 
            'Min Price' => 'minConstPrice',  
            'buyers_channels.ratioSendVsSold' 
        ))
        ->where("(`status` = 'active' or `status` = 'paused')")
        ->order("Buyer")
        ->order("title");
        
        if($buyer){
            $selectPostings->where("buyer_id=?", $buyer);    
        }
        
        if(strlen($product)){
            $selectPostings->where('product=?', $product);   
        }
        
        if(T3Users::getCUser()->isRoleBuyerAgent()){
            $selectPostings->where("product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')");   
        }

        $selectPostings->where('!isDeleted');

        $postings = T3Db::api()->fetchAll($selectPostings);
        
        $postingsID = array();
        foreach($postings as &$posting){
            $postingsID[] = $posting['id'];     
        }



        $roundedDateFromWeeklyZd = new Zend_Date($dateRange->dateFrom, MYSQL_DATE_FORMAT_ZEND);        
        $tmpZd = new Zend_Date($dateRange->dateFrom, MYSQL_DATE_FORMAT_ZEND);
        $tmpZd->setWeekday(7);
        $tmpZdString = $tmpZd->toString(MYSQL_DATE_FORMAT_ZEND);
        if($tmpZdString>=$dateRange->dateFrom && $tmpZdString<=$dateRange->dateTill)
          $roundedDateFromWeeklyZd->setWeekday(1);

        $roundedDateFromMonthlyZd = new Zend_Date($dateRange->dateFrom, MYSQL_DATE_FORMAT_ZEND);
        $tmpZd = new Zend_Date($dateRange->dateFrom, MYSQL_DATE_FORMAT_ZEND);
        $tmpZd->addMonth(1);
        $tmpZd->setDay(1);
        $tmpZd->subDay(1);
        $tmpZdString = $tmpZd->toString(MYSQL_DATE_FORMAT_ZEND);
        if($tmpZdString>=$dateRange->dateFrom && $tmpZdString<=$dateRange->dateTill)
          $roundedDateFromMonthlyZd->setDay(1);

        if(
          $roundedDateFromWeeklyZd->toString(MYSQL_DATE_FORMAT_ZEND)<
          $roundedDateFromMonthlyZd->toString(MYSQL_DATE_FORMAT_ZEND)
        )
          $roundedDateFromZd = $roundedDateFromWeeklyZd;
        else
          $roundedDateFromZd = $roundedDateFromMonthlyZd;

        


        $data = T3Db::cache()->fetchAll(
            "select `date`,channel_id,send,sold,persents from {$table} where TO_DAYS(`date`) >= TO_DAYS(?) and TO_DAYS(`date`) <= TO_DAYS(?) and channel_id in ('" . implode("','", $postingsID) . "')",
            array(
                $roundedDateFromZd->toString(MYSQL_DATETIME_FORMAT_ZEND),  //$dateRange->dateFrom,
                $dateRange->dateTill,
            )
        );



        
        $zd = new Zend_Date();
        if($dateRange->dateTill == $zd->toString(MYSQL_DATE_FORMAT_ZEND)){
          $todayDataV2 = T3BuyersStats::getInstance()->getTodaySummaryForBuyerChannels($buyer);
          $data = array_merge($data, $todayDataV2);
        }
        

        
        $dataIndex = array();
        $totalForNotification = array();
        foreach($data as &$el){
            $dataIndex[$el['channel_id']][$el['date']] = $el;
            if(!isset($totalForNotification[$el['channel_id']])) $totalForNotification[$el['channel_id']] = array('send'=>0, 'sold'=>0); 
            $totalForNotification[$el['channel_id']]['send']+= $el['send'];
            $totalForNotification[$el['channel_id']]['sold']+= $el['sold'];      
        }                                                                                                                
        
        /*$datas = array();
        for($i = (-1)*$dateRange->dateFrom_Delta; $i >= (-1)*$dateRange->dateTill_Delta; $i--){
            $mktime = mktime(0,0,0, date('m'), date('d') - $i, date('Y'));
            $date = date("Y-m-d", $mktime);
            $dateFormat = DateFormat::dateOnly($date, false); 
            
            $datas[] = array(
                'date'   => $date,
                'format' => $dateFormat,
                'mktime' => $mktime,
            );
        }*/



        $datas = array();
        $currentDateZd = clone $roundedDateFromZd;

        while($currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND)<=$dateRange->dateTill){

          $datas[] = array(
            'show' => $currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND)>=$dateRange->dateFrom,
            'date'   => $currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND),
            'format' => DateFormat::dateOnly($currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND), false),
            'mktime' => $currentDateZd->getTimestamp(),
            'is_special' => false,
            'is_week_beginning' => $currentDateZd->get(Zend_Date::WEEKDAY_8601)==1,
            'is_month_beginning' => $currentDateZd->get(Zend_Date::DAY)==1,
          );

          if($forCalendar){

            if($currentDateZd->get(Zend_Date::WEEKDAY_8601)==7){

              $datas[] = array(
                'show' => $currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND)>=$dateRange->dateFrom,
                'format' => "Weekly Average <div style='display:none'>".randomString()."</div>",
                'is_special' => true,
                'is_weekly' => true,
                'is_monthly' => false,
                'is_up_to_date' => false,
              );

            }

            $tmpZd = clone $currentDateZd;
            $tmpZd->addDay(1);
            if($tmpZd->get(Zend_Date::DAY)==1){

              $tmpZd = clone $currentDateZd;
              $tmpZd->setDay(1);

              $datas[] = array(
                'show' => $currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND)>=$dateRange->dateFrom,
                'format' => "Monthly Average <div style='display:none'>".randomString()."</div>",
                'is_special' => true,
                'is_weekly' => false,
                'is_monthly' => true,
                'is_up_to_date' => false,
                'monthKey' => $tmpZd->toString(MYSQL_DATE_FORMAT_ZEND),
              );

            }

          }

          $currentDateZd->addDay(1);


        }

        if($forCalendar){

          $datas[] = array(
            'show' => 1,
            'format' => "Up-to-Date",
            'is_special' => true,
            'is_weekly' => false,
            'is_monthly' => false,
            'is_up_to_date' => true,
          );

        }




        $onlyShowingDatesRev = array();
        $specialDatesKeys = array();
        foreach($datas as $k => $v){
          if($v['show'])
            $onlyShowingDatesRev[] = $v;
          if($v['is_special'])
            $specialDatesKeys[] = $v['format'];
        }
        $onlyShowingDatesRev = array_reverse($onlyShowingDatesRev);

        foreach($postings as &$posting){
            $temp = array(
                'total_send' => ifset($totalForNotification[$posting['id']]['send'], 0),
                'total_sold' => ifset($totalForNotification[$posting['id']]['sold'], 0),
            );
            
            $ss = $temp['total_sold'];
            if($ss == 0)$ss = 1;
            $temp['total_ratio'] = ceil($temp['total_send']/$ss);
            $temp['recomend_is_send'] = ceil($temp['total_ratio']*3); 
            
            if($temp['recomend_is_send'] == 0){
                $temp['status'] = "<span style='color:#CCC'>Unknown</span>";       
            }
            else if($posting['ratioSendVsSold'] < $temp['total_ratio']*1.3){
                $temp['status'] = "<span style='color:#F00'>Low</span>";    
            } 
            else if($posting['ratioSendVsSold'] < $temp['recomend_is_send']){
                $temp['status'] = "<span style='color:#666'>Norm</span>";
            } 
            else if($posting['ratioSendVsSold'] > $temp['total_send']*6){
                $temp['status'] = "<span style='color:#F00'>Many</span>";
            }
            else {
                $temp['status'] = "<span style='color:#090'>Good</span>";      
            }
        

            $weeklySold = 0;
            $weeklySent = 0;
            $monthlySold = 0;
            $monthlySent = 0;
            $totalSold = 0;
            $totalSent = 0;

            $last = array();
            foreach($datas as &$date){

                if(!$date['is_special']){
                  if($date['is_week_beginning']){
                    $weeklySent = 0;
                    $weeklySold = 0;
                  }
                  if($date['is_month_beginning']){
                    $monthlySent = 0;
                    $monthlySold = 0;
                  }

                  $el =& $dataIndex[$posting['id']][$date['date']];
                  $thereIsData = isset($el);
                  if($thereIsData){

                    $sent = $el['send'];
                    $sold = $el['sold'];
                    $thisPercents = $el['persents'];
                    $thereIsData = $sent != 0;
                    $lastPercent = ifset($last[$posting['id']], null);

                    $weeklySent += $sent;
                    $weeklySold += $sold;
                    $monthlySent += $sent;
                    $monthlySold += $sold;
                    
                    if($date['show']){
                      $totalSent += $sent;
                      $totalSold += $sold;
                    }

                  }

                }else {

                  if($date['is_weekly']){

                    $sent = $weeklySent;
                    $sold = $weeklySold;
                    if($weeklySent!=0){
                      $thisPercents = round($weeklySold/$weeklySent*100, 2);
                      $thereIsData = true;
                    }else{
                      $thereIsData = false;
                    }
                    $lastPercent = null;
                    
                  }else if($date['is_monthly']){

                    $thereIsData = true;
                    $sent = $monthlySent;
                    $sold = $monthlySold;
                    if($monthlySent!=0){
                      $thisPercents = round($monthlySold/$monthlySent*100, 2);
                      $thereIsData = true;
                    }else{
                      $thereIsData = false;
                    }
                    $lastPercent = null;

                  }else if($date['is_up_to_date']){

                    $thereIsData = true;
                    $sent = $totalSent;
                    $sold = $totalSold;
                    if($totalSent!=0){
                      $thisPercents = round($totalSold/$totalSent*100, 2);
                      $thereIsData = true;
                    }else{
                      $thereIsData = false;
                    }
                    $lastPercent = null; 

                  }

                }

                if($date['show']){
                  if($thereIsData){

                      $redDec   = 0;
                      $greenDec = 0;
                      $blueDec  = 0;
                      $ratioColor = '#666';
                      if($date['is_special']){
                        $redDec   = 0;
                        $greenDec = 0;
                        $blueDec  = 0;
                        $ratioColor = '#666';
                      }
                      else if(is_null($lastPercent) || $lastPercent == 0){
                          if($thisPercents == 0){
                              $redDec   = 200;
                              $greenDec = 200;
                              $blueDec  = 200;
                          }
                          else {
                              $redDec   = 120;
                              $greenDec = 120;
                              $blueDec  = 120;
                          }

                      }
                      else if($thisPercents == 0){
                          // проверка подходит ли постинг по там фильтру
                          if($sent > 0){
                              $redDec = 255;
                              $greenDec = 0;
                              $blueDec  = 0;
                          }
                          else {
                              $redDec   = 200;
                              $greenDec = 200;
                              $blueDec  = 200;
                          }
                      }

                      $red   =  str_pad(dechex($redDec),   2, "0", STR_PAD_LEFT);
                      $green =  str_pad(dechex($greenDec), 2, "0", STR_PAD_LEFT);
                      $blue  =  str_pad(dechex($blueDec),  2, "0", STR_PAD_LEFT);

                      $displaySentSoldRatioString = !isset($_GET['show_sent_sold_ratio']) || $_GET['show_sent_sold_ratio'] ? '' : 'display:none';

                      if(!$forCalendar){
                          if($showType == 'alp'){
                              $temp[$date['format']] = "<span style='color:#BBB'>$</span> " . round($sold * $posting['Min Price'] / $sent, 2);    
                          }
                          else if($showType == 'postSold'){
                              $temp[$date['format']] = "<b style='color:#{$red}{$green}{$blue}'>{$thisPercents} <span style='color:#BBB'>%</span></b> " .
                                                       "<span style='color:$ratioColor;$displaySentSoldRatioString'>{$sent}:{$sold}</span>";    
                          }
                          else if($showType == 'onlyPerc'){
                              $temp[$date['format']] = "<span style='color:#{$red}{$green}{$blue}'>$thisPercents</span> <span style='color:#BBB'>%</span>";    
                          }
                          else {
                              $temp[$date['format']] = "<b style='color:#{$red}{$green}{$blue}'>{$thisPercents} <span style='color:#BBB'>%</span></b> " . 
                                                       "<span style='color:$ratioColor;$displaySentSoldRatioString'>{$sent}:{$sold}</span> <span style='color:#BBB'>($</span> <b>" . 
                                                       round($sold * $posting['Min Price'] / $sent, 2) .  "</b><span style='color:#BBB'>)</span>";
                          }
                      }
                      else{
                        $temp[$date['format']] = array(
                          'thereIsData' => true,
                          'ratio' => $thisPercents,
                          'sent' => $sent, 
                          'sold' => $sold,
                        );
                      }
                      $last[$posting['id']] = $thisPercents;
                      
                  }
                  else {
                    if(!$forCalendar){
                      $temp[$date['format']] = "-";
                    }else{
                      $temp[$date['format']] = array(
                        'thereIsData' => false,
                      );
                    }
                      $last[$posting['id']] = null;
                  }
                }
            }  
            $posting = $posting + array_reverse($temp);         
        }
        
        //varExport($postings);
        
        return array(
            'dates'     => $onlyShowingDatesRev,
            'postings'  => $postings,
            'specialKeys' => $specialDatesKeys,
        );    
    }

    public static function RegroupDataForCalendar(&$data){

      //vvv($data);

      $weeks = array();
      $currentWeekKey = null;

      $begin = true;

      $monthlyAveragesDates = array();

      $dates = $data['dates'];
      foreach($dates as $date){
        $lastDate = $date;
        if(!$date['is_special'])
          break;
      }
      $lastDateZd = new Zend_Date($lastDate['date'], MYSQL_DATE_FORMAT_ZEND);
      $lastDateWeekEndZd = clone $lastDateZd;
      $lastDateWeekEndZd->setWeekday(7);

      $dates = array_reverse($dates);

      if($lastDateWeekEndZd->toString(MYSQL_DATE_FORMAT_ZEND) > mySqlDateFormat()){

        $currentDateZd = clone $lastDateZd;
        for(;;){

          if($currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND)>$lastDateWeekEndZd->toString(MYSQL_DATE_FORMAT_ZEND))
            break;

          $dates[] = array(
            'show' => false,
            'date'   => $currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND),
            'format' => DateFormat::dateOnly($currentDateZd->toString(MYSQL_DATE_FORMAT_ZEND), false),
            'mktime' => $currentDateZd->getTimestamp(),
            'is_special' => false,
            'is_week_beginning' => false,
            'is_month_beginning' => false,
            'in_future' => true,
          );

          $currentDateZd->addDay(1);

        }
      }

      



      foreach($dates as $k => $v){
        if(!$v['is_special']){

          $date = $v['date'];
          $dateZd = new Zend_Date($date, MYSQL_DATE_FORMAT_ZEND);

          if(empty($currentMonthKey)){
            $zd1 = new Zend_Date($date, MYSQL_DATE_FORMAT_ZEND);
            $zd1->setDay(1);
            $currentMonthKey = $zd1->toString(MYSQL_DATE_FORMAT_ZEND);
          }else if($dateZd->get(Zend_Date::DAY)==1){
            $currentMonthKey = $date;
          }

          if($dateZd->get(Zend_Date::WEEKDAY_8601)==1){
            $currentWeekKey = $date;
            $weeks[$currentWeekKey] = array(
              'dates' => array(),
              'summary' => null,
            );
          }

          if(!empty($currentWeekKey)){
            $weeks[$currentWeekKey]['dates'][$date] = array(
              'weekKey' => $currentWeekKey,
              'monthKey' => $currentMonthKey,
              'date' => $date,
              'show' => $v['show'],
              'format' => $v['format'],
              'dayOfMonth' => $dateZd->get(Zend_Date::DAY),
              'month' => $dateZd->get(Zend_Date::MONTH),
            );
          }

        }else if($v['is_weekly']){
          if(!empty($currentWeekKey)){
           $weeks[$currentWeekKey]['summary'] = $v;
          }
        }else if($v['is_monthly']){
          $monthlyAveragesDates[] = $v;
        }
      }


      $postingsData = array();

      foreach($data['postings'] as $v){

        $postingData = array(
          'id' => $v['id'],
          'buyer_systemName' => $v['Buyer'],
          'buyer_channel_title' => $v['Posting'],
          'minPrice' => $v['Min Price'],
          'monthlyAverages' => array(),
          'dataArray' => array(),
        );
        foreach($monthlyAveragesDates as $v1){
          $postingData['monthlyAverages'][] = $v[$v1['format']]+array('monthKey'=>$v1['monthKey']);
        }
        
        foreach($weeks as $weekKey => $weekData){
          $postingData['dataArray'][$weekKey] = array(
            'days' => array(),
            'summary' => !empty($weekData['summary']) ? $v[$weekData['summary']['format']] : null,
          );
          foreach($weekData['dates'] as $dateKey => $dateData){
            $postingData['dataArray'][$weekKey]['days'][$dateKey] = array(
              'dayOfMonth' => $dateData['dayOfMonth'],
              'month' => $dateData['month'],
            );
            if(isset($v[$dateData['format']])){
              foreach($v[$dateData['format']] as $k1 => $v1)
                $postingData['dataArray'][$weekKey]['days'][$dateKey][$k1] = $v1;
            }else
              $postingData['dataArray'][$weekKey]['days'][$dateKey]['thereIsData'] = false;
          }          
        }

        $postingsData[] = $postingData;

      }

      return $postingsData;

    }
    
    static public function getOldInfo($posting, $date){
        $result = array(
            'send' => '0',
            'sold' => '0',
        );
        
        if(T3Synh_Runv2v1::isRun()){
            $oldTitle = T3Db::v1()->fetchOne("select `title` from posting where idBuyerNewT3Leads=?", $posting);
            $oldProduct = T3Db::v1()->fetchOne("select `types` from posting where idBuyerNewT3Leads=?", $posting);
            
            if($oldTitle){
                $startID  = T3Db::v1()->fetchOne("select idlead from stat where TO_DAYS(stat.`leaddatetime`) = TO_DAYS('{$date}') order by idlead asc limit 1");
                $finishID = T3Db::v1()->fetchOne("select idlead from stat where TO_DAYS(stat.`leaddatetime`) = TO_DAYS('{$date}') order by idlead desc limit 1");  
                
                $result = array(
                    'send' => self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'sold','pending','reject'"),
                    'sold' => self::v1getValue($oldProduct, $startID, $finishID, $oldTitle, "'sold'"),
                );      
            }
        }
        
        return $result;    
    }
}