<?php

class T3BuyerReasons
{

    public static function updateReopenedReasons()
    {
	$reasons_to_update = T3Db::api()->fetchCol("SELECT CONCAT(posting_id,' ',reason_id) as p_r FROM buyer_channels_reasons_log GROUP BY posting_id, reason_id HAVING SUM(viewed=0)>0 AND SUM(viewed=1)>0");
	if(count($reasons_to_update)){
	$reasons_to_update = implode('\',\'',$reasons_to_update);
	T3Db::api()->query("UPDATE buyer_channels_reasons_log SET viewed=0 ,reopened=1 WHERE CONCAT(posting_id,' ',reason_id) IN ('{$reasons_to_update}')")->execute();
        //die(var_dump("UPDATE buyer_channels_reasons_log SET viewed=0 ,reopened=1 WHERE CONCAT(posting_id,' ',reason_id) IN ('{$reasons_to_update}')"));
	}
    }

}

?>
