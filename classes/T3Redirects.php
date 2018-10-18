<?php

require_once 'T3Redirect.php';

class T3Redirects {

    static public function createRedirectDefault(T3Lead $lead) {
            $db = T3Db::api();

            $redirectObject = T3Tnx::getTnxObjectByLead($lead);
            $default_url = $redirectObject->urlIncludeParams;
            $default_url = str_replace("&amp;", "&", $default_url);

            $db->insert('post_redirect',array(
                'idlead'                => $lead->id,
                'clientIP'              => $lead->ip_address,
                'clientIPint'           => myHttp::get_ip_num($lead->ip_address),
//                'postingID'             => $channel->id,
                'channelID'             => $lead->channel_id,
                'buyerID'               => 50533,
                'webmasterID'           => $lead->affid,
                'WMasterSubAccountID'   => isset($lead->subacc) ? $lead->subacc : "0",
                'redirectURL'           => (string)$default_url,
            ));

            $redirectID = $db->lastInsertId();

            if(T3TestCluster::isTestMode()){
                return "http://{$_SERVER['SERVER_NAME']}/system/redirect.php?".IdEncryptor::encode($redirectID);
            }

            return "https://{$_SERVER['HTTP_HOST']}/system/redirect.php?".IdEncryptor::encode($redirectID);
    }

    static public function createRedirect(T3Lead $lead, T3BuyerChannel $channel, $url) {
        if($url){
            $db = T3Db::api();

            $url = str_replace("&amp;", "&", $url);
                                                                             
            $db->insert('post_redirect',array(
                            'idlead'                => $lead->id,
                            'clientIP'              => $lead->ip_address,
                            'clientIPint'           => myHttp::get_ip_num($lead->ip_address),
                            'postingID'             => $channel->id,
                            'channelID'             => $lead->channel_id,
                            'buyerID'               => $channel->buyer_id,
                            'webmasterID'           => $lead->affid,
                            'WMasterSubAccountID'   => isset($lead->subacc) ? $lead->subacc : "0",
                            'redirectURL'           => (string)$url,
                        ));

            $redirectID = $db->lastInsertId();

            if(T3TestCluster::isTestMode()){
                return "http://{$_SERVER['SERVER_NAME']}/system/redirect.php?".IdEncryptor::encode($redirectID); 
            }
            
            return "https://{$_SERVER['HTTP_HOST']}/system/redirect.php?".IdEncryptor::encode($redirectID);
        }
        return null;
    }

    static public function redirectClick($id) {
        if (isset($id) && is_numeric($id)) {
            $redirect = new T3Redirect();
            $redirect->fromDatabase($id);
            
            if (isset($redirect->id) && $redirect->id>0) {
                T3Cache_LeadsPayAndRedirects::add_goodRedirect($redirect->idlead,$redirect->postingID);
                return $redirect;
            }
        }
        return null;
    }
    
    
    
    static public function getDataByPeriodAndType( $date_start, $date_end, $type , $params = array())
    { 
        $db = T3Db::api();
        $only_count = false;
        
        if(isset($params['show_parts']) && $params['show_parts'] == 1) {
            return self::getPartsDataByPeriodAndType($date_start, $date_end, $type, $params);
        }
        
        $select = new Zend_Db_Select($db);

        
        if(!isset( $params['cache_from'] )) $params['cache_from'] = 1;
        $type = (int)$type;
        
        if(isset($params['only_count'])) $only_count = true;
        
        
        if($type == 1) {
        
            
            
            if(!$only_count) {
            
                $select->distinct();
                if( $params['cache_from'] == 1 ) {
                    $select->from(
                        array('tbl' => 'redirects_tenminutes_chanel_cache'), 
                         array('chanel_id', 'webmaster_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } elseif ( $params['cache_from'] == 2 ) {
                    $select->from(
                        array('tbl' => 'redirects_hourly_chanel_cache'), 
                         array('chanel_id', 'webmaster_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } elseif ( $params['cache_from'] == 3 ) {
                    $select->from(
                        array('tbl' => 'redirects_daily_chanel_cache'), 
                         array('chanel_id', 'webmaster_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } else {
                    $select->from(
                        array('tbl' => 'redirects_monthly_chanel_cache'), 
                         array('chanel_id', 'webmaster_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                }
         
                //->where('tenmin.cron = 0')
                if( $params['channel_id'] ) $select->where('chanel_id =? ', (int)$params['channel_id']); 
                
                $select->where("`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i') " );
                $select->group('tbl.chanel_id');
                $select->order('tbl.percent ASC');
                $select->limitPage($params['page'], $params['per_page']);//varDump2($select->__toString());
            } else {  
                if( $params['cache_from'] == 1 )$from_table = "redirects_tenminutes_chanel_cache";
                elseif ($params['cache_from'] == 2) $from_table = "redirects_hourly_chanel_cache";
                elseif ( $params['cache_from'] == 3 ) $from_table = "redirects_daily_chanel_cache";
                else $from_table = "redirects_monthly_chanel_cache";
                
                $query_part1 = "SELECT count(*) as total from (SELECT DISTINCT id FROM `".$from_table."` AS `tbl`
						  		WHERE (`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i')";
                if( $params['channel_id'] ) $query_part1 = $query_part1."AND chanel_id = ".(int)$params['channel_id']." ) ";
                else $query_part1 = $query_part1." ) ";
                $query_part2 = "GROUP BY `tbl`.`chanel_id` ORDER BY `tbl`.`percent` ASC) AS aa";
                $query = $query_part1.$query_part2;              
            }
            //die($select->__toString());
            
 
        } else {
            
            $select->distinct();
            
            if(!$only_count) {
            
                if( $params['cache_from'] == 1 ) {
                    $select->from(
                        array('tbl' => 'redirects_tenminutes_posting_cache'), 
                         array('posting_id', 'buyer_id', 'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } elseif ( $params['cache_from'] == 2 ) {
                    $select->from(
                        array('tbl' => 'redirects_hourly_posting_cache'), 
                         array('posting_id', 'buyer_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } elseif ( $params['cache_from'] == 3 ) {
                    $select->from(
                        array('tbl' => 'redirects_daily_posting_cache'), 
                         array('posting_id', 'buyer_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                } else {
                    $select->from(
                        array('tbl' => 'redirects_monthly_posting_cache'), 
                         array('posting_id', 'buyer_id',  'SUM(total_redirects) AS total_redirects', 'SUM(good_redirects) AS good_redirects', 'good_redirects * ( 100 / total_redirects )  AS percent'));
                }
         
                //->where('tenmin.cron = 0')
                if( $params['posting_id'] ) $select->where('posting_id =? ', (int)$params['posting_id']); 
                
                $select->where("`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i') " );
                $select->group('tbl.posting_id');
                $select->order('tbl.percent ASC');
                $select->limitPage($params['page'], $params['per_page']);
            } else {
                if( $params['cache_from'] == 1 )$from_table = "redirects_tenminutes_posting_cache";
                elseif ($params['cache_from'] == 2) $from_table = "redirects_hourly_posting_cache";
                elseif ( $params['cache_from'] == 3 ) $from_table = "redirects_daily_posting_cache";
                else $from_table = "redirects_monthly_posting_cache";
                
                $query_part1 = "SELECT count(*) as total from (SELECT DISTINCT id FROM `".$from_table."` AS `tbl`
						  		WHERE (`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i')";
                if( $params['posting_id'] ) $query_part1 = $query_part1."AND posting_id = ".(int)$params['posting_id']." ) ";
                else $query_part1 = $query_part1." ) ";
                $query_part2 = "GROUP BY `tbl`.`posting_id` ORDER BY `tbl`.`percent` ASC) AS aa";
                $query = $query_part1.$query_part2; 
                
            }
        }

        
        //varExport($select->__toString());
        
        if(!$only_count) {
            //varDump2($select->__toString());
            $results = $db->fetchAll($select);
        } else $results = $db->fetchOne($query);

        return $results;
    }
    
    static public function getPartsDataByPeriodAndType( $date_start, $date_end, $type , $params = array())
    {
        $db = T3Db::api();
        $only_count = false;

        $select = new Zend_Db_Select($db);
        
        if(!isset( $params['cache_from'] )) $params['cache_from'] = 1;
        $type = (int)$type;
        
        if(isset($params['only_count'])) $only_count = true;
        
        
        if($type == 1) {
            
            if(!$only_count) {

                if( $params['cache_from'] == 1 ) {
                    $select->from(array('tbl' => 'redirects_tenminutes_chanel_cache'),array( '*' ));
                } elseif ( $params['cache_from'] == 2 ) {
                    $select->from(array('tbl' => 'redirects_hourly_chanel_cache'), array( '*' ));
                } elseif ( $params['cache_from'] == 3 ) {
                    $select->from( array('tbl' => 'redirects_daily_chanel_cache'), array( '*' ));
                } else {
                    $select->from( array('tbl' => 'redirects_monthly_chanel_cache'),  array('*'));
                }
         
                //->where('tenmin.cron = 0')
                if( $params['channel_id'] ) $select->where('chanel_id =? ', (int)$params['channel_id']); 
                
                $select->where("`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i') " );
                //$select->group('tbl.chanel_id');
                $select->order('tbl.percent ASC');
                $select->limitPage($params['page'], $params['per_page']); //varDump2($select->__toString());
            } else {  
                if( $params['cache_from'] == 1 )$from_table = "redirects_tenminutes_chanel_cache";
                elseif ($params['cache_from'] == 2) $from_table = "redirects_hourly_chanel_cache";
                elseif ( $params['cache_from'] == 3 ) $from_table = "redirects_daily_chanel_cache";
                else $from_table = "redirects_monthly_chanel_cache";
                
                $query_part1 = "SELECT count(*) as total FROM `".$from_table."` AS `tbl`
						  		WHERE `datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i') ";
                if( $params['channel_id'] ) $query_part1 = $query_part1." AND chanel_id = ".(int)$params['channel_id']."  ";
                else $query_part1 = $query_part1."  ";
                $query_part2 = "  ";
                $query = $query_part1.$query_part2;              
            }
            //die($select->__toString());
            
 
        } else {
            
            if(!$only_count) {
            
                if( $params['cache_from'] == 1 ) {
                    $select->from( array('tbl' => 'redirects_tenminutes_posting_cache'), array('*'));
                } elseif ( $params['cache_from'] == 2 ) {
                    $select->from( array('tbl' => 'redirects_hourly_posting_cache'), array('*'));
                } elseif ( $params['cache_from'] == 3 ) {
                    $select->from( array('tbl' => 'redirects_daily_posting_cache'), array('*'));
                } else {
                    $select->from( array('tbl' => 'redirects_monthly_posting_cache'), array('*'));
                }
         
                //->where('tenmin.cron = 0')
                if( $params['posting_id'] ) $select->where('posting_id =? ', (int)$params['posting_id']); 
                
                $select->where("`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H') " );
                //$select->group('tbl.posting_id');
                $select->order('tbl.percent ASC');
                $select->limitPage($params['page'], $params['per_page']);
            } else {
                if( $params['cache_from'] == 1 )$from_table = "redirects_tenminutes_posting_cache";
                elseif ($params['cache_from'] == 2) $from_table = "redirects_hourly_posting_cache";
                elseif ( $params['cache_from'] == 3 ) $from_table = "redirects_daily_posting_cache";
                else $from_table = "redirects_monthly_posting_cache";
                
                $query_part1 = "SELECT count(*) as total FROM `".$from_table."` AS `tbl`
						  		WHERE (`datetime` BETWEEN STR_TO_DATE('".$date_start."', '%d.%m.%Y %H:%i')  AND STR_TO_DATE('".$date_end."', '%d.%m.%Y %H:%i')";
                if( $params['posting_id'] ) $query_part1 = $query_part1." AND posting_id = ".(int)$params['posting_id']." ) ";
                else $query_part1 = $query_part1." ) ";
                $query_part2 = " ";
                $query = $query_part1.$query_part2; 
                
            }
        }
        
        
        if(!$only_count) {
            $results = $db->fetchAll($select);
        } else $results = $db->fetchOne($query);

        return $results;
    }
    
    
    static public function getDetailData($type, $id)
    {
        $id = (int)$id;
        $db = T3Db::api();
        
        $select = new Zend_Db_Select($db);
        
        if($type == 1) {
            $select->from(array('tbl' => 'redirects_daily_chanel_cache'), array('*'))
                    ->where('chanel_id = ?', $id )
                    ->order('id ASC');
        } else {
            $select->from(array('tbl' => 'redirects_daily_posting_cache'), array('*'))
                   ->where('posting_id = ?', $id )
                   ->order('id ASC');
        }
        
        return $db->fetchAll($select);
    }
    
    static public function getChannelDetail( $channel_id )
    {
        $channel_id = (int)$channel_id;
        $db = T3Db::api();
        
        $select = new Zend_Db_Select($db);  
        $select->from(array('tbl' => 'redirects_daily_chanel_cache'), array('*'))
                    ->where('chanel_id = ?', $channel_id )
                    ->order('id ASC');
        return $db->fetchAll($select);
    }
    
    static public function getPostingDetail( $posting_id )
    {
        $posting_id = (int)$posting_id;
        $db = T3Db::api();
        
        $select = new Zend_Db_Select($db);
        $select->from(array('tbl' => 'redirects_daily_posting_cache'), array('*'))
               ->where('posting_id = ?', $posting_id )
               ->order('id ASC')->limit(50);
        return $db->fetchAll($select);       
    }    
    
    static public function getDailyDataByID( $type, $id )
    {
        $id = (int)$id;
        $db = T3Db::api();
        $results = array();
        
        
        $select = new Zend_Db_Select($db);
        
        
        if($type == 1) {
            $select->from(array('rdcc'=>'redirects_daily_chanel_cache'), array('*'))->where('id = ?', $id);
        } else {
            $select->from(array('rdcc'=>'redirects_daily_posting_cache'), array('*'))->where('id = ?', $id);
        }
        
        $dbdata = $db->fetchRow($select);
        
        if(count($dbdata) && isset($dbdata['bad_redirect_ids']) && strlen($dbdata['bad_redirect_ids']) > 0) {
            $bad_ids_arr = explode(",", $dbdata['bad_redirect_ids']);
            $bad_ids = '';
            $i = 0;
            
            $keys = array();
            
            if(count($bad_ids_arr)) {
                foreach ($bad_ids_arr AS $k=>$v) {
                    $v = (int)$v;
                    if(in_array($v, $keys)) continue;
                    if($v > 0) {
                        if($i === 0) $bad_ids .= ''.$v; else  $bad_ids .= ','.$v;
                        $keys[] = $v;
                        $i++;
                    }
                    
                }
            }
            
            if(strlen($bad_ids) > 2) {
                $query = " SELECT ld.*, ld.id AS leadId, pr.createDate, pr.redirectURL, pr.clientIP, pr.channelID FROM post_redirect AS pr
                            LEFT JOIN leads_data AS ld ON (  pr.idlead = ld.id )
                            WHERE pr.id IN (".$bad_ids.")
                            ORDER BY pr.id DESC  ";
                //die($query);
                $results = $db->fetchAll($query);
            }
            
        }
        
        return array($results,$dbdata) ;
    }
    
    static public function getLatestRedirectsByChannelId($channelID)
    {
        $channelID = (int)$channelID;
        $db = T3Db::api();
        $q = " SELECT  pr.idLead, pr.channelID, pr.redirectComplite, ld.*
        	   FROM post_redirect AS pr 
        	   LEFT JOIN leads_data AS ld ON( ld.id = pr.idLead)
			   WHERE ( UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(createDate) <= 86400  AND channelID = ".$channelID." )
			   ORDER BY redirectComplite ASC
			   LIMIT 30  ";
        return $db->fetchAll($q);
    }
    
    
    
    
    
    
    
    
    
    public static function getTenMinutesRedirects()
    {
        $db = T3Db::api();
        //$q = new MyZend_Db_Table();

        
        $results = $db->fetchAll("SELECT * FROM redirects_reports_ten_minutes");
        // TODO tut
        //varDump2($results);
        return $results;
    } 
    
    public static function sortRedirectsResults( $items , $sortBy = 'channelID')
    {
        $sortByType = ( $sortBy == 'channelID' ) ? $sortBy : 'postingID'; // channelID - 1  OR postingID - 2
        
        $results = array();
        $results_out = array();
        $keys = array();
        
        if(count($items) && is_array($items)) {

            foreach ( $items AS $k=>$v ) {
                
                if( !in_array($v[$sortByType], $keys ) ) {
                    $results[$v[$sortByType]]['leads_num'] = 1;
                    $results[$v[$sortByType]]['complete_num'] = ($v['redirectComplite'] == 1) ? 1 : 0 ;
                    $keys[] = $v[$sortByType];
                    
                } else {
                    $results[$v[$sortByType]]['leads_num'] ++ ;
                    if( $v['redirectComplite'] == 1 ) {
                        $results[$v[$sortByType]]['complete_num'] ++ ;
                    }
                }
            }
            
            foreach ( $results AS $key => $value ) {
                $percent = null;
                if($value['complete_num'] === 0) $percent = 0;
                else {
                    $percent = round($value['complete_num'] * 100 / $value['leads_num']);
                }
                $results[$key]['percent'] = $percent;
                $results[$key][$sortByType] = $key;
            }
            
            $i = 0;
            foreach ( $results AS $key => $value ) {
                list ($a0) = array($value['percent']);
                $results_out[$i] =  array ( 'percent' => $a0, 'data' => $value );
                $i++;
            }
            
            usort ( $results_out, 'percentCmp' );
        }

        
        
        return $results_out;
    }
    
    
    
    
    
    
    
}
