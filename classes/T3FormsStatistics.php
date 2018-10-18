<?php

/**
*  класс работающий с сервером статистики форм(formstats.t3leads.com)
*/
class T3FormsStatistics 
{
    public static $host = 'http://formsstats.t3leads.com';

    static $apiPath = 't3api.php';

    public static function query($q)
    {
        $r = file_get_contents($q);
        return json_decode($r, true);
    }

    public static function summary($from, $till, $templates, $product = null, $webmaster = null, $domain = null)
    {
        $params = "from={$from}&till={$till}";
        foreach ($templates as $template) 
        {
            $params.="&templates[]={$template}";
        }
        if(!is_null($product))
        {
            $params.="&product={$product}";
        }
        if(!is_null($webmaster))
        {
            $params.="&webmaster={$webmaster}";
        }
        if(!is_null($domain))
        {
            $params.="&domain={$domain}";
        }
        $q = self::$host.'/'.self::$apiPath.'?mode=summary&'.$params;
        return self::query($q);
    }
    public static function formsWebmasters($product, $template, $from, $browser,$skip = 0)
    {
        $params ="from={$from}&product={$product}&name={$template}&browser={$browser}&skip={$skip}"; 
        $q = self::$host.'/'.self::$apiPath.'?mode=template&'.$params;
        //var_dump($q);
        return self::query($q);
    }
    public static function formsFields($product, $template, $browser, $from, $till)
    {
        $params ="product={$product}&name={$template}&from={$from}&till={$till}&browser={$browser}"; 
        $q = self::$host.'/'.self::$apiPath.'?mode=fields&'.$params;
        return self::query($q);
    }
}
