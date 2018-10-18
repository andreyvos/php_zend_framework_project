<?php

class T3Price_Form {
    static public $groups = array();
    static public function getGroups($product){
        if(!isset(self::$groups[$product])){
            self::$groups[$product] = array();
            
            self::$groups[$product] = T3Db::api()->fetchAll("
                select 
                    * 
                from 
                    prices_games_by_forms_default 
                where 
                    product=?
                order by `order`
                ", 
                $product
            );
            if(count(self::$groups[$product])){
                foreach(self::$groups[$product] as $k => $v){
                    self::$groups[$product][$k]['md5'] = md5(
                        $v['product'] . 
                        $v['bank_max'] . 
                        $v['bank_min'] . 
                        $v['wmPercent'] . 
                        $v['highMaxCoefficient'] . 
                        $v['highPriceProbabiliyPercents'] . 
                        $v['roundingToBank'] . 
                        $v['highPriceMinBuyerValue'] . 
                        $v['addingMinPrice'] . 
                        $v['addingMaxPrice'] . 
                        $v['addingPubliserPercent'] .
                        $v['addingProbabilityPercent'] . 
                        $v['webmasterMaxPrice']     
                    );
                    
                    self::$groups[$product][$k]['md5_np'] = md5(
                        $v['product'] . 
                        $v['bank_max'] . 
                        $v['bank_min'] . 
                        $v['highMaxCoefficient'] . 
                        $v['highPriceProbabiliyPercents'] . 
                        $v['roundingToBank'] . 
                        $v['highPriceMinBuyerValue'] . 
                        $v['addingMinPrice'] . 
                        $v['addingMaxPrice'] . 
                        $v['addingPubliserPercent'] .
                        $v['addingProbabilityPercent'] . 
                        $v['webmasterMaxPrice']     
                    );
                }
            }
                  
        }
        return self::$groups[$product];
    }
    
    static public function getGroupSettings($id){
        $r = T3Db::api()->fetchRow("
            select 
                bank_max,
                bank_min,
                wmPercent,
                highMaxCoefficient,
                highPriceProbabiliyPercents,
                roundingToBank,
                highPriceMinBuyerValue,
                addingMinPrice,
                addingMaxPrice,
                addingPubliserPercent,
                addingProbabilityPercent,
                webmasterMaxPrice 
            from 
                prices_games_by_forms_default
            where
                id=?
        ", $id);
        
        if(!is_array($r)) $r = array();
        return $r; 
    }
    
    static public function getGroupsNames($product, $firstEl = null){
        $result = array();
        if(!is_null($firstEl)) $result[''] = $firstEl;
        $g = self::getGroups($product);
        if(count($g)){
            foreach($g as $el){
                $result[$el['id']] = "{$el['group']} ({$el['wmPercent']})";
            }
        }
        return $result;
    }
    
    static public function getGroup($options, $notPrice = false){
        if(is_numeric($options)){
            $options = T3Db::api()->fetchRow("select * from prices_games_by_forms where id=?", $options);
        }
        $v = $options;
        
        $default = self::getGroups($options['product']);
        
        if(count($default)){
            foreach($default as $el){
                $md5 =  md5(
                    $v['product'] . 
                    $v['bank_max'] . 
                    $v['bank_min'] . 
                    $v['wmPercent'] . 
                    $v['highMaxCoefficient'] . 
                    $v['highPriceProbabiliyPercents'] . 
                    $v['roundingToBank'] . 
                    $v['highPriceMinBuyerValue'] . 
                    $v['addingMinPrice'] . 
                    $v['addingMaxPrice'] . 
                    $v['addingPubliserPercent'] .
                    $v['addingProbabilityPercent'] . 
                    $v['webmasterMaxPrice']     
                );
                
                if($md5 == $el['md5']){
                    return $el['group'] . " ({$v['wmPercent']})";
                }
            }
            
            if($notPrice){
                foreach($default as $el){
                    $md5 =  md5(
                        $v['product'] . 
                        $v['bank_max'] . 
                        $v['bank_min'] .  
                        $v['highMaxCoefficient'] . 
                        $v['highPriceProbabiliyPercents'] . 
                        $v['roundingToBank'] . 
                        $v['highPriceMinBuyerValue'] . 
                        $v['addingMinPrice'] . 
                        $v['addingMaxPrice'] . 
                        $v['addingPubliserPercent'] .
                        $v['addingProbabilityPercent'] . 
                        $v['webmasterMaxPrice']     
                    );
                    
                    if($md5 == $el['md5_np']){
                        return $el['group'] . " but " . $v['wmPercent'];
                    }
                }
            }
        }         
        
        return "Unknown";
    }
    
    static public function copySettings($gameID, $groupID, $isCopyMainProcent){
         
    }
}