<?

class Tnx_Filters {

  public static $_instance;

  public $system;
  
  /**
  * Ссылка на Объект базы данных
  * @var Zend_Db_Adapter_Abstract
  */
  public $database;

  public $filters = array();

  protected function initialize() {
    $this->system = T3System::getInstance();
    $this->database = $this->system->getConnect();
  }

  /**
  * @return Tnx_Filters
  */
  public static function getInstance(){
    if(is_null(self::$_instance)){
      self::$_instance = new self();
      self::$_instance->initialize();
    }
    return self::$_instance;
  }

  public function getConditions_Array($id){
    $ar = $this->database->fetchAll("
      SELECT *
      FROM tnx_filters
      WHERE channel_id = ?
    ", array($id));
    $result = array();
    foreach($ar as $k => $v)
      $result[$v['id']] = & $ar[$k];
    return $result;
  }

  public function setConditions_Array($id, &$array){
    $filter = new Tnx_Filter();
    $filter->channelId = $id;
    $filter->fromArray($array);
    $filter->saveToDatabase();
  }

  public function editConditions_Array(array $conditions){
    foreach($conditions as $k => $v){
      $condition = Tnx_Filter_Condition::createFromArray($v);
      $condition->id = $k;
      $condition->saveToDatabase();     
    }
  }

  public function createConditions_Array(array $conditions){
    $result = array();
    foreach($conditions as $k => $v){
      $condition = Tnx_Filter_Condition::createFromArray($v);
      $result[] = $condition->insertIntoDatabase();
    }
    return $result;
  }

  public function deleteConditions_Array(array $ids){
    $result = 0;
    foreach($ids as $v)
      $result += (int)(Tnx_Filter_Condition::deleteFromDatabaseStatic('tnx_filters', $v));
    return $result;
  }

  public function getFilters_Array($params = array()){
    if(!is_array($params))
      $params = array(TableDescription::get('buyers_channels')->idFieldName => $params);

    $select = $this->database->select()
      ->from(
        array('bc' => 'buyers_channels'),
        array('main_channel_id' => 'bc.id', 'bfc.*'))
      ->joinLeft(
        array('bfc' => 'tnx_filters'),
        'bc.id = bfc.channel_id')
      ->order('bc.id');

    foreach($params as $k => $v)
      $select->where("$k = " . $this->database->quote($v));

    $ar1 = $this->database->query($select)->fetchAll();

    $ar2 = array();
    foreach($ar1 as $k => $v){
      if(is_null($v['channel_id'])){
        $ar2[$v['main_channel_id']] = null;
        continue;
      }
      if(!isset($ar2[$v['channel_id']]))
        $ar2[$v['channel_id']] = array();
      $ar2[$v['channel_id']][] =& $ar1[$k];
    }

    return $ar2;

  }

  public function getFilters($lazy = true){
    if($lazy && !is_null($this->filters))
      return $this->filters;
    $ar2 = $this->getFilters_Array();
    $this->filters = array();
    foreach($ar2 as $k => $v){
      $filter = new Tnx_Filter();
      if(is_null($v))
        $filter->id = $k;
      else
        $filter->fromArray($v);
      $this->filters[$k] = $filter;
    }
    return $this->filters;
  }

  public function getFilter($channelId, $lazy = true){
    if($lazy && isset($this->filters[$channelId]))
      return $this->filters[$channelId];
    $filter = new Tnx_Filter();
    $filter->fromDatabase($channelId);
    $this->filters[$channelId] = $filter;
    return $filter;
  }
  
    public function clearCacheFilter($channelId){
        unset($this->filters[$channelId]);    
    }
  
    /**
    * Копирование фильтров одного баера в другого
    * 
    * @param int $copyBuyerID
    * @param int $toBuyerID
    */
    static public function copyFilters($copyBuyerID, $toBuyerID){
        /**
        * Ссылка на Объект базы данных
        * @var Zend_Db_Adapter_Abstract
        */
        $database =& self::getInstance()->database;
        
        // получение фильтров из исходного канала
        $filters = $database->fetchAssoc("select * from tnx_filters where channel_id='" . (int)$copyBuyerID . "'");
        
        // удаление фильтров конечного канала
        $database->delete('tnx_filters', "channel_id = " . (int)$toBuyerID);
        
        $count = 0;
        
        // создание фильтров в конечном канале
        foreach($filters as $filter){
            unset($filter['id']);
            $filter['channel_id'] = $toBuyerID;
            $database->insert('tnx_filters', $filter);
            if($filter['works']) $count++;           
        }
        
        return $count;   
    }

}


