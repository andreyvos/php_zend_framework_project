<?php



class DbSerializable {

    /**
    * ссылка на объект T3System
    * 
    * @var T3System
    */
    public $system;
    
    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    public $database;

    public $existsInDatabase = false;

    public $tables = array(); // имена таблиц (ключи массива TableDescription::$table)
    protected $setIdMakesArrayChanging = false;
    protected $readNewIdAfterInserting; // поле типа boolean. Если true, то insertIntoDatabase присваивает тот id, который выдала таблица для новой записи

    protected $className;

    public function  __construct() {

        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
        $this->readNewIdAfterInserting = true;

    }

    public function replaceIntoDatabase(){
      $data = $this->toArray();
      $ar = array();
      $table = reset($this->tables);
      foreach(TableDescription::$tables[$table]->saveExceptions as $v){
        unset($data[$v]);
      }      
      foreach($data as $k => $v){
        $identifier = $this->database->quoteIdentifier($k);
        $value = $v === null ? 'NULL' : $this->database->quote($v);
        $ar[] = "$identifier = $value";
      }
      $str = implode(', ', $ar);
      $table = reset($this->tables);
      $result = $this->database->query("
        REPLACE INTO `$table`
        SET $str
      ");//->execute();

      if($this->id === null && $this->readNewIdAfterInserting)
        if($this->readNewIdAfterInserting === true)
          $this->setId($this->database->lastInsertId());
        elseif(is_string($this->readNewIdAfterInserting)) 
          $this->setId($this->database->fetchOne("select @{$this->readNewIdAfterInserting}"));
        
      return $result;

    }

    public function morphIntoActualClass(&$array) {
        return $this;
    }

    // Метод заполнения объекта из массива. Ключи в массиве - названия полей таблицы БД
    public function fromArray($array) {
      $this->setParams($array);
    }

    /**
    * @param DbSerializable
    */
    public function setParams($array){

      foreach($array as $k => $v)
        $this->$k = $v;
        
      return $this;
       
    }

    // Метод, переносящий данные объекта в массив. Ключи массива - названия
    // полей таблицы БД
    public function toArray($tables = null) {
      return $this->getParams($tables);
    }

    public function getParams($tables = null){

        if($tables === null)
          $tables = & $this->tables;
        elseif(!is_array($tables))
          $tables = array($tables);

        $result = array();
        foreach($tables as $table) {
            $ar = TableDescription::get($table)->fields;
            foreach($ar as $v)
              $result[$v] = $this->$v;
        }

        return $result;
    }
    
    public function getParam($varible, $default = null){
        if(isset($this->$varible)){
            return $this->$varible;
        }
        return $default;   
    }

    public function getProperty($name){
      $method = "get_$name";
      if(method_exists($this, $method))
        return $this->$method();
      return $this->$name;
    }

    public function setProperty($name, $value){
      $method = "set_$name";
      if(method_exists($this, $method))
        return $this->$method($value);
      return $this->$name = $value;
    }

    // Служебный метод. Смотрите статисческий метод createFromArray
    protected static function createFromArrayByClass(&$array, $class) {
        $object = new $class();
        $object->fromArray($array);
        return $object;
    }

    public static function saveToDatabaseStatic($tables, $id, $data, $turnOffValidation = false, $idFieldName = 'id', $database = null) {

        if(!is_array($tables))
          $tables = array($tables);
        if(is_null($database))
          $database = T3Db::api();
          
        $firstTable = reset($tables);

        if(!$turnOffValidation && ExpressValidator::validateTableData($firstTable, $data, $id, $idFieldName)->isError())
          return ExpressValidator::getReport();

        foreach(TableDescription::$tables[$firstTable]->saveExceptions as $v){
          unset($data[$v]);
        }

        return $database->update($firstTable, $data, "$idFieldName = " . $database->quote($id));

    }

    public static function fromDatabaseStatic($tables, $conditions, $database = null, $liteLoadCols = array()) {
        if (!is_array($tables))
            $tables = array($tables);

        if (is_null($database))
            $database = T3Db::api();

        $firstTable = reset($tables);

        $idFieldName = TableDescription::get($firstTable)->idFieldName;

        if (!is_array($conditions))
            $conditions = array($idFieldName => $conditions);

        foreach($tables as $table)
          if(ExpressValidator::fieldNamesValid($table, $conditions)->isError())
            return ExpressValidator::getReport();
        
        $joins = "";
        if(count($tables) > 1){
            $joins = " $firstTable ";
            
            foreach($tables as $v) {
              if ($v == $firstTable)
                continue;
              $idField2 = TableDescription::get($v)->idFieldName;
              $joins.= " LEFT JOIN $v ON $firstTable.$idFieldName = $v.$idField2 ";
            }
        }

        $wheres = array();
        $values = array();
        foreach($conditions as $k => $v) {
            $wheres[] = "$k = ?";
            $values[] = $v;
        }
        
        $whereString = implode(' AND ', $wheres);

        $cols = '*';
        if(is_array($liteLoadCols) && count($liteLoadCols)){
            $cols = "`" . implode('`,`', $liteLoadCols) . "`";   
        } 
        
        $array = $database->fetchRow("SELECT {$cols} FROM {$firstTable}{$joins} WHERE {$whereString} LIMIT 1", $values);

        return $array;
    }

    /**
    * Метод заполнения данного объекта информацией из БД
    * 
    * @param mixed $conditions
    * @param mixed $fillObjects
    * @param mixed $liteLoadCols урезанная загрузка, будут загружаться только эти поля
    * @return Report
    */
    public function fromDatabase($conditions, $fillObjects = array(), $liteLoadCols = array()) {
        $array = self::fromDatabaseStatic($this->tables, $conditions, $this->database, $liteLoadCols);
        
        if(ExpressValidator::getReport()->isError())
          return ExpressValidator::getReport();

        if ($array === false)
          return false;

        $this->fromArray($array);
        foreach($fillObjects as $k => $v)
          $this->$v->fromArray($array);

        $this->existsInDatabase = true;

        return true;
    }

    // Служебный метод. Смотрите статисческий метод createFromDatabase
    protected static function createFromDatabaseByClass($conditions, $class) {

        $object = new $class();
        if ($object->fromDatabase($conditions)===false)
            return false;
        else
            return $object;

    }

    // Метод, производящий update базы
    public function saveToDatabase() {

        return self::saveToDatabaseStatic(
          $this->tables,
          $this->id,
          $this->toArray(),
          false,
          TableDescription::get(reset($this->tables))->idFieldName,
          $this->database
        );

        /*$firstTable = reset($this->tables);
        $idFieldName = TableDescription::get($firstTable)->idFieldName;

        $data = $this->toArray();

        if(ExpressValidator::validateTableData($firstTable, $data, $this->id)->isError())
          return ExpressValidator::getReport();        

        $affected = $this->database->update($firstTable, $data, "{$idFieldName} = " . $this->database->quote($this->id));

        return $affected!=0;*/

    }

    public function setId($newId)
    {
        $this->id = $newId;
    }

    /* Метод, производящий insert в базу. После выполнения запроса $this->id меняется на
     * id новой записи
     */
    public function insertIntoDatabase($printErrors = true) {

        try {

            $transaction = count($this->tables)>1;

            if ($transaction)
                $this->database->beginTransaction();

            $firstTable = reset($this->tables);
            $first = true;

            foreach($this->tables as $table) {

                $data = $this->toArray($table);

                if ($first && $this->readNewIdAfterInserting)
                  unset($data[TableDescription::get($firstTable)->idFieldName]);

                if(ExpressValidator::validateTableData($table, $data)->isError())
                  return ExpressValidator::getReport();           

                $this->database->insert($table, $data);


                if ($first)
                  if($this->readNewIdAfterInserting === true){
                    $this->setId($this->database->lastInsertId());
                    
                    if ($this->setIdMakesArrayChanging)
                      $data = $this->toArray();
                  }elseif(is_string($this->readNewIdAfterInserting) && !empty($this->readNewIdAfterInserting)) {

                    $this->setId($this->database->fetchOne("select @{$this->readNewIdAfterInserting}"));
                    if ($this->setIdMakesArrayChanging)
                      $data = $this->toArray();
                  }

                $first = false;

            }

            if ($transaction)
                $this->database->commit();

        } catch (Exception $e) {

            if ($transaction)
                $this->database->rollBack();

            if($printErrors) echo $e->getMessage();

        }

        $this->existsInDatabase = true;
        
        return $this->id;
    }

    public function deleteFromDatabase(){
      self::deleteFromDatabaseStatic($this->tables, $this->id, $this->database);
      $this->existsInDatabase = false;
    }

    public static function deleteFromDatabaseStatic($tables, $conditions, $database = null) {

        if (!is_array($tables))
            $tables = array($tables);
 
        if (is_null($database))
            $database = T3Db::api();

        $firstTable = reset($tables);

        if (!is_array($conditions))
            $conditions = array(TableDescription::get($firstTable)->idFieldName => $conditions);

        $conditionsStrs = array();
        foreach($conditions as $k => $v)
          $conditionsStrs[] = "$k = " . $database->quote($v);

        $conditionsString = implode(" AND ", $conditionsStrs);

        $result = null;

        foreach($tables as $v) {
          if(ExpressValidator::fieldNamesValid($v, $conditions)->isError())
            return ExpressValidator::getReport();
          $affect = $database->delete($v, $conditionsString);
          if (is_null($result))
              $result = $affect;
        }

        return $result;

    }

    // Если вы хотите использовать методы createFromDatabase и createFromArray в
    // дочернем классе, вы должны переопределить их там таким же образом, как они
    // определены ниже


    public static function createFromDatabase($conditions) {
        return self::createFromDatabaseByClass($conditions, __CLASS__);
    }

    public static function createFromArray(&$array) {
        return self::createFromArrayByClass($array, __CLASS__);
    }
    
    
    

}