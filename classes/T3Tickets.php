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
class T3Tickets {
//put your code here
    public $db;
    public function  __construct() {
        $this->db= T3Db::api();
    }
    public function getAllTicketsArray() {
        return $this->db->fetchAll("SELECT * FROM tickets");
    }
    public function getAllTickets() {
        $ticketsArray = $this->getAllTicketsArray();
        $tickets = array();
        foreach($ticketsArray as $v) {
            $ticket = new T3Ticket();
            $ticket->fromArray($v);
            $tickets[]=$ticket;
        }
        return $tickets;
    }
    public function getOutBoxTickets($id) {
        $select = $this->db->select()->from(array("t"=>"tickets"))->where("from_id=".$id)->order("t.date_time DESC");
        $data=$this->db->query($select)->fetchAll();
        $tickets = array();
        foreach($data as $v) {
            $ticket = new T3Ticket();
            $ticket->fromArray($v);
            $tickets[]=$ticket;
        }
        return $tickets;
    }
    public function getInboxTicketsByMessageType($type) {
        $select = $this->db->select()->from(array("t"=>"tickets"));
        if(is_array($type)) {
            foreach($type as $k=>$v) {
                if($k==0) {
                    $select=$select->where("to_message_type='".$v."'");
                }else {
                    $select=$select->orWhere("to_message_type='".$v."'");
                }

            }
        }else {
            $select=$select->where("to_message_type='".$type."'");

        }
        $select =$select->order("t.date_time DESC");
        $data = $select->query()->fetchAll();
        return $data;
    }
    public function getOutboxTicketsByMessageType($type,$time=null) {
        $select = $this->db->select()->from(array("t"=>"tickets"));
        $where = "";
        if($time!==null) {
            $where="(";
            $date = new Zend_Date();
            if($time!=="day") {
                $date->subMinute($time);
            }else {
                $date->subDay(1);
            }
        }
        if(is_array($type)) {

            if(array_key_exists("id", $type)) {
                $where = "(";
            }
            foreach($type as $k=>$v) {
                if($k===0) {
                    $where.="from_message_type='".$v."'";
                    //$select=$select->where("from_message_type='".$v."'");
                }else {
                    //$select=$select->orWhere("from_message_type='".$v."'");
                    if($k!=="id") {
                        $where .= " OR from_message_type='".$v."'";

                    }
                }

            }
            if(array_key_exists("id", $type)) {
                $where .= ")";
                $where .= "AND (id='".$type["id"]."')";
            }
            if($time!==null) {
                $where.=")";
            }
            $select=$select->where($where);
        }else {
            $select=$select->where("from_message_type='".$type."'");

        }
        $select =$select->order("t.date_time DESC");
        $data = $select->query()->fetchAll();
        return $data;
    }
    public function getInboxTickets($id) {
        $select = $this->db->select()->from(array("t"=>"tickets"))->where("to_id=".$id)->order("t.date_time DESC");
        $data =$this->db->query($select)->fetchAll();
        $tickets = array();
        foreach($data as $v) {
            $ticket = new T3Ticket();
            $ticket->fromArray($v);
            $tickets[]=$ticket;
        }
        return $tickets;
    }
    public function getNewInboxTickets($id) {
        $select = $this->db->select()->from(array("t"=>"tickets"),array('COUNT(*)'))->where('to_id=?',(int)$id)->where('`read`=?',0);
        $data = $this->db->query($select)->fetchAll();
        return (int)$data[0]["COUNT(*)"];
    }
    public function checkTicketsAgents(){
        $tickets = $this->getAllTicketsArray();
        $webmasters_ids = array();
        $buyers_ids = array();
        foreach($tickets as $k => $ticket){
            if($ticket['from_message_type'] == 'from_webmaster'){
                $webmasters_ids[$ticket['from_id']][$ticket['to_id']][$ticket['id']] = 0;
            }elseif($ticket['to_message_type'] == 'to_webmaster'){
                $webmasters_ids[$ticket['to_id']][$ticket['from_id']][$ticket['id']] = 1;
            }elseif($ticket['from_message_type'] == 'from_buyer'){
                $buyers_ids[$ticket['from_id']][$ticket['to_id']][$ticket['id']] = 0;
            }elseif($ticket['to_message_type'] == 'to_buyer'){
                $buyers_ids[$ticket['to_id']][$ticket['from_id']][$ticket['id']] = 1;
            }
        }
        $wms_agents = $this->db->fetchPairs("SELECT u.id as uid,c.agentID as aid FROM users as u LEFT JOIN users_company_webmaster as c ON  u.company_id = c.id  WHERE u.id IN (".implode(',',array_keys($webmasters_ids)).")");
        $buyer_agents = $this->db->fetchPairs("SELECT u.id as uid,c.agent_id as aid FROM users as u LEFT JOIN users_company_buyer as c ON  u.company_id = c.id  WHERE u.id IN (".implode(',',array_keys($buyers_ids)).")");
        foreach($wms_agents as $wid => $aid){
            if($aid==0)continue;
            $this->db->query("UPDATE tickets SET to_id = '$aid' WHERE from_id = '$wid' AND to_id != '$aid' AND to_id != '1019081'");//Don't update current AgentID and Hrant's Admin AccountID
            $this->db->query("UPDATE tickets SET from_id = '$aid' WHERE to_id = '$wid' AND from_id != '$aid' AND from_id != '1019081'");//Don't update current AgentID and Hrant's Admin AccountID
        }
        foreach($buyer_agents as $bid => $aid){
            if($aid==0)continue;
            $this->db->query("UPDATE tickets SET to_id = '$aid' WHERE from_id = '$bid' AND to_id != '$aid' AND to_id != '1019081'");//Don't update current AgentID and Hrant's Admin AccountID
            $this->db->query("UPDATE tickets SET from_id = '$aid' WHERE to_id = '$bid' AND from_id != '$aid' AND from_id != '1019081'");//Don't update current AgentID and Hrant's Admin AccountID
        }
    }
}
?>
