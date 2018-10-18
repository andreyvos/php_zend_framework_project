<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of EmailChange
 *
 * @author twissel
 */
TableDescription::addTable('email_recovery_table', array(
  'id',                       //  int(11)
  'userID',                   //  int(11)
  'oldEmail',                 //  text
  'newEmail',                 //  text
  'codeFromOld',              //  text
  'codeFromNew',              //  text
  'status',                   //  enum('active','delete')
  'oldVerified',              //  tinyint(1)
  'newVerified',              //  tinyint(1)
));

class EmailChange extends DbSerializable {
//put your code here

    public $id;
    public $userID;
    public $oldEmail;
    public $newEmail;
    public $codeFromOld;
    public $codeFromNew;
    public $oldVerified;
    public $newVerified;
    public $status;
    public function __construct() {
	parent::__construct();
	$this->tables[] = "email_recovery_table";
    }
    public function setStatuses($userID) {
	$select = $this->database->select(array('*'))->from('email_recovery_table')->where('userID=?',(int)$userID);
	$data = $this->database->query($select)->fetchAll();
	foreach($data as $v){
	    $emailChange = new EmailChange();
	    $emailChange->fromArray($v);
	    $emailChange->status="delete";
	    $emailChange->saveToDataBase();
	}
    }
}
?>
