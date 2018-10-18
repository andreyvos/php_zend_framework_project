<?php

class T3RedirectsNew {
    static protected function reIndexWebmaster($from, $till, $type){
        T3Db::api()->delete($type, "`from` >= '{$from}' and `till` <= '{$till}'");
        
        $all = T3Db::api()->fetchAll("select 
        channels.product as product,
        pr1.webmasterID as company_id, 
        pr1.channelID as channel_id, 
        count(*) as total, 
        IFNULL(success, 0) as success, 
        count(*) - IFNULL(success, 0) as failed,
        round(IFNULL(success, 0)*100/count(*),1) as percents
        from post_redirect as pr1
        left join (select id,channelID as cannel_id, count(*) as success from post_redirect 
        where createDate BETWEEN '{$from}' and '{$till}' and redirectComplite='1' group by channelID) as pr2 on (pr2.cannel_id=pr1.channelID)
        inner join channels on (channels.id = pr1.channelID)
        where createDate BETWEEN '{$from}' and '{$till}' group by pr1.channelID"); 
        
        $runTime = date("Y-m-d H:i:s"); 
        
        if(is_array($all) && count($all)){
            foreach($all as $k => $v){
                $all[$k]['run_time'] = $runTime;
                $all[$k]['from'] = $from;
                $all[$k]['till'] = $till;           
            }  
            T3Db::api()->insertMulty($type, array_keys($all[0]), $all, 100);
        }   
    } 
    
    static public function reIndexWebmaster_10mins  ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_10mins");  }
    static public function reIndexWebmaster_30mins  ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_30mins");  }
    static public function reIndexWebmaster_1hour   ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_1hour");   }
    static public function reIndexWebmaster_3hours  ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_3hours");  }
    static public function reIndexWebmaster_6hours  ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_6hours");  }
    static public function reIndexWebmaster_12hours ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_12hours"); }
    static public function reIndexWebmaster_daily   ($from, $till){ self::reIndexWebmaster($from, $till, "redirect_webmaster_daily");   }
    
    
    
    
    static protected function reIndexBuyer($from, $till, $type){
        T3Db::api()->delete($type, "`from` >= '{$from}' and `till` <= '{$till}'");
        
        $all = T3Db::api()->fetchAll("select 
        buyers_channels.product as product,
        pr1.buyerID as company_id, 
        pr1.postingID as channel_id, 
        count(*) as total, 
        IFNULL(success, 0) as success, 
        count(*) - IFNULL(success, 0) as failed,
        round(IFNULL(success, 0)*100/count(*),1) as percents
        from post_redirect as pr1
        left join (select id,postingID as cannel_id, count(*) as success from post_redirect 
        where createDate BETWEEN '{$from}' and '{$till}' and redirectComplite='1' group by postingID) as pr2 on (pr2.cannel_id=pr1.postingID)
        inner join buyers_channels on (buyers_channels.id = pr1.postingID)
        where createDate BETWEEN '{$from}' and '{$till}' group by pr1.postingID");
        
        $runTime = date("Y-m-d H:i:s"); 
        
        // получение списка баеров, по которым уже недавно отправлялись уведомления (для того что бы не отправлять повторные)
        $pausedBuyers = T3Db::api()->fetchCol("select buyer from redirect_notifications_log_buyer where `create` > ?", 
            date('Y-m-d H:i:s', mktime(date('H') - 2, date('i'), 0, date('m'), date('d'), date('Y')))
        );
        
        if(is_array($all) && count($all)){
            foreach($all as $k => $v){
                $all[$k]['run_time'] = $runTime;
                $all[$k]['from'] = $from;
                $all[$k]['till'] = $till;  
                
                // Если процент хроших редиректов ниже определенного уровня и по этому баеру недавно не было уведомления, то посылается письмо баер агену
                if($v['percents'] <= 75 && $v['total'] > 10 && !in_array($v['company_id'], $pausedBuyers)){
                    T3Db::api()->insert("redirect_notifications_log_buyer", array(
                        'create'    => date('Y-m-d H:i:s'),
                        'buyer'     => $v['company_id'],
                        'from'      => $from,
                        'till'      => $till,
                        'leads'     => $v['total'],
                        'percent'   => $v['percents'],    
                    ));
                    
                    $emails = T3UserBuyerAgents::getAgentsEmails(T3BuyerChannels::getChannel($v['channel_id'])->product, 'redirect');
                    
                    if(count($emails)){
                        $message = T3Mail::createMessage('sendMail_BuyersRedirectReport', array (
                            'buyer'     => T3Cache_Buyer::render($v['company_id'], true),
                            'posting'   => T3Cache_BuyerChannel::get($v['channel_id'], true),
                            'date1'     => $from,
                            'date2'     => $till,
                            'success'   => $v['success'],
                            'failed'    => $v['failed'],
                            'total'     => $v['total'],
                            'percents'  => $v['percents'],
                            'details'   => "<a href='" . T3SendMail_Main::createLink('BuyersRedirectReport', array(
                                'from'      => $from,
                                'till'      => $till,
                                'buyer'     => $v['company_id'],
                                'posting'   => $v['channel_id'],
                            )) . "'>Show in T3Leads.com</a>",
                        ));  

                        $message->setSubject("T3Leads Redirect Report - " . T3Cache_BuyerChannel::getUnlinks($v['channel_id']));
                        
                        foreach($emails as $el){
                            $message->addTo($el['email'], $el['name']);  
                        }
                        
                        $message->SendMail();
                    }  
                }             
            }
            //varExport(count($all));  
            T3Db::api()->insertMulty($type, array_keys($all[0]), $all, 100);
        }   
    } 
    
    static public function reIndexBuyer_10mins  ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_10mins");  }
    static public function reIndexBuyer_30mins  ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_30mins");  }
    static public function reIndexBuyer_1hour   ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_1hour");   }
    static public function reIndexBuyer_3hours  ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_3hours");  }
    static public function reIndexBuyer_6hours  ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_6hours");  }
    static public function reIndexBuyer_12hours ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_12hours"); }
    static public function reIndexBuyer_daily   ($from, $till){ self::reIndexBuyer($from, $till, "redirect_buyer_daily");   }

    
    /**
    * Переиндексировать данные несовпадениях IP в редиректах
    * 
    * @param mixed $date
    * @param mixed $sendMail
    */
    static public function indexMatchIP($date, $sendMail = false){
        T3Db::api()->delete("redirects_match_ip", "`date`='{$date}'");
        
        $all = T3Db::api()->fetchAll("
            select 
                webmasterID as webmaster, 
                sum(redirectComplite) as good_redirects, 
                sum(matchRedirectIP) as match_ip, 
                (sum(matchRedirectIP)*100/sum(redirectComplite)) match_ip_percent
            from post_redirect where createDate between '{$date}' and '{$date} 23:59:59'
            group by webmasterID
            having good_redirects>0
            order by good_redirects desc
        ");
        
        
        
        if(count($all)){
            $webmasters = array();
            foreach($all as $v){
                $webmasters[] = $v['webmaster'];  
            }
            
            // кешировать ID ашентов
            T3Cache_WebmasterAgent::load($webmasters);
            
            foreach($all as $k => $v){
                $all[$k]['agent'] = T3Cache_WebmasterAgent::get($v['webmaster']); 
                $all[$k]['date']  = $date;
            }
            
            
            
            T3Db::api()->insertMulty('redirects_match_ip', array_keys($all[0]), $all); 
            
            if($sendMail){
                $badForAdmin = array();
                $badForAgent = array();
                
                foreach($all as $v){
                    if($v['match_ip_percent'] < 90){
                        $badForAdmin[] = $v;
                        
                        if($v['agent']){
                            if(!isset($badForAgent[$v['agent']])) $badForAgent[$v['agent']] = array();
                            $badForAgent[$v['agent']][] = $v;
                        }    
                    }    
                }
                
                if(count($badForAdmin)){
                    // отправить письмо адмам, данные = $badForAdmin
                    $table = new AZend_Table("table");
                    
                    $table->addField_Date('date', 'Date');
                    $table->addField_User('agent', 'Agent');
                    $table->addField_Publisher('webmaster', 'Webmaster');
                    $table->addField('good_redirects', 'Success Redirects');
                    $table->addField('match_ip', 'Redirects with a matching IP');
                    $table->addField('match_ip_percent', 'Matching %');
                    
                    $table->setData($badForAdmin);
                    
                    T3Mail::createMessage('redirectIpMatch', array('data' => $table->render()))
                    ->addToArray(array(
                        'hrant.m@t3leads.com'
                    )) 
                    ->SendMail();  
                }
                
                if(count($badForAgent)){
                    foreach($badForAgent as $agent => $dataForAgent){
                        // отправить письмо агенту $agent, данные = $dataForAgent
                        $table = new AZend_Table("table");
                    
                        $table->addField_Date('date', 'Date'); 
                        $table->addField_Publisher('webmaster', 'Webmaster');
                        $table->addField('good_redirects', 'Success Redirects');
                        $table->addField('match_ip', 'Redirects with a matching IP');
                        $table->addField('match_ip_percent', 'Matching %');
                        
                        $table->setData($dataForAgent);
                        
                        T3Mail::createMessage('redirectIpMatch', array('data' => $table->render()))
                        ->addToArray(array(
                            T3Users::getUserById($agent)->email,
                        )) 
                        ->SendMail(); 
                    }
                }
            }
        }
    }
}