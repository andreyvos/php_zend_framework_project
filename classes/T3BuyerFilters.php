<?php

class T3BuyerFilters {
    public $system;

    public static $_instance;

    /**
    * @return T3BuyerFilters
    */
    static public function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }

    /***************************************************************/

    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    public $database;

    public $filters = array();

    protected function initialize() {
        $this->system   = T3System::getInstance();
        $this->database = T3Db::api();
    }



    public function getConditions_Array($id, $minimization = false){
        if($minimization){
            $ar = T3BuyerChannels::getPoolFiltersData($id);
        }
        else {
            $ar = $this->database->fetchAll("SELECT * FROM buyers_filters_conditions WHERE channel_id = ?", array($id));   
        }
        
        $result = array();
        
        foreach($ar as $k => $v){
            $result[$v['id']] = $ar[$k];
        }  
        
        return $result;
    }

    public function setConditions_Array($id, &$array){
        $filter = new T3BuyerFilter();
        $filter->channelId = $id;
        $filter->fromArray($array);
        $filter->saveToDatabase();
    }

    public function editConditions_Array(array $conditions){
        foreach($conditions as $k => $v){
            $condition = T3BuyerFilter_Condition::createFromArray($v);
            $condition->id = $k;
            $condition->saveToDatabase();     
        }
    }

    public function createConditions_Array(array $conditions){
        $result = array();
        foreach($conditions as $k => $v){
            $condition = T3BuyerFilter_Condition::createFromArray($v);
            $result[] = $condition->insertIntoDatabase();
        }
        return $result;
    }

    public function deleteConditions_Array(array $ids){
        $result = 0;
        foreach($ids as $v){
            $result += (int)(T3BuyerFilter_Condition::deleteFromDatabaseStatic('buyers_filters_conditions', $v));
        }
        return $result;
    }

    public function getFilters_Array($params = array()){
        if(!is_array($params)){
            $params = array(TableDescription::get('buyers_channels')->idFieldName => $params);
        }

        $select = $this->database->select()
        ->from(
            array('bc' => 'buyers_channels'),
            array('main_channel_id' => 'bc.id', 'bfc.*')
        )
        ->joinLeft(
            array('bfc' => 'buyers_filters_conditions'),
            'bc.id = bfc.channel_id'
        )
        ->order('bc.id');

        foreach($params as $k => $v){
            $select->where("$k = " . $this->database->quote($v));
        }

        $ar1 = $this->database->query($select)->fetchAll();
        $ar2 = array();
        
        foreach($ar1 as $k => $v){
            if(is_null($v['channel_id'])){
                $ar2[$v['main_channel_id']] = null;
                continue;
            }
            
            if(!isset($ar2[$v['channel_id']])){
                $ar2[$v['channel_id']] = array();
            }
            
            $ar2[$v['channel_id']][] =& $ar1[$k];
        }

        return $ar2;

    }

    public function getFilters($lazy = true){
        if($lazy && !is_null($this->filters)){
        return $this->filters;
        }
        
        $ar2 = $this->getFilters_Array();
        $this->filters = array();
        
        foreach($ar2 as $k => $v){
            $filter = new T3BuyerFilter();
            
            if(is_null($v)){
                $filter->id = $k;
            }
            else {
                $filter->fromArray($v);
            }
            
            $this->filters[$k] = $filter;
        }
        return $this->filters;
    }

    /**
    * Получение фильтра
    * 
    * @param mixed $channelId
    * @param mixed $lazy
    * @param mixed $minimization   получение только рабочих фильтров, для процесса получения лида (в настрйоках надо получать и отключенные фильтры)
    * 
    * @return T3BuyerFilter
    */
    public function getFilter($channelId, $lazy = true, $minimization = false){
        if($lazy && isset($this->filters[$channelId])){
            return $this->filters[$channelId];
        }
        
        $filter = new T3BuyerFilter();
        $filter->fromDatabase($channelId, (bool)$minimization);
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
    static public function copyFilters($from_channel_id, $to_channel_id, $buyer_id){
        /**
        * Ссылка на Объект базы данных
        * @var Zend_Db_Adapter_Abstract
        */

        $posting_id = $to_channel_id;

        $database =& self::getInstance()->database;

        // получение фильтров из исходного канала
        $filters = $database->fetchAssoc("select * from buyers_filters_conditions where channel_id='" . (int)$from_channel_id . "'");

        // удаление фильтров конечного канала
        $database->delete('buyers_filters_conditions', "channel_id = " . (int)$to_channel_id);

        $count = 0;

        $copied_filters = Array();

        
        //die( varDump( $postingObject ) );*/
        // создание фильтров в конечном канале
        foreach($filters as $filter){
            unset($filter['id']);
            $filter['channel_id'] = $to_channel_id;

            $copied_filters[] = $filter;

            $database->insert('buyers_filters_conditions', $filter);
            if($filter['works']) $count++;           
        }


        $event_type_id = T3TimeLine_EventType::getIdByType(T3TimeLine_EventType::POSTING_FILTERS_COPY);
        if($event_type_id){
             T3TimeLine_Event::add($buyer_id,  //$data["buyerId"],
                                  T3TimeLine_Event::TYPE_BUYER,
                                  $event_type_id,
                                  array('posting_id' => $posting_id ,'from_channel_id' => $from_channel_id, 'to_channel_id' => $to_channel_id, 'copied_filters_array' => $copied_filters )
            );            
        }



        return $count;   
    }

}
