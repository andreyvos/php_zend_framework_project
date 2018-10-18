<?php

require_once 'TableDescription/Table.php';

class TableDescription{

  public static $tables = array();
  public static $classes = array();

  private static function parseTable($array, $table = null){

    $output = array();
    foreach($array as $v){

      if(is_array($v)){

        $curIntKey = 0;

        if(isset($v['field']))
          $field = &$v['field'];
        elseif(isset($v[$curIntKey]))
          $field = &$v[$curIntKey++];
        else
          trigger_error('TableDescription : field not found', E_USER_WARNING);

        $validatorsFound = false;

        if(isset($v['validators']))
          $validators = &$v['validators'];
        elseif(isset($v[$curIntKey]))
          $validators = &$v[$curIntKey++];
        else
          $validators = array();

        $validatorsChain = new T3Validator_Chain();
        foreach($validators as &$validator){
          $validatorParams = ExpressValidator::parseValidator($validator);
          $className = ExpressValidator::getClassName($validatorParams['validatorName']);
          if(method_exists($className, T3Validator_Database::defDbInitMethod) && callStaticMethod($className, T3Validator_Database::defDbInitMethod))
            $validatorParams['validatorOptions'] = array($table, $field);
          $validatorObject = ExpressValidator::createValidator($validatorParams);
          $validatorObject->setKeyName($field);
          $validatorsChain->addValidator($validatorObject);
        }

        $output[$field] = $validatorsChain;

      }else
        $output[$v] = null;

    }

    return $output;

  }

  public static function addTable($tableName, $fields, $idFieldName = 'id'){

    if(isset(self::$tables[$tableName])){
      throw new Exception("Table $tableName already exists");
      return;
    }

    $table = new TableDescription_Table($tableName);
    $table->validators = self::parseTable($fields, $tableName);
    $table->fields = array_keys($table->validators);
    $table->class = isset(self::$classes[$tableName]) ? self::$classes[$tableName] : null;
    $table->idFieldName = $idFieldName;

    self::$tables[$table->name] = $table;

    ExpressValidator::cache($tableName, $table->validators);

  }

  protected static function autoLoadTable($tableName){
    if(!isset(TableDescription::$tables[$tableName]))
      spl_autoload_call(TableDescription::$classes[$tableName]);
  }

  public static function get($tableName){
    TableDescription::autoLoadTable($tableName);
    return TableDescription::$tables[$tableName];
  }

  public static function addClasses(array $classes){
    TableDescription::$classes = array_merge(TableDescription::$classes, $classes);
  }

}

TableDescription::addClasses(array(
  'buyers_filters_conditions'           => 'T3BuyerFilter_Condition',
  'buyers_channels'                     => 'T3BuyerChannel',     
  'leads_data'                          => 'T3Lead',
  'leads_data_payday'                   => 'T3LeadBody_PaydayLoan',
  'users'                               => 'T3User',
  'channels'                            => 'T3Channel_Abstract',
  'channels_js_forms'                   => 'T3Channel_JsForm',
  'channels_post'                       => 'T3Channel_Post',
  'buyers_invoices'                     => 'T3Invoice',
  'buyers_leads_sellings'               => 'T3Leads_Sellings',
  'buyers_leads_movements'              => 'T3Leads_Movement', 
));

