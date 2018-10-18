<?php

TableDescription::addTable('leads_visitors', array(
  'id',
  'webmaster_id',
  'lead_id',
  'referrer',
  'domain',
  'subaccount',
  'keyword',
  'visitor_datetime',
  'wm',
  'ref',
  'agn',
  't3',
  'ttl',
  'used_for_cache',
  'clicks_count',
  'lead_status',
  'is_sold',
  'clickid'
));

class T3Visitor extends DbSerializable {

    public $id;
    public $webmaster_id;
    public $lead_id;
    public $referrer;
    public $domain;
    public $subaccount;
    public $keyword;
    public $visitor_datetime;
    public $wm;
    public $ref;
    public $agn;
    public $t3;
    public $ttl;
    public $used_for_cache;
    public $clicks_count;
    public $lead_status;
    public $is_sold;
    public $clickid;
    
    public $keywordFromReferrer = "";

    public function  __construct() {

        if (!isset($this->className))$this->className = __CLASS__;

        parent::__construct();
        $this->tables = array('leads_visitors');

    }


    public function addNew(
        $webmaster_id,
        $lead_id,
        $referrer,
        $subaccount,
        $keyword,
        $clickid
        )
    {
        // Domain parsing...
        preg_match("/^(http:\/\/)?([^\/]+)/i", $referrer, $result);

        $domain = "";
        if (!empty($result[2]))
        {
            $domain = strtolower($result[2]);
        }        

        // Keyword parsing...
        //$this->keywordFromReferrer = "";
        $isSE = false;
        if (
          (strstr($domain, 'google.')) || (strstr($domain, 'yahoo.')) || (strstr($domain, 'bing.'))
         )
        {
            $isSE = true;
        }
        
        $pkeyword = "";
        
        if ($isSE)
        {
            preg_match("/q=([^#&]*)/i", $referrer."&", $pkeyword);

            if (!empty($pkeyword[1]))
            {
                if ($pkeyword[1] != "")
                {
                    $keyword = $pkeyword[1];
                }
            }

        }

        //vvv($keyword);
        /*
        if (!empty($pkeyword[1]))
        {
            $keyword = $pkeyword[1];
        }
        */


        $this->wm = 0;
        $this->ref = 0;
        $this->t3 = 0;
        $this->ttl = 0;

        $this->webmaster_id = $webmaster_id;
        $this->lead_id = $lead_id;
        $this->referrer = $referrer;
        $this->domain = $domain;
        $this->subaccount = $subaccount;
        $this->keyword = $keyword;
        $this->visitor_datetime = mySqlDateTimeFormat();
        $this->used_for_cache = 0;
        $this->is_sold = 0;
        $this->clickid = "0";
        if ( isset($clickid) )
        {
            $this->clickid = $clickid;
        }
        $this->insertIntoDatabase();
    }

}

