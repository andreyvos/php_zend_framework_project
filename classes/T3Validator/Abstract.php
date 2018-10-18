<?php

abstract class T3Validator_Abstract{

    const IS_NULL = 'isNull';
    const IS_EMPTY = 'isEmpty';
    const INVALID = 'invalid';
    
    const CHANGE_OFFER = 'changeOffer';
    
    const VAR_TITLE = 'title';
    const VAR_VALUE = 'value';

    const TEXTDET_DETAILED = 0;
    const TEXTDET_USER = 1;

    const INVALID_TEXT = '%title% has been entered incorrectly';

    const NOT_EXISTENT_MES = 0xFFFFFF;

    protected $canBeNull = true;
    protected $canBeEmptyString = true;
    protected $keyName;
    public $title;

    public $report;

    protected $value;

    protected $selfReliant = true;

    protected $insertText = true;

    protected $textDetLevel = 0;

    public function  __construct($keyName = null) {
      $this->report = new Report();
      $this->setKeyName($keyName);
      $this->initialize();
    }

    public function initialize(){}

    public function setKeyName($keyName){
      if($keyName===null){
        $this->keyName = 'validator';
      }else
        $this->keyName = $keyName;
    }

    public function getKeyName(){
      return $this->keyName;
    }

    public function setTitle($title){
      $this->title = $title;
    }

    public function getTitle(){
      return $this->title;
    }

    public function setInsertText($insertText){
      $this->insertText = $insertText;
    }

    public function getInsertText(){
      return $this->insertText;
    }

    // public function defInit(){}

    public function isValid($value, $keyName = null){

      $this->value = $value;
      $this->report->clear();

      if($keyName !== null)
        $this->keyName = $keyName;

      if($value===null){
        if($this->canBeNull)
          $this->ok(Report_Codes::OK);
        else
          $this->error(self::IS_NULL);
        return $this->report;
      }

      if($value === ''){
        if($this->canBeEmptyString)
          return $this->report;
        else
          $this->error(self::IS_EMPTY);
        return $this->report;
      }

      $this->isValidCore($value);
      if($this->selfReliant && $this->report->isEmpty())
        $this->ok();
        
      return $this->report;

    }

    public static function needsDatabaseInitialization(){
      return false;
    }

    public function isValidCore($value){}

    public function ok($code = Report_Codes::OK){
      $this->report->ok($this->keyName, $code, $this->insertText ? $this->getCodeText($code) : null)->setData('value', $this->value);
    }

    public function notice($code = Report_Codes::NOTICE){
      $this->report->notice($this->keyName, $code, $this->insertText ? $this->getCodeText($code) : null)->setData('value', $this->value);
    }

    public function warning($code = Report_Codes::WARNING){
      $this->report->warning($this->keyName, $code, $this->insertText ? $this->getCodeText($code) : null)->setData('value', $this->value);
    }

    public function error($code = Report_Codes::ERROR){
      $this->report->error($this->keyName, $code, $this->insertText ? $this->getCodeText($code) : null)->setData('value', $this->value);
    }

    private function messageReplaceCallback($m){
      return $this->getMesVar($m[1]);
    }

    protected function getMesVar($code){
      switch($code){
        case self::VAR_TITLE :  return $this->title !== null ? $this->title : $this->keyName;
        case self::VAR_VALUE :  return $this->value;
        default:                return self::NOT_EXISTENT_MES;
      }
    }

    protected function getMessage($code){
      switch($code){
        case Report_Codes::OK : return 'ok';
        case self::IS_EMPTY :   return 'Enter the %title% please';
        case self::IS_NULL  :   return 'Enter the %title% please';
        case self::INVALID :    return self::INVALID_TEXT;
        default:                return self::NOT_EXISTENT_MES;  
      }
    }

    public function getCodeText($code){
      $mes = $this->getMessage($code);
      if($mes === self::NOT_EXISTENT_MES)
        $mes = self::INVALID_TEXT;
      return ucfirst(preg_replace_callback('/%([^%]+)%/i', array($this, 'messageReplaceCallback'), $mes));
    }
}
