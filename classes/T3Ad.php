<?php

class T3Ad {
    /**
    * Преобразовать числовой ID в такой вид, что бы его нельзя было просто поменять.
    * 
    * @param mixed $id
    */
    static public function createSecureID($id){
        return ($hid = AP_IdEncryptor::encode($id)) . '-' . sprintf("%u", crc32("secureid=" . $hid . "-" . $id));   
    }
    
    /**
    * Получить исходный ID из закодорованного функцией: T3Ad::createSecureID($id)
    * 
    * 
    * $code = T3Ad::createSecureID($id);
    * $rid = T3Ad::decodeSecureID($code);
    * 
    * В итоге:
    * $id = $rid
    * 
    * @param mixed $secureAdsId
    */
    static public function decodeSecureID($secureAdsId){
        $secureAdsId = trim($secureAdsId);
        $r = explode("-", $secureAdsId);
        if(count($r) == 2){
            $id = AP_IdEncryptor::decode($r[0]);
            $hash = sprintf("%u", crc32("secureid=" . $r[0] . "-" . $id));
            
            if($id && $hash == $r[1]){
                return $id;
            }
        }
        return null;
    }
    
    /**
    * Сделать ссылку для рекламного блока
    * 
    * @param mixed $blockID      ID рекламного блока (id преобразуется так что поменять его без знания аглоритма его преобразования не получится)
    * @param mixed $subaccount   Субаккаунт (он будет передан так как есть, вебмастр без проблем сможет его поменять)
    */
    static public function createAdsLink($blockID, $subaccount = null){
        return AP_Http::urlConvertor(
            array(
                'b' => self::createSecureID($blockID),
                's' => $subaccount,   
            ),
            "https://f.t3leads.com/system/ad.php"
        );
    }
    
    
    
    
    /**
    * Получить данные по продажам за период
    * 
    * @param mixed $from
    * @param mixed $till
    * 
    * @return array    Example:
    *                    array ( 
    *                      'summary' => array (
    *                        28624 => array (
    *                          'webmaster' => 28624,
    *                          'value' => 194,
    *                        ),
    *                        28806 => array (
    *                          'webmaster' => 28806,
    *                          'value' => 485,
    *                        ),
    *                      ),
    * 
    *                      'details' => array (
    *                        0 => array (
    *                          'to_webmaster' => 28624,
    *                          'to_subaccount_str' => 'yahoo',
    *                          'to_subaccount_id' => '1124042',
    *                          'product' => 'payday',
    *                          'product_id' => 7,
    *                          'channel' => 1059,
    *                          'value' => 97,
    *                        ),
    *                        1 => array (
    *                          'to_webmaster' => 28806,
    *                          'to_subaccount_str' => 'z12sas',
    *                          'to_subaccount_id' => '1124043',
    *                          'product' => 'payday',
    *                          'product_id' => 7,
    *                          'channel' => 1059,
    *                          'value' => '97.00',
    *                        ),
    * 
    *                        // ...
    * 
    *                    )
    */
    static public function getPaysData($from, $till){
        $return = array(
            'summary' => array(),
            'details' => array(),
        );
        
        $adWebmaster = T3Aliases::getID('ad');
              
        $data = array(
            T3Db::api()->fetchAll(
                "select lead_product, channel_id, subaccount_id, sum(action_sum) as `value` from webmasters_leads_sellings 
                where webmaster_id=? and action_datetime between ? and ? group by subaccount_id, lead_product, channel_id",
                array(
                    $adWebmaster, $from, $till     
                )
            ),
            
            T3Db::api()->fetchAll(
                "select lead_product, channel_id, subaccount_id, sum(action_sum) as `value` from webmasters_leads_movements 
                where webmaster_id=? and action_datetime between ? and ? group by subaccount_id, lead_product, channel_id",
                array(
                    $adWebmaster, $from, $till    
                )
            ) 
        );  

        $data_full = array();
        $subaccounts = array();

        foreach($data as $ellist){
            foreach($ellist as $el){
                if(!isset($data_full[$el['subaccount_id']][$el['lead_product']][$el['channel_id']])){
                    $subaccounts[] = $el['subaccount_id'];
                    
                    $data_full[$el['subaccount_id']][$el['lead_product']][$el['channel_id']] = $el['value'];    
                }
                else {
                    $data_full[$el['subaccount_id']][$el['lead_product']][$el['channel_id']]+= $el['value'];    
                }
            }
        }

        if(count($data_full)){
            $subaccounts_index = T3Db::api()->fetchPairs("select id, `name` from users_company_webmaster_subacc where id in (" . implode(",", $subaccounts) . ")");
            
            foreach($data_full as $subacc => $subacc_el){
                foreach($subacc_el as $product => $product_el){
                    foreach($product_el as $channel => $value){
                        $subarr = explode(":", $subaccounts_index[$subacc]);
                        
                        if(
                            count($subarr) >= 2 &&                      // Если субаакаунт состоит из 2-х частей, первая это wmIdOrAlias, вторая это первичный субаккаунт
                            ($toWm = T3Aliases::getID($subarr[0])) &&   // Если по идентификатору можно найти вебмастра
                            strlen($subarr[1])                          // И Если есть субаккаунт (а он всегда будет)
                        ){
                            $return['details'][] = array(
                                'to_webmaster'          => $toWm,
                                'to_subaccount_str'     => ($toSubaccountStr = substr($subarr[1], 0, 255)),
                                'to_subaccount_id'      => T3WebmasterCompany::getSubaccountID($toWm, $toSubaccountStr),
                                
                                'product'               => $product,
                                'product_id'            => T3Products::getID($product),
                                'channel'               => $channel,
                                'value'                 => $value,
                                
                            );        
                        }
                    }
                }
            }
            
            foreach($return['details'] as $el){
                if(!isset($return['summary'][$el['to_webmaster']])){
                    $return['summary'][$el['to_webmaster']] = array(
                        'webmaster' => $el['to_webmaster'],
                        'value' => 0,
                    );   
                }
                $return['summary'][$el['to_webmaster']]['value'] = round($return['summary'][$el['to_webmaster']]['value'] + $el['value'], 2);  
            }   
                       
        }
        
        return $return;
    }
}