<?php


class T3Banners_PartialUrl {

  public $part1;
  public $part2;
  public $partial;
  public $is_t3cms;
  public $accepts_referrers;
  public $url;

  public function  __construct($data = null) {

    if($data === null)
      return;

    $this->fromData($data);

  }

  public function fromData($data){

    $this->is_t3cms = (bool)($data['is_t3cms']);
    $this->accepts_referrers = (bool)($data['accepts_referrers']);
    $this->url = $data['url'];

    if($this->accepts_referrers && $this->is_t3cms){

      if(preg_match('/^(https?:\/\/[^\/]+\/)([^\/]+)(.*)$/i', $this->url, $out)){
        $this->partial = true;
        $this->part1 = $out[1];
        $this->part2 = $out[3];
      }else{
        $this->partial = false;
        $this->part1 = "";
        $this->part2 = "";
      }

    }else{
      $this->partial = false;
      $this->part1 = "";
      $this->part2 = "";
    }

  }

  public function getUrl($referrer){

    if(!$this->accepts_referrers)
      return $this->url;

    if($this->partial)
      return $this->part1 . $referrer . $this->part2;

    //$url = new Url($this->url);
    //$url->query->setParameter('cid', $referrer);

    return $url->toString();

  }

  public function toArray(){
    return array(
      'part1' => $this->part1,
      'part2' => $this->part2,
      'partial' => $this->partial,
      'is_t3cms' => $this->is_t3cms,
      'accepts_referrers' => $this->accepts_referrers,
      'url' => $this->url,
    );
  }

  public function toString(){
    return serialize($this->toArray());
  }

  public function equals($a){
    if($this->partial && $a->partial){
      return $this->part1 == $a->part1 && $this->part2 == $a->part2;
    }else if(!$this->partial && !$a->partial){
      return $this->url == $a->url;
    }else
      return false;
  }

  public function fromString($s){
    $ar = unserialize($s);
    if(!is_array($ar))
      return false;

    if(
      !isset($ar['part1']) || !isset($ar['part2']) || !isset($ar['partial']) ||
      !isset($ar['is_t3cms']) || !isset($ar['accepts_referrers']) || !isset($ar['url']) ||
      !is_string($ar['part1']) || !is_string($ar['part2']) || !is_bool($ar['partial']) ||
      !is_bool($ar['is_t3cms']) || !is_bool($ar['accepts_referrers']) || !is_string($ar['url'])
    )
    return false;
  
    $this->part1 = $ar['part1'];
    $this->part2 = $ar['part2'];
    $this->partial = $ar['partial'];
    $this->is_t3cms = $ar['is_t3cms'];
    $this->accepts_referrers = $ar['accepts_referrers'];
    $this->url = $ar['url'];
    return true;
  }

}


