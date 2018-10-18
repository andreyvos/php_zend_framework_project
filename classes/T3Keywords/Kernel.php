<?php

class T3Keywords_Kernel
{

    public static function CronUpdateCache()
    {
        $dbConnect = T3System::getConnect();

        $res = T3System::getConnectCache()->fetchOne("select max(date) from keywords_summary");

        $maxd = strtotime($res); // последняя дата
        $today = strtotime(date ("d-m-Y")) - (60*60*24); // вчера
        $d = ($today - $maxd) / (60*60*24); //
        if ($d>=1)
        {
            $sdate = date("Y-m-d", $maxd+(60*60*24));
            $edate = date("Y-m-d", $maxd+(60*60*24)*$d);


            $_q = "
                select
                leads_data.product as product,
                leads_data.channel_id as channel_id,
                leads_data.agentID as agentID,
                DATE(leads_visitors.visitor_datetime) as dvisitor_datetime,
                leads_visitors.webmaster_id as lvwebmaster_id,
                leads_visitors.keyword as lvkeyword,

                sum(leads_visitors.wm) as swm,
                sum(leads_visitors.ref) as sref,
                sum(leads_visitors.agn) as sagn,
                sum(leads_visitors.t3) as st3,
                sum(leads_visitors.ttl) as sttl,
                sum(leads_visitors.clicks_count) as sclicks_count,
                count(leads_visitors.id) as cid,
                sum(leads_visitors.is_sold) as sis_sold

                from leads_visitors left join leads_data on leads_visitors.lead_id = leads_data.id
                where ((leads_visitors.keyword!='') and (leads_visitors.visitor_datetime>='$sdate') and (leads_visitors.visitor_datetime<='$edate'))
                group by leads_visitors.webmaster_id, DATE(leads_visitors.visitor_datetime), leads_visitors.keyword, product, channel_id, agentID
            ";
            
            //echo $_q;

            $rows = $dbConnect->fetchAll($_q);

            foreach ($rows as $r)
            {

                $p = array(
                        "webmaster_id" => $r['lvwebmaster_id'],
                        "date" => $r['dvisitor_datetime'],
                        "keyword" => $r['lvkeyword'],
                        "product" => $r['product'],
                        "channelid" => $r['channel_id'],
                        "agentid" => $r['agentID']);

                $uParams = array(
                        "clicks_count"=>$r['sclicks_count'],
                        "leads_count"=>$r['cid'],
                        "sold_leads_count"=>$r['sis_sold'],
                        "wm"=>$r['swm'],
                        "ref"=>$r['sref'],
                        "agn"=>$r['sagn'],
                        "t3"=>$r['st3'],
                        "ttl"=>$r['sttl']);

                T3Keywords_Kernel::UpdateCache($p, $uParams);

            }


        }
    }

    public static function UpdateCache($sortParams, $updateParams)
    {
        //'leads_per_clicks',
        //'sold_leads_per_all_leads',

        $date = $sortParams['date'];
        $webmaster_id = $sortParams['webmaster_id'];
        $keyword = $sortParams['keyword'];

        $product = $sortParams['product'];
        $channelid = $sortParams['channelid'];
        $agentid = $sortParams['agentid'];

        $clicks_count = $updateParams['clicks_count'];
        $leads_count = $updateParams['leads_count'];
        $sold_leads_count = $updateParams['sold_leads_count'];
        $wm = $updateParams['wm'];
        $ref = $updateParams['ref'];
        $agn = $updateParams['agn'];
        $t3 = $updateParams['t3'];
        $ttl = $updateParams['ttl'];

        // select
        $res = T3System::getConnectCache()->fetchOne(
                "select * from keywords_summary where ".
                "(webmaster_id=?) and (date=?) and (keyword=?) and (product=?) and (channelid=?) and (agentid=?)",
            array($webmaster_id, $date, $keyword, $product, $channelid, $agentid)
        );

        if ($res)
        {
            $q = "update keywords_summary set "."
                  clicks_count=clicks_count+'".$clicks_count."',
                  leads_count=leads_count+'".$leads_count."',
                  ".($sold_leads_count!=NULL?("sold_leads_count=sold_leads_count+'".$sold_leads_count."',"):"")."                  
                  wm=wm+'".$wm."',
                  ref=ref+'".$ref."',
                  agn=agn+'".$agn."',
                  t3=t3+'".$t3."',
                  ttl=ttl+'".$ttl."'
                  "." where (webmaster_id='".$webmaster_id."') and (date='".$date."') and (keyword='".addslashes($keyword)."') and (product='".$product."') and (channelid='".$channelid."') and (agentid='".$agentid."')";

            // Update
            T3System::getConnectCache()->query($q);


        }
        else
        {
            // Insert
            T3System::getConnectCache()->insert("keywords_summary",
                array(
                        "webmaster_id" => $webmaster_id,
                        "date" => $date,
                        "keyword" => $keyword,
                        "product" => $product,
                        "channelid" => $channelid,
                        "agentid" => $agentid,

                        "clicks_count"=>($clicks_count==NULL?'0':$clicks_count),
                        "leads_count"=>$leads_count,
                        "sold_leads_count"=>$sold_leads_count,
                        "wm"=>$wm,
                        "ref"=>$ref,
                        "agn"=>$agn,
                        "t3"=>$t3,
                        "ttl"=>$ttl
                )
            );
        }
    }

    public static function GetList(
        $startDate,
        $endDate,
        $webmaster_id
    )
    {
        $res = "";

        $res = T3System::getConnectCache()->fetchAll(
            "select * from keywords_summary where ".
            "((webmaster_id=?) and ((date between ? and ?) OR (date between ? and ?)))",
            array($webmaster_id, $startDate, $endDate, $endDate, $startDate)
        );

        $_startDate = strtotime($startDate);
        $daysRangeCount = ((strtotime($endDate) - $_startDate)/(60*60*24));
        $result = "";

        for ($i = 0; $i <= $daysRangeCount; $i++)
        {
            $result[$i] = Array(
                'date' => date("Y-m-d", $_startDate + $i * (60 * 60 * 24)),
                "webmaster_id" => $webmaster_id,
                "keyword" => '',
                "clicks_count"=> '',
                "leads_count"=> '',
                "sold_leads_count"=> '',
                "wm"=> '',
                "ref"=> '',
                "agn"=> '',
                "t3"=> '',
                "ttl"=> ''

            );
        }

        foreach ($res as $var => $value)
        {
            for ($i = 0; $i <= $daysRangeCount; $i++)
            {
                if ($res[$var]['date'] == $result[$i]['date'])
                {
                    $result[$i] = $res[$var];
                }
            }
        }

        $res = $result;

        return $res;
    }

}
