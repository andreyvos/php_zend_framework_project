<?php

class T3Cache_CallQuality {
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
                $result = T3Db::apiReplicant()->fetchAll("select id,quality_leads, quality_calls, quality_channel_id from phone_call where id in (" . implode(",", $ids) . ")");

                if(count($result)){
                    foreach($result as $res){
                        self::$data[$res['id']] = $res;
                    }
                }

            }
        }
    }


    static public function getData($leadID){
        self::load($leadID);
        if (isset(self::$data[$leadID])){
            return self::$data[$leadID];
        }else{
            return null;
        }
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
        MyZend_Site::addJS('table/call_quality.js');

        $result = self::getData($leadID);


        if(is_array($result) && count($result)){
            $leadID = IdEncryptor::encode($leadID);

            $options = array(
                'quality_leads' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
                'quality_calls' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
                'quality_channel_id' => array(
                    'color' => '#FFF',
                    'text'  => '',
                ),
            );

            $colors = array(
                array(0, '#D3F4B3'),
                array(3, '#E6F7B0'),
                array(5, '#EEF9AE'),
                array(10, '#FAF0AD'),
                array(20, '#FBDFAC'),
                array(30, '#FDCFAA'),
            );

            if((int)$result['quality_leads']>0){
                $options['quality_leads']['text'] = $result['quality_leads'];

                $options['quality_leads']['color'] = "#FDAA99";
                foreach($colors as $opt){
                    if($result['quality_leads'] <= $opt[0]){
                        $options['quality_leads']['color'] = $opt[1];
                        break;
                    }
                }
            }

            if((int)$result['quality_calls']>0){
                $options['quality_calls']['text'] = $result['quality_calls'];

                $options['quality_calls']['color'] = "#FDAA99";
                foreach($colors as $opt){
                    if($result['quality_calls'] <= $opt[0]){
                        $options['quality_calls']['color'] = $opt[1];
                        break;
                    }
                }
            }

            if((int)$result['quality_channel_id']>0){
                $options['quality_channel_id']['text'] = $result['quality_channel_id'];

                $options['quality_channel_id']['color'] = "#FDAA99";
            }

            return "<span class='tableQuality' id='lead_quality_main_{$leadID}'><a class='qualityA' onClick=\"createQualityMenu('{$leadID}')\">" .
            "<span class='qualitySpan' style='background:{$options['quality_leads']['color']}'>{$options['quality_leads']['text']}</span>" .
            "<span class='qualitySpan' style='background:{$options['quality_calls']['color']}'>{$options['quality_calls']['text']}</span>" .
            "<span class='qualitySpan' style='background:{$options['quality_channel_id']['color']}'>{$options['quality_channel_id']['text']}</span>" .
            "<div class='qualityInfo' id='lead_quality_div_{$leadID}'></div></a></span>";
        }

        return "";

    }
}