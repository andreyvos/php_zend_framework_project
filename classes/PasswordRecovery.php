<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PasswordRecovery
 *
 * @author twissel
 */
 TableDescription::addTable('password_recovery_table', array(
  'id',                       //  int(11)
  'userID',                   //  int(11)
  'status',                   //  enum('active','delete')
  'sessionKey',               //  text
  'ip',                       //  varchar(40)
  'creationDate',             //  datetime
));
class PasswordRecovery extends DbSerializable {
    //put your code here

  public $id;
  public $userID;
  public $status;
  public $sessionKey;
  public $ip;
  public $creationDate;
  public function  __construct() {
      parent::__construct();
	$this->tables[] = "password_recovery_table";
  }
  public function beginRecover(T3User $user,$ip){
      $this->userID=$user->id;
      $this->ip=$ip;
      $this->status ="active";
      $hash = md5($user->login.=$user->password);
      $this->sessionKey=$hash;
      $this->creationDate=mySqlDateTimeFormat();
      $id=$this->insertIntoDatabase();
      return array("id"=>$id,"key"=>$hash);
  }
  public function getUserSessions($id){
      $select = $this->database->select(array('*'))->from('password_recovery_table')->where('userID=?',(int)$id);
      $data = $this->database->query($select)->fetchAll();
      $result = array();
      foreach($data as $v){
	  $session = new PasswordRecovery();
	  $session->fromArray($v);
	  $result[] = $session;
      }
      return $result;
  }
}
?>
