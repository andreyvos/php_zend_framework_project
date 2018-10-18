<?php

class GetContent
{
    public $contentName;
    public $referringUrl;
    public $contentUrl='https://system.t3leads.com/system/centralcontent.php';
    public $content = '';

    protected $paydayPages = array(
        'termsandconditions'    => 'http://dlserv.t3lead.com/privacy/index/product/termsandconditions?ref=',
        'privacypolicy'         => 'http://dlserv.t3lead.com/privacy/index/product/payday?ref=',
        'marketingpractices'    => 'http://dlserv.t3lead.com/privacy/index/product/us_payday_marketingpractices?ref=',
        'ukprivacypolicy'       => 'http://dlserv.t3lead.com/privacy/index/product/ukpaydaytruste?ref=',
        'ushowitworks'          => 'http://dlserv.t3lead.com/privacy/index/product/ushowitworks?ref=',
        'ukhowitworks'          => 'http://dlserv.t3lead.com/privacy/index/product/ukhowitworks?ref=',
        'faqus'                 => 'http://dlserv.t3lead.com/privacy/index/product/usfaq?ref=',
        'faquk'                 => 'http://dlserv.t3lead.com/privacy/index/product/faquk?ref=',

    );


    public function __construct($content, $refUrl)
    {
        $this->contentName = $content;
        $this->referringUrl = $refUrl;

        $this->fetchContent();
//        $this->replaceLinks();
    }

    public function fetchContent()
    {
        if (array_key_exists($this->contentName, $this->paydayPages)) {
            $url=$this->paydayPages[$this->contentName].$this->referringUrl;
            $this->content = file_get_contents($url);
        }

    }

    public function replaceLinks()
    {

        $existingLinks=array
        (
            'terms_and_conditions.html',
            'privacy_policy.html',
            'marketing_practices.html'

        );

        $updatedLinks=array
        (
            $this->contentUrl.'?url='.$this->referringUrl.'&con=termsandconditions',
            $this->contentUrl.'?url='.$this->referringUrl.'&con=privacypolicy',
            $this->contentUrl.'?url='.$this->referringUrl.'&con=marketingpractices'
        ,

        );

//        $existingLinks=array
//        (
//            '"terms_and_conditions.html"style="color:blue; text-decoration:none;"',
//            '"privacy_policy.html"style="color:blue; text-decoration:none;"',
//            '"marketing_practices.html"style="color:blue; text-decoration:none;"'
//
//        );

//        $updatedLinks=array
//        (
//            '"'.$this->paydayPages['termsandconditions'].$this->referringUrl.'"',
//            '"'.$this->paydayPages['privacypolicy'].$this->referringUrl.'"',
//            '"'.$this->paydayPages['marketingpractices'].$this->referringUrl.'"'
//        ,
//
//        );

        $this->content=str_replace($existingLinks, $updatedLinks, $this->content);




    }

    public function returnContent()
    {
        return $this->content;
    }


}