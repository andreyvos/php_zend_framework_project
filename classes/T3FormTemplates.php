<?php

class T3FormTemplates {
    static public $webmasters = array(
    '26368', '28006', '28744', '28809', '28775', '29434', '26431', '29072', '28381', '29255', '27974', '27767', '29290', '25599', '25277', '25591', '25634', '27566', '28885',
    '26046', '25583', '23424', '19418', '28161', '29255', '22424', '23848', '28634', '22415', '19131', '21368'
    );
    
    static public function reindexMonthlyRatio(){
        /*
        $all = T3Db::api()->fetchAll("select id,product, `name` from form_template");
        
        if(count($all)){
            $date = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-30, date("Y")));
            
            foreach($all as $el){
                $update = array(
                    'months_clicks' => (int)T3Db::api()->fetchOne("select count(*) from forms_unique_clicks where `date` > ? and template=? and product=? and webmaster in ('" . implode("','", self::$webmasters) . "')", array($date, $el['name'], $el['product'])),
                    'months_leads' => (int)T3Db::api()->fetchOne("select count(*) from forms_add_leads where `date` > ? and template=? and product=? and webmaster in ('" . implode("','", self::$webmasters) . "')", array($date, $el['name'], $el['product'])),
                    'months_ratio' => 0,
                );
                
                if($update['months_clicks'] && $update['months_leads']){
                    $update['months_ratio'] = $update['months_leads'] / $update['months_clicks'];      
                }
                
                T3Db::api()->update("form_template", $update, "id={$el['id']}");
                
            }    
        } 
        */
    }    
}