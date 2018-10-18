<?php


class T3Report_CalendarReport{


  public static function GetData($startDate, $finishDate){

    $startDateZd = new Zend_Date(strtotime($startDate));
    // Zend_Date::WEEKDAY_8601
    $startDateZd->setWeekday(1);

    $actualStartDate = $startDateZd->toString(MYSQL_DATE_FORMAT_ZEND);

    $finishDateZd = new Zend_Date(strtotime($finishDate));
    $finishDateZd->setWeekday(7);
    $actualFinishDate = $finishDateZd->toString(MYSQL_DATE_FORMAT_ZEND);


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


  }


}