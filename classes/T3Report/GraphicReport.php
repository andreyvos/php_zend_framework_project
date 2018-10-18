<?php



class T3Report_GraphicReport {

  public static function GetData($buyerId, $periodBeg, $periodEnd, $periodsNumber){


    $periodLength = (int)floor((strtotime($periodEnd)-strtotime($periodBeg))/86400)+1;    


    $daysSum = $periodLength*$periodsNumber;

    $periodEndZd = new Zend_Date(strtotime($periodEnd));

    $lowDate = clone $periodEndZd;
    $lowDate->subDay($daysSum-1);

    $channelsData = T3Db::api()->fetchAll('
      select id, title from buyers_channels where buyer_id = ? && !isDeleted order by title
    ', array($buyerId));
    
    if(empty($channelsData))
      return array();

    $channelsIds = array();
    $channelsDataIndexed = array();
    foreach($channelsData as $v){
      $channelsIds[] = $v['id'];
      $channelsDataIndexed[$v['id']] = $v;
    }
    
    $channelsIdsString = implode(', ', $channelsIds);

    $data = T3System::getConnectCache()->fetchAll("
      select *, $periodsNumber - 1 - (DATEDIFF(?, `date`) DIV $periodLength) AS period from buyer_channel_convert
      where channel_id in ($channelsIdsString)
      and `date` <= ? and `date` >= ?
      ORDER BY channel_id, `date`
    ", array($periodEndZd->toString(MYSQL_DATE_FORMAT_ZEND), $periodEndZd->toString(MYSQL_DATE_FORMAT_ZEND), $lowDate->toString(MYSQL_DATE_FORMAT_ZEND)));


    $resultData = array();
    foreach($channelsIds as $v){
      $resultData[$v] = array();
      for($i1=0;$i1<$periodsNumber;$i1++){
        $resultData[$v][$i1] = array(
          'sent' => 0,
          'sold' => 0,
          'ratio' => null,
        );
      }
    }

    
    foreach($data as $v){
      $row = &$resultData[$v['channel_id']][$v['period']];  
      $row['sent']+=$v['send'];
      $row['sold']+=$v['sold'];
    }


    foreach($resultData as $k1 => $v1)
      foreach($v1 as $k2 => $v2)
        if($v2['sent']>0){
          $resultData[$k1][$k2]['ratio'] = $v2['sold']/$v2['sent'];
          $resultData[$k1][$k2]['ratioString'] = round($v2['sold']/$v2['sent']*100, 2) . " %";
        }


    $zd1 = clone $periodEndZd;
    $zd2 = clone $periodEndZd;
    $zd2->subDay($periodLength-1);
    $periods = array();
    for($i1=0;$i1<$periodsNumber;$i1++){
      $periods[$i1] = array(
        'id' => $i1,
        'title' => DateFormat::dateOnly($zd2->toString(MYSQL_DATETIME_FORMAT_ZEND)) . " .. " . DateFormat::dateOnly($zd1->toString(MYSQL_DATETIME_FORMAT_ZEND)),
      );
      $zd1->subDay($periodLength);
      $zd2->subDay($periodLength);
    }
    $periods = array_reverse($periods);

    return array(
      'data' => $resultData,
      'legend' => $periods,
      'periodsCount' => count($periods),
      'channels' => $channelsDataIndexed,
    );

  }

}