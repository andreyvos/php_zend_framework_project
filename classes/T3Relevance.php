<?php
class T3Relevance 
{
	public function updateRelevance() 
	    {
	    	set_time_limit(600);
	    	$this->getHelper('viewRenderer')->setNoRender();
	    	
	    	//Webmasters Relevance Calculation and database update
	    	$queries = T3Db::api()->fetchAll("SELECT * FROM users_company_webmaster ");
	 
	    	$totalEarning = array();
	    	$totalLeads = array();
	    	foreach($queries as $query)
	    	{
	    		$sum = 0;
	    		$index= $query['id'];
	    		$sum += T3Db::api()->fetchOne("SELECT SUM(total_earnings) as total_sum FROM buyers_statistics_grouped WHERE lead_webmaster_id=$query[id] AND record_date>SUBDATE(CURDATE(),INTERVAL 1 DAY);");
	    		$totalEarning[$index] = $sum*0.2;
	
	    		$sumLeads=0;
	    		$sumLeads += T3Db::api()->fetchOne("SELECT SUM(leads_count) as total_leads FROM buyers_statistics_grouped WHERE lead_webmaster_id=$query[id] AND record_date>SUBDATE(CURDATE(),INTERVAL 1 DAY);");
	    		$totalLeads[$index] = $sumLeads*0.8;
	    		$relevance = array();
	    		$relevance[$index]= ($totalEarning[$index] + $totalLeads[$index])*100;
	    		$data = array('relevance' => "$relevance[$index]");
				$where = "id = $index";
				T3Db::api()->update('users_company_webmaster', $data, $where);
	    	} 
	    	
	    	
	    	//Buyers Relevance calculation and Database update
	    	$queries = T3Db::api()->fetchAll("SELECT * FROM users_company_buyer ");
	    	
	    	$totalEarning = array();
	    	$totalLeads = array();
	    	foreach($queries as $query)
	    	{
	    		$sum = 0;
	    		$index= $query['id'];
	    		$sum += T3Db::api()->fetchOne("SELECT SUM(total_earnings) as total_sum FROM buyers_statistics_grouped WHERE buyer_id=$query[id] AND record_date>SUBDATE(CURDATE(),INTERVAL 1 DAY);");
	    		$totalEarning[$index] = $sum*0.2;
	
	    		$sumLeads=0;
	    		$sumLeads += T3Db::api()->fetchOne("SELECT SUM(leads_count) as total_leads FROM buyers_statistics_grouped WHERE buyer_id=$query[id] AND record_date>SUBDATE(CURDATE(),INTERVAL 1 DAY);");
	    		$totalLeads[$index] = $sumLeads*0.8;
	    		$relevance = array();
	    		$relevance[$index]= ($totalEarning[$index] + $totalLeads[$index])*100;
	    		$data = array('relevance' => "$relevance[$index]");
				$where = "id = $index";
				T3Db::api()->update('users_company_buyer', $data, $where);
	    	} 
	    	  	 	
	    }
}