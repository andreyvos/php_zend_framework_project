<?php

class T3Synh_Runv2v1 {
    public $run = true;
    
    static protected $runNow = true;
    static public function isRun(){
        return self::$runNow;   
    }
}