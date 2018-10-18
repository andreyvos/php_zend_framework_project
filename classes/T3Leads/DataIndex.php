<?php

class T3Leads_DataIndex {
    static protected $specs = array();
    
    /**
    * @param mixed $product
    * @return T3Leads_DataIndex_Product_Abstract | null
    */
    static protected function getSpec($product){
        if(!isset(self::$specs[$product])){     
            $file = dirname(__FILE__) . "/DataIndex/Product/{$product}.php";
            if(is_file($file)){
                include_once $file;
                
                $class = "T3Leads_DataIndex_Product_{$product}";
                self::$specs[$product] = new $class();
            }
            else {
                self::$specs[$product] = null;
            }
        }
        return self::$specs[$product];
    }
    
    static public function reindex($date){
        $products = self::getProducts();
        
        if(count($products)){
            foreach($products as $product => $v){
                self::reindexProduct($product, $date);
            }
        }   
    }
    
    static public function reindexProduct($product, $date){
        /**
        * 1. найти ID первого и последнего лида за этот день
        * 2. получить НЕОБХОДИМЫЕ для этого продукта переиндексируеммые данные из основной таблицы (уже сгруппированные)
        * 3. для каждого ПОЛЯ, которое содержит НЕ ЧИСЛОВЫЕ даннеы получить ID значения
        * 4. записать
        */
        
        if(!self::getSpec($product)) return false;
        
        $minID = T3Db::apiReplicant()->fetchOne("select min(id) from leads_data where `datetime` between ? and ?", array(
            "{$date} 00:00:00", "{$date} 23:59:59"
        ));
        
        if(!$minID) return false;
        
        $maxID = T3Db::apiReplicant()->fetchOne("select max(id) from leads_data where `datetime` between ? and ?", array(
            "{$date} 00:00:00", "{$date} 23:59:59"
        ));
        
        // общее количетво лидов по пользователям
        $wmCountIndex = T3Db::apiReplicant()->fetchPairs(
            "select userid, sum(all_leads) from cache_summary_days_details where product=? and `date`=? group by userid",
            array($product, $date)
        );
        
        
        // main
        foreach(self::getSpec($product)->getValues() as /** @var T3Leads_DataIndex_Value_Abstract */ $productObject){    
            $select = T3Db::apiReplicant()->select()
            ->from("leads_data", null)
            ->join("leads_data_{$product}", "leads_data.id=leads_data_{$product}.id", null)
            ->columns(array(
                'webmaster' => 'leads_data.affid',
                'channel'   => 'leads_data.channel_id',
                'value'     => "leads_data_{$product}.{$productObject->name}",
                'sold'      => new Zend_Db_Expr("sum(CASE WHEN leads_data.wm>0 THEN 1 ELSE 0 END)"),
                "count"     => new Zend_Db_Expr("count(*)")   
            ))
            ->where("leads_data.id between {$minID} and {$maxID}")
            ->where("leads_data.status != 'process'")
            ->where("leads_data.status != 'error'")
            ->group(array(
                'leads_data.affid',
                'leads_data.channel_id',
                "leads_data_{$product}.{$productObject->name}"
            ))
            ->order("count desc")

            /**
             * Давид просил что бы было 100% данных, что бы количесво лидов по этому репорту сходилось с
             * количесвом лидов по системе
             *
             * ->having("`count` > 1")
             */;
                                            
            $all = T3Db::apiReplicant()->fetchAll($select);
            
            try{
                T3Db::cache()->delete(self::getTableName($product), "element='{$productObject->getID()}' and `date`='{$date}'");
            }
            catch(Exception $e){
                T3Db::cache()->query("
                    CREATE TABLE `" . self::getTableName($product) . "` (                              
                       `id` int(10) unsigned NOT NULL AUTO_INCREMENT,                      
                       `element` tinyint(3) unsigned DEFAULT NULL,                         
                       `date` date DEFAULT NULL,                                           
                       `webmaster` mediumint(8) unsigned DEFAULT NULL,                     
                       `channel` mediumint(8) unsigned DEFAULT NULL,                       
                       `value` int(10) unsigned DEFAULT NULL,                              
                       `sold` mediumint(8) unsigned DEFAULT NULL,
                       `count` mediumint(8) unsigned DEFAULT NULL,                         
                       `total` mediumint(8) unsigned DEFAULT NULL,                         
                       `rate` float unsigned DEFAULT NULL,                                 
                       PRIMARY KEY (`id`),                                                 
                       UNIQUE KEY `main` (`element`,`date`,`webmaster`,`channel`,`value`)  
                     )
                ");    
            }

            if(count($all)){
                $values = array();
                foreach($all as $k => $v){
                    $all[$k]['element'] = $productObject->getID();
                    $all[$k]['date']    = $date;
                    $all[$k]['total']   = (int)ifset($wmCountIndex[$v['webmaster']]);
                    
                    $values[] = $v['value'];
                    
                    if(isset($wmCountIndex[$v['webmaster']]) && $wmCountIndex[$v['webmaster']] > 0){
                        $all[$k]['rate'] = $v['count'] / $wmCountIndex[$v['webmaster']];   
                    }                                  
                    else {
                        $all[$k]['rate'] = 0;     
                    }
                }
                
                $valuesIndexes = $productObject->getValuesIndexes($values);

                foreach($all as $k => $v){                           
                    $all[$k]['value']   = (int)ifset($valuesIndexes[$v['value']], 0);  
                }

                $allNew = array();

                foreach($all as $el){
                    $k = $el['channel'] . "-" . $el['value'];

                    if(!isset($allNew[$k])){
                        $allNew[$k] = $el;
                    }
                    else {
                        $allNew[$k]['sold']+=   $el['sold'];
                        $allNew[$k]['count']+=  $el['count'];
                        $allNew[$k]['total']+=  $el['total'];
                        $allNew[$k]['rate']+=   $el['rate'];
                    }
                }

                $allNew = array_values($allNew);

                // varExport($allNew);

                T3Db::cache()->insertMulty(self::getTableName($product), array_keys($all[0]), $allNew);
            }        
        }  
    }
    
    static public function getProducts(){
        $products = array();
        $temp = T3Products::getProducts_MiniArray();
        
        foreach($temp as $k => $v){
            if(self::getSpec($k)){
                $products[$k] = $v;
            }
        }
        
        return $products;
    }
    
    static public function getFields($product){
        $result = array();
        
        if(self::getSpec($product)){
            $values = self::getSpec($product)->getValues();
    
            if(is_array($values) && count($values)){
                foreach($values as /** @var T3Leads_DataIndex_Value_Abstract */ $el){
                    $result[$el->id] = $el->name;    
                }
            }
        }    
        return $result;
    }
    
    static public function loadValues($product, $values){
        return self::getSpec($product)->loadValues($values);    
    }
    
    static public function getValue($product, $int){
        return self::getSpec($product)->getValue($int);
    }
    
    static public function getTableName($product){
        return "leads_data_{$product}_index";
    }
}
