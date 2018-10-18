<?php

class T3CallCenter_Management extends T3CallCenter_Abstract {

    public $database = null;

    public function __construct(){
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();

        $this->database = T3Db::api();
    }

    public function GetActiveList($userId){
        return array();
        /*
        return $this->database->fetchAll('select * from call_center_list_of_active_leads where userID="'.$userId.'"');
        */
    }

    public function InsertNewLead($leadID, $_from = "", $_reason = "", $_more_info = ""){
        /*
        // Добавляем новый лид в call_center_list_of_waiting_leads
        
        $l = $this->database->fetchAll('select * from leads_data where id="'.$leadID.'"');
        $l = $l[0];
        $ld = $this->database->fetchAll('select * from leads_data_'.$l['product'].' where id="'.$leadID.'"');
        $ld = $ld[0];
        
        $a = array(
            'leadID'=>$l['id'],
            'createDate' => $l['datetime'],
            'startDate' => date("Y-m-d H:i:s"),
            'number_busy_count' => "0",
            'timezone_code' => "0", //$ld['timezone_code']
            'lead_product' => $l['product'],
            'home_phone' => $ld['home_phone'],
            'work_phone' => $ld['work_phone'],
            'work_phone_ext' => $ld['work_phone_ext'],

            'cell_phone' => $ld['cell_phone'],
            'best_time_to_call' => $ld['best_time_to_call'],
            'from' => $_from,
            'reason' => $_reason,
            
            'more_info' => $_more_info
        );

        $this->database->insert('call_center_list_of_waiting_leads', $a);

        // vvv($l, false);
        // vvv($ld, false);
        */

    }

  public function LoadListFromWaitingLeads($ableToBeActive, $sortOrder = "asc", $count = 0){
      // Загружает список лидов из

      // Необходимые возможности:
      // 1) $ableToBeActive = загружать только те которые можно перевести в активные или все
      // 2) $count = кол-во
      // 3) $sortOrder = как сортировать
      return array();
      /*
      $q = "select * from call_center_list_of_waiting_leads order by id ".$sortOrder."".($count>0?" LIMIT ".$count:"");
      return $this->database->fetchAll($q);
      */
  }

  public function MoveFromWaitingToActive($list, $userId){
      // Перемещает список лидов из режима ожидания в режим активности
      /*
      foreach ($list as $lvar => $lval)
      {
           $this->database->insert('call_center_list_of_active_leads',
              array(
                'leadID'=>$lval['leadID'],
                'createDate'=>$lval['createDate'],
                'number_busy_count'=>$lval['number_busy_count'],
                'timezone_code'=>$lval['timezone_code'],
                'lead_product'=>$lval['lead_product'],
                'home_phone'=>$lval['home_phone'],
                'work_phone'=>$lval['work_phone'],
                'work_phone_ext'=>$lval['work_phone_ext'],
                'cell_phone'=>$lval['cell_phone'],
                'best_time_to_call'=>$lval['best_time_to_call'],
                'userID'=>$userId,
                'from' => $lval['from'],
                'more_info' => $lval['more_info'],
              ));
          $this->database->delete('call_center_list_of_waiting_leads', 'leadID="'.$lval['leadID'].'"' );
          //$this->database->delete('call_center_list_of_waiting_leads', array('leadID'=>$lval['leadID']));
      }
      */
  }

  public function ApplyStatusToActiveLead(
      $leadID,
      $status,
      $comment_code,
      $comment_text,
      $comment_for_agent,
      $history_work
      )
  {
      /*
      if (($status == "approved") || ($status == "rejected"))
      {
          // Перемнщанм из active_leads в processed_leads

          $l = $this->database->fetchAll('select * from call_center_list_of_active_leads where leadID="'.$leadID.'"');
          $l = $l[0];

          $a = array(
            'leadID' => $l['leadID'],
            'userID' => $l['userID'],
            'createDate' => $l['createDate'],
            'number_busy_count' => $l['number_busy_count'],
            'timezone_code' => $l['timezone_code'],
            'home_phone' => $l['home_phone'],
            'work_phone' => $l['work_phone'],
            'work_phone_ext' => empty($l['work_phone_ext'])?"":$l['work_phone_ext'],
            'cell_phone' =>  empty($l['cell_phone'])?"":$l['cell_phone'],
            'best_time_to_call' => $l['best_time_to_call'],
            'lead_product' => $l['lead_product'],

            'status' => $status,
            'comment_code' => '0',
            'comment_text' => $comment_text,
            'comment_for_agent' => $comment_for_agent,
            'history_work' => $history_work,

            'from' => $l['from'],
            'more_info' => $l['more_info']

          );


          $this->database->insert('call_center_list_of_processed_leads', $a);

          // Тут будем выполнять действие после того как лид был отправлен в обработанные

          T3CallCenter_AfterBecameProcessed::CallAction($a, $l['from'], $l['more_info']);

          if (false)
          {
              $this->database->delete('call_center_list_of_active_leads', 'leadID="'.$lval['leadID'].'"' );
          }
                    

      }
      else
      {
          // Это значит что лид возвращается назад waiting_leads
      }
      */
  }

  public function AddEntryIntoLog($leadID, $from, $to){
      // call_center_log
      // Данная функция отвечает за историю перемещения лидов среди трех таблиц
      // from/to = [waiting, active, processed]
  }

  public function RewindAllTask($userId){
    //$this->Rewind($userId);
  }


    public function Rewind($userId, $leadID=""){
      /*
      // отправить активные лиды назад
      $q = "select * from call_center_list_of_active_leads where (userID='".$userId."') ".($leadID!=""?(" AND (leadID='".$leadID."')"):"")."";
      $list = $this->database->fetchAll($q);

      foreach ($list as $lvar => $lval)
      {
           $this->database->insert('call_center_list_of_waiting_leads',
              array(
                'leadID'=>$lval['leadID'],
                'createDate'=>$lval['createDate'],
                'number_busy_count'=>$lval['number_busy_count'],
                'timezone_code'=>$lval['timezone_code'],
                'lead_product'=>$lval['lead_product'],
                'home_phone'=>$lval['home_phone'],
                'work_phone'=>$lval['work_phone'],
                'work_phone_ext'=>$lval['work_phone_ext'],
                'cell_phone'=>$lval['cell_phone'],
                'best_time_to_call'=>$lval['best_time_to_call'],
                'from' => $lval['from'],
                'more_info' => $lval['more_info']
                //'userID'=>$userId
              ));
          $this->database->delete('call_center_list_of_active_leads', 'leadID="'.$lval['leadID'].'"');
      }
      */
    }

}