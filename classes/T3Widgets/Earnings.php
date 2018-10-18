<?php

class T3Widgets_Earnings extends T3Widgets_Abstract {
    
    public $todayEarinigs;
    public $yesterdayEarinigs;
    public $lastWeekEarinigs;
    public $lastMonthEarinigs;
    
    public $previousWeekEarinigs;
    public $previousMonthEarinigs;
    
    public function __construct(){
        if(T3Users::getCUser()->isRoleWebmaster()){
            $cache = T3Db::api()->fetchOne("select `data` from webmasters_earnings_cache where webmaster=? and `date`=?", array(T3Users::getCUser()->company_id, date("Y-m-d")));
            
            
            if($cache === false){  
                $cache = array(
                    'y'  => round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                        T3Users::getCUser()->company_id,
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y"))),
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")))
                    )), 2),
                    'lw' => round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                        T3Users::getCUser()->company_id,
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-7, date("Y"))),
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")))
                    )), 2),
                    'lm' => round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                        T3Users::getCUser()->company_id,
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-30, date("Y"))),
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-1, date("Y")))
                    )), 2),
                    
                    'pw' => round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                        T3Users::getCUser()->company_id,
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-14, date("Y"))),
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-8, date("Y")))
                    )), 2), 
                    'pm' => round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                        T3Users::getCUser()->company_id,
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-60, date("Y"))),
                        date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-31, date("Y")))
                    )), 2),
                    
                );
                
                T3Db::api()->delete("webmasters_earnings_cache", "webmaster='" . T3Users::getCUser()->company_id . "'");
                try{ 
                    T3Db::api()->insert("webmasters_earnings_cache", array(
                        'webmaster' => T3Users::getCUser()->company_id,
                        'date' => date("Y-m-d"),
                        'data' => serialize($cache),
                    )); 
                }
                catch (Exception $e){
                    $this->show = false; 
                } 
            }
            else {
                $cache = unserialize($cache);    
            }
            
            if($this->show == true){
                $this->todayEarinigs = round((float)T3Db::api()->fetchOne("select sum(moneyWM)+sum(moneyRef)+sum(moneyBonuses)+sum(moneyWMReturns)+sum(moneyAD) from cache_summary_days where userid=? and `date` BETWEEN ? and ?", array(
                    T3Users::getCUser()->company_id,
                    date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y"))),
                    date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")))
                )), 2);
                $this->yesterdayEarinigs = ifset($cache['y'])+0; 
                $this->lastWeekEarinigs  = ifset($cache['lw'])+0; 
                $this->lastMonthEarinigs = ifset($cache['lm'])+0;
                
                $this->previousWeekEarinigs  = ifset($cache['pw'])+0; 
                $this->previousMonthEarinigs = ifset($cache['pm'])+0; 
            }  
        }
        else {
            $this->show = false;
        }  
    }      
}