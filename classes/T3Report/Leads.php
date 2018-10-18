<?php

class T3Report_Leads {
    public $reportVersions = array(
        'v1' => 'Leads Version 1',
        'v2' => 'Leads Version 2',
    );
    
    /**   
    * @var T3Report_Leads_Version1
    */
    public $version1;
    
    /**   
    * @var T3Report_Leads_Version2
    */
    public $version2;
    
    /**
    * Текущаяя версия
    * 
    * @var mixed
    */
    public $currentVersion = 'v2';
    
    public function __construct(){
        $this->version1 = new T3Report_Leads_Version1();
        $this->version2 = new T3Report_Leads_Version2();    
    }
    
    public function getCurrentTypes(){
            
    }
    
    public function setTotal($array){
        $this->version1->total = array(
            'all_leads'         => $array['all_leads_v1'],
            'sold_leads'        => $array['sold_leads_v1'],
            'return_leads'      => $array['return_leads_v1'],
            'bonuses_count'     => $array['bonuses_count_v1'],
            'moneyWMReturns'    => $array['moneyWMReturns_v1'],
            'moneyBonuses'      => $array['moneyBonuses_v1'],
            'moneyWM'           => $array['moneyWM_v1'],
            'moneyAgent'        => ifset($array['moneyAgent_v1'],0), 
            'moneyRef'          => ifset($array['moneyRef_v1'],0), 
            'moneyTTL'          => ifset($array['moneyTTL_v1'],0), 
        );
        
        $this->version2->total = array(
            'all_leads'         => round($array['all_leads'] - $array['all_leads_v1'], 2),
            'sold_leads'        => round($array['sold_leads'] - $array['sold_leads_v1'], 2),
            'return_leads'      => round($array['return_leads'] - $array['return_leads_v1'], 2),
            'bonuses_count'     => round($array['bonuses_count'] - $array['bonuses_count_v1'], 2),
            'moneyWMReturns'    => round($array['moneyWMReturns'] - $array['moneyWMReturns_v1'], 2),
            'moneyBonuses'      => round($array['moneyBonuses'] - $array['moneyBonuses_v1'], 2),
            'moneyWM'           => round($array['moneyWM'] - $array['moneyWM_v1'], 2),
            'moneyAgent'        => round(ifset($array['moneyAgent'],0) - ifset($array['moneyAgent_v1'],0), 2), 
            'moneyRef'          => round(ifset($array['moneyRef'],0) - ifset($array['moneyRef_v1'],0), 2), 
            'moneyTTL'          => round(ifset($array['moneyTTL'],0) - ifset($array['moneyTTL_v1'],0), 2), 
        );
    }
    
    public function setVersion($version){
        // значение по умолчанию
        if(!isset($_SESSION['report_leads_system_version'])){
            $this->currentVersion = 'v2';
            if($this->version1->total['all_leads'] > $this->version2->total['all_leads']){
                $this->currentVersion = 'v1';     
            }
        }
        else {
            $this->currentVersion = $_SESSION['report_leads_system_version']; 
        }
        
        
        
        if($this->currentVersion == 'v2' && $this->version2->total['all_leads'] == 0 && $this->version1->total['all_leads'] > 0){
            $this->currentVersion = 'v1';
        } 
        
        if($this->currentVersion == 'v1' && $this->version1->total['all_leads'] == 0){
            $this->currentVersion = 'v2';
        }
        
        // получение значения из внешней переменной
        if($version == 'v2' && $this->version2->total['all_leads']>0){
            $this->currentVersion = 'v2';    
        }
        
        if($version == 'v1' && $this->version1->total['all_leads']>0){
            $this->currentVersion = 'v1';    
        }
        
        // запись переменной в сессию
        $_SESSION['report_leads_system_version'] = $this->currentVersion;
    } 
    
    public function isMultiVersion(){
        if($this->version2->total['all_leads']>0 && $this->version1->total['all_leads']>0){
            return true;    
        }
        return false;
    } 
    
    public function getCurrentVersionsArray(){
        $result = array(); 
        
        foreach($this->reportVersions as $version => $title){
            $good = false;
            $add = array();
            
            if($version == 'v1' && $this->version1->total['all_leads']>0){
                $good = true;
                $add['count'] = $this->version1->total['all_leads'];
            }
            
            if($version == 'v2'){
                $add['count'] = $this->version2->total['all_leads'];
                
                if($this->version1->total['all_leads'] == 0 || $this->version2->total['all_leads'] > 0){
                    $good = true;    
                }
            }
            
            if($good){
                 $add['name']   = $version;
                 $add['title']  = $title;
                 if($this->currentVersion == $version){
                    $add['active'] = true;
                 }
                 else {
                    $add['active'] = false; 
                 } 
                 
                 $result[] = $add;     
            }             
        }
        return $result;    
    } 
    
    public function run(T3Report_Header &$header, $page){
        if($this->currentVersion == 'v1'){
            return $this->version1->run($header, $page);    
        }
        else {
            return $this->version2->run($header, $page);    
        }
    }
    
    public function getDataObject(){
        if($this->currentVersion == 'v1'){
            return $this->version1;    
        }
        else {
            return $this->version2;   
        }
    }
}