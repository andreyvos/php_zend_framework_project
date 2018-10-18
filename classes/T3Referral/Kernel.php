<?php

class T3Referral_Kernel{
    public static function GetList($startDate, $endDate, $webmaster_id){
        return array();
        /*
          $res = Array();
          $listofwm = T3System::getConnect()->fetchAll('select * from users_company_webmaster where refaffid = "'.$webmaster_id.'"');

          $query = 'SELECT date, sum(money_ref) FROM cache_referral_days WHERE '.
          '(((date>="'.$startDate.'") AND (date<="'.$endDate.'")) OR ((date<="'.$startDate.'") AND (date>="'.$endDate.'"))) ';
          
          $useridcondition = '';
          
          if (count($listofwm)>0)
          {
              $i=0;
              $useridcondition = 'and (';

              foreach ($listofwm as $onelistofwm)
              {
                  if ($i>0)
                  {
                      $useridcondition.= ' OR ';
                  }
                  $useridcondition.='webmaster_id="'.$onelistofwm['id'].'"';
                  $i++;
              }
              $useridcondition.=')';
          }

          $query.=$useridcondition;
          $query.=" GROUP by date";
          $res = T3System::getConnect()->fetchAll($query);
          


          $_startDate = strtotime($startDate);
          $daysRangeCount = ((strtotime($endDate) - $_startDate)/(60*60*24));
          $result = "";
          
          for ($i = 0; $i <= $daysRangeCount; $i++)
          {
              $result[$i] = Array(
                'date' => date("Y-m-d", $_startDate + $i * (60 * 60 * 24)),
                'sum(money_ref)'=> '0'
              );
          }

          foreach ($res as $var => $value)
          {
              for ($i = 0; $i <= $daysRangeCount; $i++)
              {
                  if ($res[$var]['date'] == $result[$i]['date'])
                  {
                      $result[$i] = $res[$var];
                  }
              }
          }

          $res = $result;

          return $res;
        */
  }

}
