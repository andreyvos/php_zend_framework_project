<?php

class T3TestSystem {

    public static function randomUrl($quantity = 2) {
        $levels = rand(1,2);
        if ($levels==1)
            $www = rand(0,2)==0;
        else
            $www = rand(0,10)==0;
        $s1 = '';
        for ($i1=0; $i1<$levels; $i1++)
            $s1 .= substr(md5(rand(1,$quantity)),0,4) . '.';
        $domains = array('com', 'net', 'org', 'us');
        $domain = $domains[rand(0,count($domains)-1)];
        $s1 = 'http://'.($www?'www.':'').$s1.$domain;
        $n1 = rand(0,2);
        for ($i1=0; $i1<$n1; $i1++)
            $s1.='/'.substr(md5(rand(1,$quantity)),0,4);
        $s1.='/?';
        for ($i1=0; $i1<$n1; $i1++)
            $s1.=substr(md5(rand(1,$quantity)),0,4).'='.substr(md5(rand(1,$quantity)),0,4).'&';
        $s1 = substr($s1,0,-1);
    }

    public static function ifJsFormTestLead() {
        if (isset($_GET['test']) && $_GET['test']==='1') {
            $_SERVER['HTTP_REFERRER'] = self::randomUrl();
        }
        //$_GET['form']['supervisor_phone_ext'] = rand(2,9);
        //$_GET['form']['work_phone_ext'] = rand(2,9);
    }

}