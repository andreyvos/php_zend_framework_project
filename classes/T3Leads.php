<?

class T3Leads {

    protected static $_instance = null;
    public $database;

    protected function  initialize() {
        $this->database = T3Db::api();
    }

    /**
    * Получить реестр лидов
    * @return T3Leads
    */
    public static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }
  
  
  /**
    * Возвращает массив оплаты
    * 
    * @return array
    */
    public function getBilling_Array($id){
        if(is_numeric($id)){
            return $this->database->fetchAssoc('select * from leads_billing where idLead=? order by id desc', $id); 
        }
        return null;  
    }

    public function getLeadsQuery($params = array(), $order = array('datetime' => 'desc'), $pa = null, $itemsperpage = 50)
    {
        $p = "";
        foreach ($params as $pvar => $pval)
        {
            $p.= " and (".$pvar."='".addslashes($pval)."')";
        }

        if (!is_array($order))
        {
            $order = array(" "=>$order);
        }

        $o = "";
        foreach ($order as $pvar => $pval)
        {
            if (strlen($o)>0)
            {
                $o.=", ";
            }
            $o.= " ".$pvar." ".addslashes($pval)." ";
        }
        $l = "";
        if ($pa!=null)
        {
            $l = ' LIMIT '.($pa * $itemsperpage).', '.$itemsperpage;
        }
        $q = 'select * from leads_data where 1 '.$p.(strlen($o)>0?' ORDER by '.$o:'').$l;
        
        return $q;
    }

    public function getLeads_Count()
    {
        return $this->database->fetchOne("select count(*) from leads_data");
    }
    
    public function getLeads_Array($params = array(), $order = array('datetime' => 'desc'), $p = 0)
    {
        //T3SimpleDbSelect::select('leads_data', $params, $order)->fetchAll()
        $q = $this->getLeadsQuery($params, $order, $p);
        $result = $this->database->fetchAll($q);
        return $result;
    }

    // /account/lead/search/
    // Поиск лида
    // - По ID
    // - по Email
    // - по SSN
    // - по телефонному номеру
  public function getLeadsByParam_Array($field, $value){
    return $this->getLeads(array($field => $value));
  }
    
    // /account/lead/management/
    // Список лидов с сортировками и раличными кнопками для действий
    // - по продукту и
    // - по дате и
    // - по вебмастеру и
    // - по статусу
    // - для определенного канала с определенным статусом ?????????????????????
  public function getOrderedList_Array($order){
    return T3SimpleDbSelect::select('leads_data', null, $order)->fetchAll();
  }

    // /account/lead/main/?id=XXXX
    // Просмотр заголовочных данных лида
  public function getLeadHeader_Array($id){
    return DbSerializable::fromDatabaseStatic('leads_data', $id);
  }

    // /account/lead/body/?id=XXXX
    // Просмотр body

  public function getLeadBody($id, $product = null){
    return $this->getLeadBody_Array($id, $product);
  }

  public function getLeadBody_Array($id, $product = null){
      
      $lead = new T3Lead();
      $lead->loadFromGetID($id);
      return $lead->getBodyFromDatabase()->getParams();
          
    /*
    if(is_null($product)){
      $ar = DbSerializable::fromDatabaseStatic('leads_data', $id);
      if($ar===false)
        return false;
      $product = $ar['product'];
    }
    
    $lead = new T3Lead($product);
    $lead->fromDatabase($id);
    
    //if(!T3LeadsProducts::productExists($product))
    //  return false;
      
    return T3LeadBody_Abstract::fromDatabaseStatic("leads_data_$product" , $id);
    */
  }

  public function editLeadBody_Array($id, $initialData, $finalData, $product = null, &$outputReport = null){
    if(is_null($product)){
      $ar = DbSerializable::fromDatabaseStatic('leads_data', $id);
      if($ar===false)
        return false;
      $product = $ar['product'];
    }
    //if(!T3LeadsProducts::productExists($product))
    //  return false;
    if($initialData === null){
      $initialData = DbSerializable::fromDatabaseStatic("leads_data_$product", $id);
      if($initialData === false)
        return false;
    }
    DbSerializable::saveToDatabaseStatic("leads_data_$product", $id, $finalData, true); // TODO даты не проходят валидацию, но валидатор нельзя отключать. Он временно отключен с помощью последнего true
    if(ExpressValidator::isError()){
      if(func_num_args() == 4)
        $outputReport = ExpressValidator::getReport()->copy();
      return false;
    }
    T3LeadBodyChangingLog::getInstance()->recordArray($initialData, $finalData);
    return true;
  }
  
    static public function getLeadStatusTitle($name){
        switch($name) // переключающее выражение
        {
           case 'sold':         return 'Sold';
           case 'reject':       return 'Rejected';
           case 'error':        return 'Error';
           case 'duplicate':    return 'Duplicate';
           case 'pending':      return self::getValToUser('Pending Match', 'Rejected');
           case 'timeout':      return 'Time Out';
           case 'process':      return 'In Process';
           case 'verification': return 'In Verification';
           case 'noconect':     return self::getValToUser('Connect Failed', 'Rejected');
           case 'nosend':       return self::getValToUser('Not Posted', 'Pending Match'); 
           default:             return 'Rejected';
        } 
    }
    
    
    static private function getValToUser($default, $webmaster = null){
        if(!is_null($webmaster) && T3Users::getInstance()->getCurrentUser()->role == 'webmaster'){
            return $webmaster; 
        }
        return $default;          
    }
    
    
    static public function addGetID($webmasterID){
        $webmasterID = (int)$webmasterID;
        
        T3Db::api()->query("LOCK TABLES leads_nums WRITE");
        
        try {
            $searchNum = T3Db::api()->fetchOne('select current_num from leads_nums where webmaster=?', $webmasterID);
            
            if($searchNum === false){
                T3Db::api()->insert('leads_nums', array(
                    'webmaster'   => $webmasterID,
                    'current_num' => '1',
                ));    
            }
            else {
                T3Db::api()->update('leads_nums', array(
                    'current_num' => new Zend_Db_Expr("current_num+1"),
                ), "webmaster={$webmasterID}");    
            }
        }
        catch(Exception $e){}
        
        T3Db::api()->query("UNLOCK TABLES");
        
        return (int)$searchNum + 1;      
    }
    
    static public function getSubaccountStr($sbID){
        $sbID = (int)$sbID;
        if($sbID != 0){
            $sbStr = T3Db::api()->fetchOne("select `name` from users_company_webmaster_subacc where id=?", $sbID); 
            if($sbStr !== false){
                return $sbStr;
            }       
        }
        
        return '0';   
    }
}

