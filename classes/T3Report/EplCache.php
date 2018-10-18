<?php

class T3Report_EplCache{
    static public $types = array(
        'payday' => array(
            'ppc' => array(
                'name' => 'ppc',
                'title' => 'PPC',
                'descr' => "For PPC Traffic the average EPL",
                'webmasters' => array('26368', '28006', '28744', '28809', '28775', '29434', '26431', '29072', '28381', '29255', '27974', '27767', '29290', '25599', '25277', '25591', '25634', '27566', '28885')
            ),
            'seo' => array(
                'name' => 'seo',
                'title' => 'SEO',
                'descr' => "For SEO Traffic the average EPL",
                'webmasters' => array('26046', '25583', '23424', '19418', '28161', '29255', '22424', '23848', '28634', '22415', '19131', '21368')
            ),
            'email' => array(
                'name' => 'email',  
                'title' => 'Email',
                'descr' => "For Email Traffic the average EPL",
                'webmasters' => array('24770', '26918')
            ), 
        ),
    );
    
    static public function getProducts(){
        $result = array('' => 'Select One...');
        
        foreach(self::$types as $name => $type){
            $addMethods = '';
            if(count($type)){
                $methodsArray = array();
                foreach($type as $method){
                    $methodsArray[] = $method['title'];    
                }
                $addMethods = " (" . implode(", ", $methodsArray) . ")";
            }
            $result[$name] = T3Products::getTitle($name) . $addMethods;         
        }
        
        return $result;
    } 
    
    
    static public function getMethods($product){
        if(isset(self::$types[$product]) && is_array(self::$types[$product]) && count(self::$types[$product])){
            return self::$types[$product];       
        } 
        return array();     
    }
    
    static public function reindexDate($date, $productCache = null){
        foreach(self::$types as $product => $types){
            if(is_null($productCache) || $productCache == $product){
                if(is_array($types) && count($types)){
                    foreach($types as $type){
                        $header = new T3Report_Header();
                        $header->dateFrom = $date;
                        $header->dateTill = $date;
                        $header->webmasters = $type['webmasters'];
                        $header->product = $product;
                        
                        $result = T3Report_Summary::getMainCacheData($header, true);
                        
                        $topEpl = (float)T3Db::api()->fetchOne("select max(round((moneyWM/all_leads), 2)) as epl from cache_summary_days_details where 
                        `date` = ? and product=? and userid in ('" . implode("','", $type['webmasters']) . "') and sold_leads > 10", array(
                            $date,
                            $product
                        ));
                        
                        T3Db::api()->delete("epl_summary_cache", "`date` = '{$date}' and `type`='{$type['name']}' and `product`='{$product}'"); 
                        T3Db::api()->insert("epl_summary_cache", array(
                            'date' => $date,
                            'type' => $type['name'],
                            'product' => $product,
                            'epl'  => $result['epl'],
                            'top_epl' => $topEpl,
                        ));
                    }
                }
            }
        }    
    } 
    
    static public function reindexStatesReport(){
        $select = T3Db::cache()->select()
        ->from("report_states_v1", array(
            'state',  
            new Zend_Db_Expr("round(sum(epl)/count(*), 2) as epl"),
        ))
        ->where("`date` BETWEEN '".date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d")-90, date("Y")))."' and '".date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d")-1, date("Y")))."'")
        ->group('state');
        
        $selectLeads = T3Db::cache()->select()
        ->from("report_states_v1", array(
            'state',  
            new Zend_Db_Expr("sum(leads)"),
        ))
        ->where("`date` BETWEEN '".date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d")-90, date("Y")))."' and '".date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d")-1, date("Y")))."'")
        ->group('state');
        
        foreach(self::$types as $product => $types){
            $selectProduct = clone $select;
            $selectProduct->where('product=?', $product);
            
            $selectLeadsProduct = clone $selectLeads;
            $selectLeadsProduct->where('product=?', $product); 
            
            $index = array();
            $index['all'] = T3Db::cache()->fetchPairs($selectProduct);  
            $index['leads_all'] = T3Db::cache()->fetchPairs($selectLeadsProduct);  
            
            if(is_array($types) && count($types)){
                foreach($types as $type){
                    $selectType = clone $selectProduct;
                    $selectType->where("webmaster in (?)", $type['webmasters']); 
                    $index[$type['name']] = T3Db::cache()->fetchPairs($selectType);
                }
            }
            
            T3Db::cache()->delete("report_states_monthly_epl", "`product`='{$product}'");
            foreach(AZend_Geo::getStatesList() as $state => $stateTitle){
                T3Db::cache()->insert("report_states_monthly_epl", array(
                    'product'   => $product,
                    'state'     => $state, 
                    'leads'     => ifset($index['leads_all'][$state], 0), 
                    'epl_seo'   => ifset($index['seo'][$state], 0),
                    'epl_ppc'   => ifset($index['ppc'][$state], 0),
                    'epl_email' => ifset($index['email'][$state], 0),
                    'epl_all'   => ifset($index['all'][$state], 0),    
                ));
            } 
        }    
    }   
}