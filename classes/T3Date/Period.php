<?php 

require_once 'T3Date/Date.php';

class T3Date_Period {
    
    private $periods;
    private $period_now;
    private $period_default;
    
    private $fromDefault;
    private $fromDefault_mktime;
    private $tillDefault;
    private $tillDefault_mktime;
    private $lenPeriod;
    
    /**
    * Можно передавать значения по умолчанию для дат
    * 
    * @param mixed $from
    * @param mixed $till
    */
    public function __constuct($fromDefault = null,$tillDefault = null){
        $this->init($fromDefault,$tillDefault);  
    }
    
    private function init(){
        $this->periods = array(
            '7days'  => array('Last 7 Days',  array(-6,0)),
            '30days' => array('Last 30 Days', array(-29,0)),
            '60days' => array('Last 60 Days', array(-59,0)), 
            '3month' => array('Last 3 Months', array(-90,0)), 
        );
        $this->period_default = "7days";   
    }
    
    public function setPeriod(&$Period){
        $this->period_now = $this->period_default;
        
        // поиск подхдящего значения
        if(isset($Period) && is_string($Period)){
            foreach($this->periods as $key => $p){
                if($key == $Period){
                    $this->period_now = $key;  
                    break;  
                }        
            }
        } 
        
        // параметры
        $this->lenPeriod = $this->periods[$this->period_now][1][1] - $this->periods[$this->period_now][1][0];
        $this->fromDefault_mktime = mktime(0,0,0,date('m'),date('d')+$this->periods[$this->period_now][1][1],date('Y'));   
        $this->tillDefault_mktime = mktime(0,0,0,date('m'),date('d')+$this->periods[$this->period_now][1][0],date('Y'));
        $this->fromDefault = date("Y-m-d",$this->fromDefault_mktime);
        $this->tillDefault = date("Y-m-d",$this->tillDefault_mktime);   
    }
    
    public function getPeriods(){
        $class = new self();
        $class->init();
        $periods = array();
        
        foreach($class->periods as $key => $p) $periods[$key] = $p[0]; 
               
        return $periods;    
    }
    
    /**
    *  количество дней от начала до конца периода 
    */
    public function getLenPeriod(){
        if(isset($this->period_now)){
            return $this->lenPeriod;    
        }  
        return null;        
    }
    
    /**
    *  YYYY-MM-DD начала периода
    */
    public function getFromDateString(){
        if(isset($this->period_now)){
            return $this->fromDefault;    
        }  
        return null;        
    }
    
    /**
    *  YYYY-MM-DD конца периода
    */
    public function getTillDateString(){
        if(isset($this->period_now)){
            return $this->tillDefault;    
        }  
        return null;        
    }
    
    /**
    *  Timestamp начала периода
    */
    public function getFromDateTimestamp(){
        if(isset($this->period_now)){
            return $this->fromDefault_mktime;    
        }  
        return null;        
    }
    
    /**
    *  Timestamp конца периода
    */
    public function getTillDateTimestamp(){
        if(isset($this->period_now)){
            return $this->tillDefault_mktime;    
        }  
        return null;        
    }
    
    
}  