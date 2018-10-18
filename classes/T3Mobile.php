<?php

class T3Mobile
{
	
	protected $db = null;

	protected $tableName = 't3mobile_content';
	
	protected $table = null;
	
	public function __construct()
	{
		$this->db = T3Db::api();
		
		$table_configs = array(
			'db'	=> $this->db,
			'name' => $this->tableName,
			'primary' => 'id',
		);
		
		$this->table = new MyZend_Db_Table( $table_configs );
		
	}

	
	public function getPageContent( $page_system_name, $feedUrl )
	{
		$filter = new MyZend_Filter_SystemName();
		$filter->setReplacement( '-' );
		
		$page_system_name = $filter->filter($page_system_name);
		
		$page_data = $this->db->fetchRow("SELECT * FROM ".$this->tableName." WHERE system_name = '".$page_system_name."' LIMIT 1 ");
		if(!is_array() || !count($page_data) ) {
			$page_data = $this->db->fetchRow("SELECT * FROM ".$this->tableName." WHERE system_name = 'index-page' LIMIT 1 ");
		}
	
		$page_body = $this->replaseUrl( $feedUrl, $page_data['body'] );
		
		return $page_body;
	
	}
	
	public function replaseUrl( $url, $content )
	{
		$string = str_replace("{ROOT}", $url, $content );
		return $string;
	}

	public function getPages()
	{
		return $this->db->fetchAll("SELECT * FROM ".$this->tableName." WHERE active = 1 ORDER BY sort ASC ");
	}
	
	public function getById( $id )
	{
		$id = (int)$id;
		return $this->table->selectRow("SELECT * FROM ".$this->tableName." WHERE id = ".$id." LIMIT 1");
	}
	
	public function save( $data , $id = null )
	{
		
		if( NULL == $id ) {
		
			$id = $this->table->insert( $data );
		
		} else {
			$this->table->update( $data, 'id = '. $id );
		}
		
		return $id;
	}
	
	public function deleteItem($id)
	{
		$id = (int)$id;
		return $this->db->query("DELETE FROM ".$this->tableName." WHERE id = ".$id." ");	
	}
	
	public function sendMessage( $subject, $message, $feed_url )
	{
		return T3Mail::createMessage('feed_mibile_contact_us_message', array (
              'subject' => htmlspecialchars( $subject . '  -  ' . $feed_url ),
              'datetime' => date("Y-m-d H:i:s"),
              'message' => htmlspecialchars($message),
			  'feed_url' => htmlspecialchars($feed_url),
            
            ))->addToArray(array(
                'feeds@t3leads.com',
            	'alexandr.s@t3leads.com',
            ))->SendMail();
		
	}
	
	
}