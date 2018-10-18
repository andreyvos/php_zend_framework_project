<?php

/*
$data = array(

  array(

    'value' => ... ,
    'validators' => array(

      array(

        'name' => ... ,

        'options' => ...

      ),

      ...

    )

  ),

  ...

);
*/

class ExpressValidator{

  const FieldNotExists = 'notExists';
  const FieldExists = 'exists';

  const CHAIN_VALIDATOR = 'T3Validator_Chain';

  const DefInitFunc = 'defInit';

  private static $verifyingData;
  private static $verifyingTable;

  private static $prefixes = array('');

  private static $report;

  public static $cache = array();

  private static $shortClassNames = array();

  public static function nowVerifying(&$data, $table = null){
    self::$verifyingData = &$data;
    self::$verifyingTable = $table;
  }

  public static function getNowVerifying($key){
    if(array_key_exists($key, self::$verifyingData))
      return self::$verifyingData[$key];
    return null;
  }

  public static function addPrefix($prefix){
    if(!in_array($prefix, self::$prefixes))
      self::$prefixes[] = $prefix;
  }

  public static function removePrefix($prefix){
    $i = array_search($prefix, self::$prefixes);
    if($i!==false)
      unset(self::$prefixes[$i]);
  }

  public static function isError(){
    return self::$report->isError(); 
  }

  public static function getReportCopy(){
    return clone self::$report;
  }

  public static function getReport(){
    return self::$report;
  }

  public static function initReport(){
    //$result = array(self::ResultSignature => true);
    if(self::$report === null)
      self::$report = new Report();
    self::$report->clear();
  }

  public static function cache($tableName, $validators){

    self::$cache[$tableName] = array();
    $ar = &self::$cache[$tableName];
    foreach($validators as $field => &$validatorChain){

      if(is_array($validatorChain)){
        $chainValidatorClass = self::CHAIN_VALIDATOR;
        $object = new $chainValidatorClass();
        foreach($validatorChain as $validator){
          $object->addValidator(self::createValidator(self::parseValidator($validator)));
        }
      }elseif(is_null($validatorChain) || is_a($validatorChain, self::CHAIN_VALIDATOR))
        $object = $validatorChain;
      else
        trigger_error('Express Validator : Invalid parameter passed as validator chain', E_USER_WARNING);

      $ar[$field] = $object;
    }

  }

  private static function errorMessage_FieldNotExist(&$result, $table, $fieldName){
    $result->error($fieldName, self::FieldNotExists);
  }

  public static function fieldNamesValid($table, &$data){

    if(!isset(self::$cache[$table])){
      trigger_error("Table $table is not cached", E_USER_WARNING);
      return false;
    }
    self::initReport();

    foreach($data as $fieldName => $fieldValue)
      if(!array_key_exists($fieldName, self::$cache[$table])){
        self::errorMessage_FieldNotExist(self::$report, $table, $fieldName);
        continue;
      }

    return self::$report;
    
  }

  public static function allFieldsAreSet($table, &$data, $andIdField = true, $idFieldName = 'id'){
    self::initReport();
    foreach(self::$cache[$table] as $k => $v)
      if(array_key_exists($k, $data) || !$andIdField && $k==$idFieldName)
        self::$report->ok($k, self::FieldExists);
      else{
        self::$report->error($k, self::FieldNotExists);
      }
    return self::$report;
  }

  public static function validateTableData($table, &$data, $id = 0xFFFFFF, $idFieldName = 'id', $verifyIdUniq = true){

    self::nowVerifying($data, $table);
    self::fieldNamesValid($table, $data);

    if(!self::$report->isNoError())
      return self::$report;

    self::initReport();

    foreach($data as $fieldName => $fieldValue){
      if($id != 0xFFFFFF && $fieldName == $idFieldName)
        continue;
      self::processAndAppendResult(self::$report, $fieldName, $fieldValue, self::$cache[$table][$fieldName]);
    }

    if($id !== 0xFFFFFF)
      if(!array_key_exists($idFieldName, self::$cache[$table]))
        self::errorMessage_FieldNotExist(self::$report, $table, $idFieldName);
      else
        self::processAndAppendResult(self::$report, $idFieldName, $id, self::$cache[$table][$idFieldName]);

    return self::$report;

  }

  /*private static function processResult($value, $validator){
    if(!is_null($validator) && !$validator->isValid($value))
      return array(
        'value' => $value,
        'valid' => false,
        'messages' => $validator->getMessages(),
      );
    else
      return array(
        'value' => $value,
        'valid' => true,
      );    
  }*/

  private static function processAndAppendResult($report, $key, $value, $validator){

      if(!is_null($validator)){
        $report->merge($validator->isValid($value, $key));
      }
    /*$response = self::processResult($value, $validator);
    if(!$response['valid']){
      $resultArray[$key] = $response;
      return false;
    }else{
      //$resultArray[$key] = $response;
      return true;
    } */
  }

  public static function getClassName($shortClassName){

    if(isset(self::$shortClassNames[$shortClassName]))
      return self::$shortClassNames[$shortClassName];

    foreach(self::$prefixes as $prefix){
      $fullClassName = $prefix . $shortClassName;
      if(@class_exists($fullClassName)){
        self::$shortClassNames[$shortClassName] = $fullClassName;
        self::$shortClassNames[$fullClassName] = $fullClassName;
        return $fullClassName;
      }
    }

    trigger_error("Express Validator : class $shortClassName not found.");
    return false;

  }

  public static function parseValidator(&$validator){

    if(!is_array($validator)){

      $validatorName = $validator;
      $validatorOptions = null;

    }else{

      $validatorNameKey = null;
      if(isset($validator['name'])){
        $validatorName = &$validator['name'];
        $validatorNameKey = 'name';
      }elseif(isset($validator[0])){
        $validatorName = &$validator[0];
        $validatorNameKey = 0;
      }else{
        trigger_error("ExpressValidator : validator name isn't set", E_USER_WARNING);
        continue;
      }

      if(isset($validator['options']))
        $validatorOptions = &$validator['options'];
      else{
        $validatorOptions = array();
        foreach($validator as $k => $v)
          if($k!=$validatorNameKey)
            $validatorOptions[$k] = &$validator[$k];
        if(!count($validatorOptions))
          $validatorOptions = null;
      }

    }

    return array(
      'validatorName' => $validatorName,
      'validatorOptions' => $validatorOptions,
    );

  }

  public static function createValidator($shortClass, $options = null){

    if(is_array($shortClass)){
      $options = $shortClass['validatorOptions'];
      $shortClass = $shortClass['validatorName'];
    }

    $shortClass = self::getClassName($shortClass);

    if($shortClass!==false){

      $object = new $shortClass();
      if($options==null) $options = array();
      if(method_exists($object,self::DefInitFunc))
        call_user_func_array(array($object, self::DefInitFunc), $options);

      $object->setInsertText(false);

      return $object;

    }else
      trigger_error("ExpressValidator : validator class \"" . $shortClass . "\" doesn't exists", E_USER_WARNING);

  }

  private static function processElement(&$element){

      $validatorsAreExpanded = false;

      if(isset($element['value']))
        $value = &$element['value'];
      elseif(isset($element[0]))
        $value = &$element[0];
      else{
        trigger_error("ExpressValidator : value isn't set", E_USER_WARNING);
        return;
      }

      if(isset($element['validators']))
        $validators = &$element['validators'];
      elseif(isset($element[1])){
        if(is_array($element[1])){
          $validators = &$element[1];
        }else{
          $validatorsAreExpanded = true;
        }
      }else{
        trigger_error("ExpressValidator : validators aren't set", E_USER_WARNING);
        return;
      }

      $chainValidatorClass = self::CHAIN_VALIDATOR;
      $validatorObject = new $chainValidatorClass();

      if($validatorsAreExpanded){

        $first = true;

        foreach($element as $validator){

          if($first){
            $first = false;
            continue;
          }

          $validatorObject->addValidator(self::createValidator(self::parseValidator($validator)));

        }
        

      }else{

        foreach($validators as $validator){

          $validatorObject->addValidator(self::createValidator(self::parseValidator($validator)));

        }

      }

      return array(
        'value' => $value,
        'validator' => $validatorObject,
      );

  }

  public static function validate($data, $validatorName = null, $validatorOptions = null){

    self::initReport();

    if(!is_null($validatorName)){

      $validatorName = self::getClassName($validatorName);
      $validatorObject = new $validatorName($validatorOptions);

      if(!is_array($data)){
        self::processAndAppendResult(self::$report, 0, $data, $validatorObject);
      }else
        foreach($data as $k => $v)
          self::processAndAppendResult(self::$report, $k, $v, $validatorObject);

    }else{

      $firstElement = reset($data);
      if(is_array($firstElement)){
        foreach($data as $k => $element){
          $processed = self::processElement($element);
          self::processAndAppendResult(self::$report, $k, $processed['value'], $processed['validator']);
        }
      }else{
        $processed = self::processElement($data);
        self::processAndAppendResult(self::$report, 0, $processed['value'], $processed['validator']);
      }

    }

    return self::$report;


  }

}

ExpressValidator::addPrefix('T3Validator_');



