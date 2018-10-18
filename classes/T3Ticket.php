<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of T3Tickets
 *
 * @author twissel
 */
TableDescription::addTable('tickets', array(
    'id',                       //  int(11)
    'theme',                    //  text
    'body',                     //  text
    'from_id',                  //  int(11)
    'to_id',                    //  int(11)
    'date_time',                //  datetime
    'from_message_type',        //  enum('from_webmaster','from_buyer','from_webmaster_agent','from_buyer_agent')
    'to_message_type',          //  enum('to_webmaster','to_buyer','to_webmaster_agent','to_buyer_agent')
    'read',                     //  tinyint(1)
    'archived',                 //  tinyint(1)
    'root_id',                //  int(11)
    'number_in_history',
    'replay',
    'status'
));

class T3Ticket extends DbSerializable {
//put your code here
    public $id;
    public $theme;
    public $body;
    public $from_id;
    public $to_id;
    public $date_time;
    public $from_message_type;
    public $to_message_type;
    public $read;
    public $archived;
    public $root_id;
    public $number_in_history;
    public $replay;
    public $status;
    public function  __construct() {
	parent::__construct();
	$this->tables[] = "tickets";
    }
    public function getHistory() {
	if($this->root_id!=0)
	$select = $this->database->select()->from(array('t'=>'tickets'),array('*'))->where('root_id='.(int)$this->root_id." OR  id=".(int)$this->root_id." OR id=".(int)$this->id )->order("date_time DESC");
	else
	$select = $this->database->select()->from(array('t'=>'tickets'),array('*'))->where('root_id='.(int)$this->id." OR id=".(int)$this->id )->order("date_time DESC");
	$data  = $this->database->query($select)->fetchAll();
	$tickets = array();
	foreach($data as $v) {
	    $ticket = new T3Ticket();
	    $ticket->fromArray($v);
		$tickets[]=$ticket;
	}
	return $tickets;
    }
    public function getHistoryCount(){ // переделать на нормальный sql запрос
       	if($this->root_id!=0)
	$select = $this->database->select()->from(array('t'=>'tickets'),array('*'))->where('root_id='.(int)$this->root_id." OR  id=".(int)$this->root_id." OR id=".(int)$this->id )->order("date_time DESC");
	else
	$select = $this->database->select()->from(array('t'=>'tickets'),array('*'))->where('root_id='.(int)$this->id." OR id=".(int)$this->id )->order("date_time DESC");
	$data  = $this->database->query($select)->fetchAll();
	return count($data);
    }
}
?>
