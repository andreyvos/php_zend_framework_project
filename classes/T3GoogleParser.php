<?php
// Hrant, parsing google results to match with websites database we have
class T3GoogleParser {
   
	public function parse()
	{
		set_time_limit(140);
		$keywords = T3Db::api()->fetchAll("SELECT product, keywords FROM keywords_parse");
		
		define('AE_WHOIS_TIMEOUT', 15); // connection timeout
		
		foreach($keywords as $keyword)
		{
			$keywords = explode(",", $keyword['keywords']);
			
			foreach ($keywords as $key)
			{
				$key = trim($key);
				$key = str_replace(" ","%20",$key);
				$i = 0;
				for ($count=0; $count<5; $count++)
				{
					$stringJSON = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&key=ABQIAAAA2kY8EUHN87iAFSTZ2F_rLBSLl5tVsStLxDwAydcfBk4fRArRrhT0W09_1g3zBm5xgQMCNTxYe1SO0w&q=$key&start=".$i;
					
				    $str = file_get_contents($stringJSON);
				    $str = json_decode($str);
				    if ($str->responseStatus !="403")
					{					
				    	foreach ($str->responseData->results as $el)
				    	{
				    		$date = date("Y-m-d H:i:s");
				    		$keytobase = str_replace("%20"," ",$key);
				    		T3Db::api()->query("INSERT IGNORE INTO google_top_web (domain, date_parsed, whois, keyword) VALUES ('$el->visibleUrl','$date', 'NULL','$keytobase') ");
				    	}
	
					}
					else
				    {
				    	print_r($str->responseStatus);echo "<br/>";
				    }
			    	$i = $i + 4;
				}
			}
		}
		
	 						
    	
    	
    	$emptyWhoIsEntereis = T3Db::api()->fetchAll("SELECT * FROM google_top_web WHERE whois='NULL'");
    	
    	$phonenumber = "none";
    	$ownername = "none";
    	$email =  "none";
    	$street = "none";
    	$city =  "none";
    	$state = "none";
    	$postalcode = "none";
    	$country = "none";
    	$linksincount = "none";
    	$rank =  "none";
    	
    	foreach($emptyWhoIsEntereis as $emptyWhoIsEntery)
    	{
    		$id = $emptyWhoIsEntery['id'];
    		$url = $emptyWhoIsEntery['domain'];
    		$str = file_get_contents("http://alexa/urlinfo.php?url=$url");
    		$el = json_decode($str);   			
    		if (isset($el->phonenumber))
    		{    			$phonenumber = $el->phonenumber;
    		}
    	    if (isset($el->ownername))
    		{
    			$ownername = $el->ownername;
    		}
    		if (isset($el->email))
    		{
    			$email = $el->email;
    		}
    	    if (isset($el->street))
    		{
    			$street = $el->street;
    		}
    	    if (isset($el->city))
    		{
    			$city = $el->city;
    		}
    	    if (isset($el->state))
    		{
    			$state = $el->state;
    		}
    	    if (isset($el->postalcode))
    		{
    			$postalcode = $el->postalcode;
    		}
    	    if (isset($el->country))
    		{
    			$country = $el->country;
    		}
    	    if (isset($el->linksincount))
    		{
    			$linksincount = $el->linksincount;
    		}
    	    if (isset($el->rank))
    		{
    			$rank = $el->rank;
    		}
    		$str = addslashes($str);
    		T3Db::api()->query("UPDATE google_top_web SET whois='$str', phonenumber='$phonenumber', ownername='$ownername', email='$email', street='$street', city='$city', state='$state', postalcode='$postalcode', country='$country', linksincount='$linksincount', rank='$rank' WHERE id='$id'");
    	}
    
	}
	
	
	public function assign()
	{
		$webmasterAgents = T3Db::api()->fetchAll("SELECT * FROM tasts_to_agents");
		
		foreach ($webmasterAgents as $webmasterAgent)
		{
			$count = (int)T3Db::api()->fetchOne("SELECT COUNT(*) as cnt FROM google_top_web  WHERE completed='0' AND assigned_to='$webmasterAgent[agent_id]' GROUP BY assigned_to");
			
			if ((int)$webmasterAgent['tasks_amount']>$count)
			{
				$tasksToAdd = (int)$webmasterAgent['tasks_amount'] - $count;
				$websites = T3Db::api()->fetchAll("SELECT * FROM google_top_web WHERE assigned_to='0' AND product='$webmasterAgent[product]' LIMIT $tasksToAdd");
				foreach($websites as $website)
				{
//					print_r($website); die;
					$info = "<h3>Please Research on the Domain Below</h3>";
					$info.= "<p><strong>Domain:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[domain]</p>";
					$info.= "<p><strong>Phonenumber:</strong>&nbsp; $website[phonenumber]</p>";
					$info.= "<p><strong>Ownername:</strong>&nbsp;&nbsp;&nbsp;&nbsp; $website[ownername]</p>";
					$info.= "<p><strong>Email:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[email] </p>";
					$info.= "<p><strong>Street:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[street]</p>";
					$info.= "<p><strong>City:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </strong>$website[city]</p>";
					$info.= "<p><strong>State:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[state]</p>";
					$info.= "<p><strong>Postalcode:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[postalcode]</p>";
					$info.= "<p><strong>Country:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[country]</p>";
					$info.= "<p><strong>Linksincount:</strong>&nbsp;&nbsp;&nbsp;&nbsp; $website[linksincount]</p>";
					$info.= "<p><strong>Rank:</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $website[rank]</p>";
			     
					// pass assign to the tasks interface START
			        $task = T3Task_General::createTaskLigths(
						    $webmasterAgent['agent_id'], // ID пользователя, которому дается задача
						    $website['domain'],     	 // Тема
						    $info,     					 // Текст в формате HTML
						    "googleParse"                // Тип задачи, всегда ставь googleParse
						);
					$taskID = $task->id;
					$task->addEventClose(array("T3GoogleParser", "callback"));
			        // pass assign to the tasks interface END
			        
					$data = array(
			            'assigned_to'   => $webmasterAgent['agent_id'],
			        	'date_assigned' => date("Y-m-d H:i:s"), 
						'task_id'		=> $taskID
			        			 );
		        	T3Db::api()->update('google_top_web', $data, 'id='.$website['id']);
		        	
				}
			}
		}
							   
	}
	
	static public function callback($id)
	{
		// Getting all comments of the task     	
		$comments = T3Task_General::getTask($id)->getHistory();
		$recordComments = "";
		foreach ($comments as $comment)
		{
			$recordComments.= $comment['text'];  
		}
		// Getting all comments of the task END 
	
    	$data = array ('completed' => 1, 'date_completed' => date("Y-m-d H:i:s"), 'comment' => $recordComments);
    	T3Db::api()->update('google_top_web', $data,'task_id='.$id);
	}
	
	public function count()
	{
		$count = T3Db::api()->fetchAll("SELECT assigned_to, COUNT(*) as cnt FROM google_top_web  WHERE completed='0' GROUP BY assigned_to");
		return $count;
		
	}
	
	public function countClosed($date)
	{
		$count = T3Db::api()->fetchAll("SELECT assigned_to, COUNT(*) as cnt FROM google_top_web  WHERE completed='1' AND date_completed BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59' GROUP BY assigned_to");
		return $count;	
	}
	
}