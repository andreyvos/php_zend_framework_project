<?php
  class T3IpTest {   
  
    public static function test($ip_num,$lead_id=0,$aff_id=0,$date=''){
        $system = T3System::getInstance();

        if (!in_array($aff_id,array('1'))){

            $all_isset_ip = $system->getConnect()->fetchRow("SELECT proxy_source.title,proxy_list.date_add FROM proxy_list,proxy_source WHERE proxy_list.source=proxy_source.id AND proxy_list.proxy=$ip_num");
            if ($all_isset_ip){
                $data = array();
                $data['result'] = 'banned';
                $data['ip'] = $ip_num;
                $data['lead_id'] = $lead_id;
                $data['aff_id'] = $aff_id;
                $data['date'] = $date;
                $data['comment'] = sprintf("%s %s",$all_isset_ip['title'],$all_isset_ip['date_add']);
                $system->getConnect()->insert('iptest_result',$data);

                $data = array(
                    'max'=>$lead_id
                );
                $system->getConnect()->update('iptest_maxlead',$data);
                return false;
            }else{
                $isset_ip = $system->getConnect()->fetchRow("select * from iptest_result where ip=$ip_num");
                if ($isset_ip){
                    $data = array();
                    $data['result'] = $isset_ip['result'];
                    $data['ip'] = $ip_num;
                    $data['lead_id'] = $lead_id;
                    $data['aff_id'] = $aff_id;
                    $data['date'] = $date;
                    $data['comment'] = $isset_ip['comment'];
                    $system->getConnect()->insert('iptest_result',$data);

                    $data = array(
                        'max'=>$lead_id
                    );
                    $system->getConnect()->update('iptest_maxlead',$data);
                    return false;
                }else{
                    $ip = myHttp::get_ip_str($ip_num);
                    $url = sprintf("http://iptest.t3leads.com/blockscript/detector.php?blockscript=api&api_key=wzoym1a2dvr9w1nmyvu8vlzvcwmbvcxg5e4pefst&action=test_ipv4&ip=%s",$ip);
                    $result = file_get_contents($url);

                    $parse_obj = new ParseXmlResponse();

                    $r = $parse_obj->XMLStringToArray(
                        $result,
                        array(
                            'blocked'     => array('xpath' => '/response/ip/blocked', 'require' => false, 'default' => null),
                            'option'   => array('xpath' => '/response/ip/option', 'require' => false, 'default' => null),
                            'status'   => array('xpath' => '/response/status', 'require' => false, 'default' => null),
                        )
                    );

                    if (isset($r['status']) && $r['status'] == 'SUCCESS'){
                        $data = array();
                        if (isset($r['blocked']) && $r['blocked'] == 'YES'){
                            $data['result'] = 'banned';
                            $data['ip'] = $ip_num;
                            $data['lead_id'] = $lead_id;
                            $data['aff_id'] = $aff_id;
                            $data['date'] = $date;
                            if (isset($r['option'])){
                                $data['comment'] = $r['option'];
                            }
                            $system->getConnect()->insert('iptest_result',$data);
                            return false;
                        }
                        $data = array(
                            'max'=>$lead_id
                        );
                        $system->getConnect()->update('iptest_maxlead',$data);
                    }
                }
            }
        }else{
            $data = array(
                'max'=>$lead_id
            );
            $system->getConnect()->update('iptest_maxlead',$data);
        }

        return true;
    }
    
  }

