<?php

class T3MyValidator_DateRange {
    public $dateFrom_ts;
    public $dateTill_ts;
    
    public $dateFrom;
    public $dateTill;
    
    public $dateFrom_Str;
    public $dateTill_Str;
    
    public $dateFrom_Delta;
    public $dateTill_Delta;
    
    public $from_delta_days = -6;
    public $till_delta_days = 0;
    
    static protected $todayTS;
    static public function getTodayTS(){
        if(is_null(self::$todayTS)){
            self::$todayTS = mktime(0,0,0, date('m'), date('d'), date("Y"));
        }
        return self::$todayTS;    
    }
    
    public function setDefault(){
        $this->checkDates('','');   
    }
    
    /**
    * Проверка дат
    */
    public function checkDates($date1, $date2){
        $error = true;
        
        $date1 = trim($date1);
        $date2 = trim($date2);
        
        if(strlen($date1) && strlen($date2)){
            $ts1 = strtotime($date1);
            $ts2 = strtotime($date2);
            
            if($ts1 > 0 && $ts2 > 0){
                $nowTS = self::getTodayTS();
                $nowTS_1Year = $nowTS - 31536000;
                
                if($ts1 > $nowTS)$ts1 = $nowTS;
                if($ts2 > $nowTS)$ts2 = $nowTS;
                
                if($ts1 < $nowTS_1Year)$ts1 = $nowTS_1Year;
                if($ts2 < $nowTS_1Year)$ts2 = $nowTS_1Year;
                
                $error = false;
                $this->dateFrom_ts = min($ts1, $ts2);
                $this->dateTill_ts = max($ts1, $ts2);
            }    
        }
        
        if($error){
            $this->dateFrom_ts = mktime(0, 0, 0, date("m"), date("d")+$this->from_delta_days, date("Y"));
            $this->dateTill_ts = mktime(0, 0, 0, date("m"), date("d")+$this->till_delta_days,   date("Y"));    
        }
        
        $this->dateFrom         = date("Y-m-d",  $this->dateFrom_ts);
        $this->dateFrom_Str     = date("M d, Y", $this->dateFrom_ts);
        $this->dateFrom_Delta   = round(($this->dateFrom_ts - self::getTodayTS()) / 86400);
        
        $this->dateTill         = date("Y-m-d",  $this->dateTill_ts);
        $this->dateTill_Str     = date("M d, Y", $this->dateTill_ts);
        $this->dateTill_Delta   = round(($this->dateTill_ts - self::getTodayTS()) / 86400);     
    }    
}