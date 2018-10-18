<?php

class T3Cache_LeadsQuality {
    static protected $data;
    
    static public function load($leadsIds){
        $ids = array();
        
        if(!is_array($leadsIds))$leadsIds = array($leadsIds);
        
        if(count($leadsIds)){
            foreach($leadsIds as $id){
                if(is_numeric($id) && !isset(self::$data[$id])){
                    $ids[] = $id; 
                    self::$data[$id] = array();      
                }
            }
            
            if(count($ids)){
                $result = T3Db::apiReplicant()->fetchAll("select id, complite, duplicateCount, isFroud, isCurrentDuplicate from leads_quality where id in (" . implode(",", $ids) . ")");
                
                if(count($result)){
                    foreach($result as $res){
                        self::$data[$res['id']] = $res;
                    }    
                }
                
                try {
                    $result = T3Db::apiReplicant()->fetchPairs("select lead_id, fraud_coef from auto_fill_log where lead_id in (" . implode(",", $ids) . ")");
                    
                    if(count($result)){
                        foreach($result as $lead => $coef){
                            if(!isset(self::$data[$lead])) self::$data[$lead] = array();
                            self::$data[$lead]['froud_coef'] = $coef;
                        }    
                    }
                }
                catch(Exception $e){}
            } 
        }   
    }
    
    
    static public function getData($leadID){
        self::load($leadID);
        return self::$data[$leadID];   
    }
    
    static protected function colorGetHex($colorCoef, $max = 100){
        $colorCoef = (int)$colorCoef;
        
        $colorCoef = $colorCoef * 255 / $max; 
        
        if($colorCoef < 0) $colorCoef = 0;
        if($colorCoef > 255) $colorCoef = 255;
        
        $colorCoef = (string)dechex($colorCoef);
        if(strlen($colorCoef) == 1) $colorCoef = "0{$colorCoef}";
        
        return $colorCoef;
    }
    
    static public function render($leadID){
        MyZend_Site::addCSS('table/quality.css');
        MyZend_Site::addJS('table/lead_quality.js');

        $result = self::getData($leadID);


        if(is_array($result) && count($result)){
            $leadID = IdEncryptor::encode($leadID);

            $options = array(
                'froud' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
                'dupNow' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
                'dupMonthly' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
                'froud_coef' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
            );

            // Фрод
            if(isset($result['isFroud'])){
                $options['froud']['color'] = ($result['isFroud']) ? "#F75" : "#D3F4B3";
                //return $options['froud']['color'];
                //return var_export($result, 1);
            }
            
            if(isset($result['froud_coef'])){
                $coef = $result['froud_coef'];
                $blue   = 100 - (abs(50 - $coef) * 2); // 0 - 100

                $add = $blue / 3;
                
                $red    = 40 + (($coef) * 90 / 100) + $add; // 0 - 100
                $green  = 20 + ((100 - $coef) * 70 / 100) + $add; // 0 - 100

                $options['froud_coef']['color'] = "#" . self::colorGetHex($red) . self::colorGetHex($green) . self::colorGetHex($blue); 
                $options['froud_coef']['text'] = $result['froud_coef'];  
            }

            // Долгосрочный дупликат
            if(isset($result['duplicateCount']) && isset($result['complite']) && $result['complite'] == '1'){
                $options['dupMonthly']['text'] = $result['duplicateCount'];

                $colors = array(
                    array(0, '#D3F4B3'),
                    array(3, '#E6F7B0'),
                    array(5, '#EEF9AE'),
                    array(10, '#FAF0AD'),
                    array(20, '#FBDFAC'),
                    array(30, '#FDCFAA'),
                );

                $options['dupMonthly']['color'] = "#FDAA99";
                foreach($colors as $opt){
                    if($result['duplicateCount'] <= $opt[0]){
                        $options['dupMonthly']['color'] = $opt[1];
                        break;
                    }
                }
            }

            // Быстрый дупликат
            if(isset($result['isCurrentDuplicate']) && isset($result['complite']) && $result['complite'] == '1'){
                if($result['isCurrentDuplicate'])   $options['dupNow']['color'] = "#FF6464";
                else                                $options['dupNow']['color'] = "#D3F4B3";
            }

            $froudCoefText = "";
            if(T3Users::getCUser()->isRoleAdmin()){
                $froudCoefText = "<span class='qualitySpan' style='background:{$options['froud_coef']['color']}'>{$options['froud_coef']['text']}</span>";   
            }

            return "<span class='tableQuality' id='lead_quality_main_{$leadID}'><a class='qualityA' onClick=\"createQualityMenu('{$leadID}')\">" .
                "<span class='qualitySpan' style='background:{$options['froud']['color']}'>{$options['froud']['text']}</span>" .            // Froud Detector
                "<span class='qualitySpan' style='background:{$options['dupNow']['color']}'>{$options['dupNow']['text']}</span>" .          // Critical Duplicate
                "<span class='qualitySpan' style='background:{$options['dupMonthly']['color']}'>{$options['dupMonthly']['text']}</span>" .  // Monthly Duplicate
                $froudCoefText .  // Froud Coef
            "<div class='qualityInfo' id='lead_quality_div_{$leadID}'></div></a></span>";
        }

        return "";
        
    } 
}