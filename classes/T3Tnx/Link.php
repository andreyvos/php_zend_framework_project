<?php

class T3Tnx_Link {
    public $id;
    public $url;
    public $urlIncludeParams;
    public $type;
    
    
    public function getRedirectedURL(){
        if($this->urlIncludeParams == '')$this->urlIncludeParams = $this->url;
        return "https://f.t3leads.com/system/lead_channel/tnx_click.php?id=" . urlencode((int)$this->id) . "&url=" . urlencode($this->urlIncludeParams);
    }    
}