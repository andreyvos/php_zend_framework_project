<?php

class T3BuyerFilter_ConditionalBackListManager {

  protected static $_instance;
  public $database;

  protected function __construct(){
    $this->database = T3Db::api();
  }

  /** @return T3BuyerFilter_ConditionalBackListManager */
  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  function getFullTypeName($typeName){
    return "T3BuyerFilter_Condition_ConditionalBlackList_$typeName";
  }

  function getTableName($typeName){
    return "buyers_filters_bl_data_$typeName";
  }

  public function typeNameExists($typeName){
    return class_exists($this->getFullTypeName($typeName));
  }

  public function getFieldsCount($typeName){
    return call_user_method('getFieldsCount', $this->getFullTypeName($typeName));
  }

  public function getFields($typeName){
    return call_user_method('getFields', $this->getFullTypeName($typeName));
  }

  public function getLists($typeName){

    $data = $this->database->fetchAll('

      select * from buyers_filters_bl_lists
      where type_name = ?

    ', array($typeName));

    return $data;

  }

  public function getListsAvailableKeys($typeName){
    $data = $this->getLists($typeName);
    $result = array();
    foreach($data as $v){

      //CachNet USA Black (34031 counts, Last Update: 2010-11-16)

      $zd = new Zend_Date($v['modification_datetime'], MYSQL_DATETIME_FORMAT_ZEND);
      $date = $zd->toString(MYSQL_DATE_FORMAT_ZEND);

      //$result[$v['id']] = "{$v['title']} <div style='display:inline;color:gray'>({$v['records_count']} counts, Last Update: $date)</div>";
      $result[$v['id']] = "{$v['title']} <span style='color:gray'>({$v['records_count']} counts, Last Update: $date)</span>";

    }
    return $result;
  }

  public function getListData($typeName, $listId){

    $header = $this->database->fetchRow('

      select * from buyers_filters_bl_lists where id = ?

    ', array($listId));

    if(empty($header))
      return false;


    $dataTableName = $this->getTableName($typeName);
    $content = $this->database->fetchAll("

      select * from $dataTableName where list_id = ?

    ", array($listId));

    $header['content'] = $content;

    return $header;

  }

  public function createList($data){

    $this->database->query('

      insert buyers_filters_bl_lists set
        type_name = ?,
        title = ?,
        records_count = ?,
        modification_datetime = ?

    ', array($data['type_name'] , $data['title'], count($data['content']), mySqlDateTimeFormat()));

    $data['id'] = $this->database->lastInsertId();
    $tableName = $this->getTableName($data['type_name']);

    foreach($data['content'] as $k => $v){
      $data['content'][$k] = array_merge(array('list_id' => $data['id']), $v);
    }

    
    $fields = array_merge(array('list_id'), $this->getFields($data['type_name']));

    $last = insertMultiple($this->database, $tableName, $fields, $data['content']);

  }

  public function saveList($data){


    try{

      $this->database->beginTransaction();

      $this->database->query('

        update buyers_filters_bl_lists set
          title = ?,
          records_count = ?,
          modification_datetime = ?
        where
          id = ?

      ', array($data['title'], count($data['content']), mySqlDateTimeFormat(), $data['id']));

      $tableName = $this->getTableName($data['type_name']);
      $this->database->delete($tableName, 'list_id = ' . $this->database->quote($data['id']));

      foreach($data['content'] as $k => $v){
        $data['content'][$k] = array_merge(array('list_id' => $data['id']), $v);
      }

      $fields = array_merge(array('list_id'), $this->getFields($data['type_name']));


      $last = insertMultiple($this->database, $tableName, $fields, $data['content']);

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      $success = false;
    }




  }


  public function deleteList($id, $typeName){

    try{

      $this->database->beginTransaction();

      $this->database->query('

        delete from buyers_filters_bl_lists
        where id = ?

      ', array($id));

      $tableName = $this->getTableName($typeName);
      $this->database->delete($tableName, 'list_id = ' . $this->database->quote($data['id']));

      $this->database->commit();

    }catch(Exception $e){
      $this->database->rollBack();
      $success = false;
    }

  }

  public function updateListsTable($typeName){

    // частный случай, так как пришлось подстраиваться под санину систему
    if($typeName == 'EmailSsn'){

      $this->database->query('

        UPDATE buyers_filters_bl_lists,
        (SELECT buyer, COUNT(*) AS c FROM dnpl_new GROUP BY buyer) tmp
        SET buyers_filters_bl_lists.records_count = tmp.c
        WHERE buyers_filters_bl_lists.type_name="EmailSsn"
        AND buyers_filters_bl_lists.id = tmp.buyer

      ');

      return;

    }

    

  }


  private $getListTitleFastCache;
  public function getListDataFast($listId){
    if($this->getListTitleFastCache === null){
      $data = $this->database->fetchAll('
        select * from buyers_filters_bl_lists
      ');
      $this->getListTitleFastCache = array();
      foreach($data as $v){
        $this->getListTitleFastCache[$v['id']] = $v;
      }
    }
    return $this->getListTitleFastCache[$listId];
  }
  


}

T3BuyerFilter_ConditionalBackListManager::getInstance();






