<?php

class T3PostErrorsReporting {

  /*
PostErrorsReporting_agent

webmaster_id => {webmaster_id}
leadsErrors => {leadsErrors}
all_leads => {all_leads}
Percents => {Percents}
webmaster_systemName => {webmaster_systemName}
post_errors_emails => {post_errors_emails}
agent_contactEmail => {agent_contactEmail}
webmaster_email => {webmaster_email}
agentID => {agentID}

array_snapshot => {array_snapshot}
   */

  protected static $_instance;

  public $testMode = false;//true;


  protected function __construct(){
    $this->database = T3Db::api();
  }

  /** @return T3PostErrorsReporting */
  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function getTimeBound(&$zdDate, &$zdDateLastSecond){

    $zd = new Zend_Date();
    $zd->subDay(1);

    $zdDate = clone $zd;
    $zdDate->setTime('00:00:00');

    $zdDateLastSecond = clone $zd;
    $zdDateLastSecond->setTime('23:59:59');

  }

  public function getErrorReport($zdDate, $zdDateLastSecond){


    $result = $this->database->fetchAll("

SELECT tmp1.webmaster AS webmaster_id , tmp1.leadsErrors, tmp1.all_leads, tmp1.Percents ,
  ucw.post_errors_emails,
  ucw.post_errors_agent_min_percent,
  ucw.post_errors_wm_min_percent,
  ucw.post_errors_agent_on,
  ucw.post_errors_wm_on,
  ucw.agentID,
  uwa.contactEmail as agent_contactEmail,
  u.email as webmaster_email,
  ucw.systemName as webmaster_systemName

FROM (

      select mainData.*,cache_summary_days.all_leads, round((mainData.leadsErrors/cache_summary_days.all_leads)*100, 1) as Percents
      from (select channels_errors.webmaster, round(sum(1/channels_errors.lead_errors_count)) as leadsErrors from channels_errors
      where channels_errors.importance='critical' && channels_errors.create_datetime between ? and ?
      group by channels_errors.webmaster) as mainData
      inner join cache_summary_days on (cache_summary_days.userid = mainData.webmaster) where
      cache_summary_days.date=date(?)
      group by Percents desc

) tmp1

LEFT JOIN users_company_webmaster ucw ON tmp1.webmaster=ucw.id
LEFT JOIN users_webmaster_agents uwa ON ucw.agentID = uwa.id
LEFT JOIN users u ON u.role='webmaster' AND u.company_id = ucw.id


    ", array(
      $zdDate->toString(MYSQL_DATETIME_FORMAT_ZEND),
      $zdDateLastSecond->toString(MYSQL_DATETIME_FORMAT_ZEND),
      $zdDate->toString(MYSQL_DATETIME_FORMAT_ZEND)
    ));

    return $result;

  }
  
  public function performSending(){

    $this->getTimeBound($zdDate, $zdDateLastSecond);

    $report = $this->getErrorReport($zdDate, $zdDateLastSecond);

    $settings = $this->getSettings();

    foreach($report as $v){


      $dateGetParam = urlencode(DateFormat::dateOnly($zdDate->toString(MYSQL_DATETIME_FORMAT_ZEND)));
      $varsArray = array (
        'webmaster_id' => $v['webmaster_id'],
        'nickname' => T3Cache_CompanyUserContacts::getNickname($v['webmaster_id'], false),
        'leadsErrors' => $v['leadsErrors'],
        'all_leads' => $v['all_leads'],
        'Percents' => $v['Percents'],
        'webmaster_systemName' => $v['webmaster_systemName'],
        'post_errors_emails' => $settings['post_errors_emails'],
        'agent_contactEmail' => $v['agent_contactEmail'],
        'webmaster_email' => $v['webmaster_email'],
        'agentID' => $v['agentID'],
        'report_link' =>
          "https://account.t3leads.com/en/account/report/get-errors-types?er_date1=" .
          "{$dateGetParam}&er_date2={$dateGetParam}&channelType=" .
          "&product=&importance=critical&webmaster={$v['webmaster_id']}&SubmitButton=Submit",
      );

      $varsArray['array_snapshot'] = var_export($varsArray, true);


      if($settings['post_errors_agent_on'] && $v['Percents']>=$settings['post_errors_agent_min_percent']){

        $message = T3Mail::createMessage('PostErrorsReporting_agent', $varsArray);

        if(!$this->testMode){

          if(!empty($v['agentID'])){
            $message->addToString($v['agent_contactEmail']);
            $message->setCcString($settings['post_errors_emails']);
          }else{
            $message->addToString($settings['post_errors_emails']);
          }
          
        }else{

          $message->addToString('0x6fwhite@gmail.com');

        }

        $message->SendMail();

      }

      if($settings['post_errors_wm_on'] && $v['Percents']>=$settings['post_errors_wm_min_percent']){

        $message = T3Mail::createMessage('PostErrorsReporting_wm', $varsArray);

        if(!$this->testMode){
          $message->addToString($v['webmaster_email']);
        }else{
          
          $message->addToString('0x6fwhite@gmail.com');

        }
       
        $message->SendMail();

      }

    }

  }


  public function getSettings(){

    $result = array(
      'post_errors_emails' => T3System::getValue('post_errors_emails'),
      'post_errors_agent_min_percent' => T3System::getValue('post_errors_agent_min_percent'),
      'post_errors_wm_min_percent' => T3System::getValue('post_errors_wm_min_percent'),
      'post_errors_agent_on' => T3System::getValue('post_errors_agent_on'),
      'post_errors_wm_on' => T3System::getValue('post_errors_wm_on'),
    );
    
    if(!isset($result['post_errors_emails']))
      $result['post_errors_emails'] = '';
    if(!isset($result['post_errors_agent_min_percent']))
      $result['post_errors_wm_min_percent'] = '1';
    if(!isset($result['post_errors_wm_min_percent']))
      $result['post_errors_emails'] = '1';
    if(!isset($result['post_errors_agent_on']))
      $result['post_errors_agent_on'] = false;
    if(!isset($result['post_errors_wm_on']))
      $result['post_errors_wm_on'] = false;

    return $result;

  }

  public function setSettings($array){

    T3System::setValue('post_errors_emails', $array['post_errors_emails']);
    T3System::setValue('post_errors_agent_min_percent', $array['post_errors_agent_min_percent']);
    T3System::setValue('post_errors_wm_min_percent', $array['post_errors_wm_min_percent']);
    T3System::setValue('post_errors_agent_on', $array['post_errors_agent_on']);
    T3System::setValue('post_errors_wm_on', $array['post_errors_wm_on']);

  }

  public function getAgentEmail($agentId){

    $this->database->fetchOne('select email from users_webmaster_agents where id = ?', array($agentId));

  }

}

T3PostErrorsReporting::getInstance();


