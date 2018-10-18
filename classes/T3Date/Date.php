<?php 

class T3Date_Date {
    private $runSuccess = false;
    
    private $stringDate;
    private $intDate;
    
    private $y;
    private $m;
    private $d;
    
    
    function __construct($date = null,$defaultDate = null){
        if(isset($date)){
            $this->setDateString($date,$defaultDate);
        }      
    }
    
    /**
    * Получение новой даты из строки формата YYYY-MM-DD
    * 
    * @param mixed $date
    * @param mixed $defaultDate
    */
    public function setDateString($date,$defaultDate = null){
        if(!$this->run_dateString($date)){
            if(!is_null($defaultDate)){
                $this->run_dateString($defaultDate);
            }        
        }       
    }
    
    private function run_dateString($date){
        $this->runSuccess = false;
        unset($this->intDate, $this->stringDate,$this->y,$this->m,$this->d);
        
        if(is_string($date)){
            $y = substr($date,0,4);
            $m = substr($date,5,2);
            $d = substr($date,8,2);
            
            if(is_numeric($y) && is_numeric($m) && is_numeric($d)){
                if(checkdate($m,$d,$y)){
                    $this->runSuccess = true;
                    list($this->y,$this->m,$this->d) = array($y,$m,$d);
                    if($mktime)return mktime(0,0,0,$m,$d,$y);
                    return true;
                }   
            }
        }
        return false;     
    }
    
    /**
    * Текущая дата в фиде строки формата YYYY-MM-DD
    * 
    */
    public function getString(){
        if($this->runSuccess){
            return "{$this->y}-{$this->m}-{$this->d}";
        } 
        else {
            return null;
        }   
    }
    
    /**
    * Текущая дата в формете поддерживаемом PHP функцией date()
    * 
    * @param mixed $format
    * @return string
    */
    public function getFormatString($format){
        if($this->runSuccess){
            return date($format,$this->getTimestamp());
        } 
        else {
            return null;
        }   
    }
    
    /**
    * Числовая метка текущей даты
    * 
    */
    public function getTimestamp(){
        if($this->runSuccess){
            if(!$this->intDate){
                $this->intDate = mktime(0,0,0,$this->m,$this->d,$this->y);    
            }
            return $this->intDate;
        } 
        else {
            return null;
        }     
    }
}  