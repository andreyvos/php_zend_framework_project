<?php

TableDescription::addTable('tasks', array(
    'id',
    'status',
    'type',
    'importance',
    'title',
    'create_date',
    'concatenation',
    'responsibleUser',
    'creator',  
));

class T3Task_Item extends DbSerializable {
    public $id;
    
    /**
    * Статус задачи
    * open  - Задача открыта, есть ответсвенные за её выполнение (она может быть не в приоритете, ну при этом она всеравно стоит перед кем то)
    * close - Задача законченна
    * wait  - Задача открыта, ну нет ответсвенного за её выполнение, она ждет назначения
    * 
    * @var string
    */
    public $status;
    
    /**
    * Тип задачи, все типы объявлены в T3Task_General::getTypesTitles
    * 
    * @var string
    */
    public $type;
    
    /**
    * Тип задачи, все возможные важности объявлены в T3Task_General::getImportanceTitles
    * Эта переменная является числом, чем оно больше тем более важна задача
    * 
    * @var int
    */
    public $importance;
    
    /**
    * Тема задачи
    * 
    * @var string
    */
    public $title;
    
    /**
    * Дата создания
    * 
    * @var string
    */
    public $create_date;
    
    /**
    * Цепочка Ответсвенности, JSON объект, в котором хранится цепочка ответсвенности, 
    * информация элемента (id, name, дата завершения) и порядковый номер ответсвернного
    * {
    *   'current': 1, // показывает порядковый номер массива this.users, на которм в данный момент стоит выполнение
    *   'users': [
    *     {
    *       'id'      : '1',
    *       'name'    : 'Anton',
    *       'endTime' : '2010-01-02 13:23:42'
    *     },
    *     {
    *       'id'      : '2',
    *       'name'    : 'Sanya',
    *       'endTime' : null
    *     }
    *   ]
    * }
    * 
    * @var string
    */
    public $concatenation;
    
    /**
    * Копия JSON объекта в виде PHP массива
    * 
    * @var array
    */
    public $concatenationArray;
    
    /**
    * ID пользователя, который в данный момент отвечает за задачу (Можно получить из объекта `concatenation`)
    * Вынесен в отдельное поле, что бы можно было SQL запросом получить все текущие задачи одного пользователя
    * 
    * @var int
    */
    public $responsibleUser;
    
    /**
    * Информация о создателе задачи, хранится в свободном виде в строке.
    * 
    * @var string
    */
    public $creator;
    
    public function __construct() {
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();

        $this->tables = array('tasks');

         $this->create_date = date("Y-m-d H:i:s");       

    }
    
    /**
    * @return T3Task_Item
    */
    static public function create(){
        return new T3Task_Item();
    } 
    
    /**
    * @return T3Task_Item
    */
    public function setTitle($title){
        $this->title = $title;
        return $this;
    }
    
    /**
    * @return T3Task_Item
    */
    public function setTaskType($type){
        $this->type = $type;
        return $this;
    }
    
    /**
    * @return T3Task_Item
    */
    public function setConcatenationJSON($concatenation){
        // Добавление нового
        $this->concatenation = $concatenation;
        $this->concatenationJsonToArray(); 
        
        $this->responsibleUser = 0;
        if(isset($this->concatenationArray['users'][$this->concatenationArray['current']]['id'])){
            $this->responsibleUser = $this->concatenationArray['users'][$this->concatenationArray['current']]['id'];        
        }
       
        // Преобразование статуса
        if($this->status != 'close'){
            if($this->concatenationArray['current'] < count($this->concatenationArray['users'])){
                $this->status = 'open';    
            }
            else {
                $this->status = 'wait';    
            }   
        }
        
        
        return $this;
    }
    
    /**
    * @return T3Task_Item
    */
    public function setConcatenationOneUser($id, $name = null){
        $u = T3Users::getUserById($id);  
        if(is_object($u) && $u instanceof T3User && $u->id){    
            if(is_null($name)){   
                $name = $u->nickname;
            }  
            
            $this->setConcatenationJSON(Zend_Json::encode(array(
                'current'   => 0,
                'users'     => array(
                    array(
                        'id'        => $id,
                        'name'      => $name,
                        'endTime'   => null
                    )
                )
            )));  
        }
        return $this;  
    }
    
    public function setConcatenationAndReIndexAndSave($concatenation){
        $result = array(
            'status' => 'ok', // ok, error
            'reason' => '', // текст ошибки 
        );
        
        
        
        if($this->concatenation == $concatenation){
            // Ничего не поменялось   
        }
        else {
            $old = Zend_Json::decode($this->concatenation);
            $new = Zend_Json::decode($concatenation);
            
            if($old['current'] == $new['current']){
                //varExport($old);
                //varExport($new);     
                $this->concatenation = $concatenation;
                $this->concatenationJsonToArray();
               
                $oldResponsibleUser = $this->responsibleUser;
               
                $this->responsibleUser = 0;
                if(isset($this->concatenationArray['users'][$this->concatenationArray['current']]['id'])){
                    $this->responsibleUser = $this->concatenationArray['users'][$this->concatenationArray['current']]['id'];        
                }  
               
                if($this->responsibleUser == $oldResponsibleUser){
                    // Текущий ответсвенный не поменялся, ничего переиндексировать не надо
                    // Ну на всякий случай мы его переиндексируем, потому что мог поменятся приоритет
                    T3Task_General::reindexUser($this->responsibleUser);
                }
                else {
                    if($oldResponsibleUser && $this->responsibleUser){
                        // был один, стал другой
                        T3Task_General::reindexUser($oldResponsibleUser);
                        T3Task_General::reindexUser($this->responsibleUser);
                        $this->status = 'open';
                        $this->setNew(true);  
                        
                        $this->sendMessageNewTask();
                    }
                    else if($oldResponsibleUser == 0 && $this->responsibleUser){
                        // был никто, стал кто то
                        T3Task_General::reindexUser($this->responsibleUser);
                        $this->status = 'open';
                        $this->setNew(true);  
                        
                        $this->sendMessageNewTask();  
                    }
                    else if($oldResponsibleUser && $this->responsibleUser == 0){
                        T3Task_General::reindexUser($oldResponsibleUser);
                        $this->status = 'wait';
                        $this->setNew(false);    
                    }
                    else {
                        // error
                        $result['status'] = 'error';
                        $result['reason'] = "Unknown #1";   
                    }
                } 
            }                                                                            
            else {
                // error
                $result['status'] = 'error';
                $result['reason'] = "Эта цепочка уже устарела и вы не можите её поменять. <a href='{$_SERVER['REQUEST_URI']}'>Обновите страницу</a>";
            }        
        } 
        
        $this->saveToDatabase();   
        
        
        return $result;  
    }
    
    /**
    * Создание Array переменной на основе Json
    */
    protected function concatenationJsonToArray(){
        $this->concatenationArray = Zend_Json::decode($this->concatenation);     
    }
    
    /**
    * Создание Json переменной на основе Array 
    */
    protected function concatenationArrayToJson(){
        $this->concatenation = Zend_Json::encode($this->concatenationArray);   
    }
    
    /**
    * @return T3Task_Item
    */
    public function setImportance($importance){
        $this->importance = $importance;
        return $this;
    }
    
    /**
    * @return T3Task_Item
    */
    public function setCreator($creator){
        $this->creator = $creator;
        return $this;
    }
    
    public function fromDatabase($conditions, $fillObjects = array()){
        parent::fromDatabase($conditions, $fillObjects);
        $this->concatenationJsonToArray();
    }
    
    public function getHistory(){
        // Если нет открытого сообщения
        /*
        if(!T3Db::api()->fetchOne("select count(*) from tasks_messages where task_id=? and `status` = 'open'", $this->id)){
            T3Db::api()->insert("tasks_messages", array(
                'task_id'       => $this->id, 
                'last_update'   => date("Y-m-d H:i:s"), 
                'from_name'     => T3Users::getCUser()->nickname,
                'status'        => 'open',
            ));        
        }
        */
        
        return T3Db::api()->fetchAll("select * from tasks_messages where task_id=? and `status`='open' order by id desc", $this->id);
    }
    
    public function createFirstMessage($text, $creator = 'Automatic'){
        //if(!T3Db::api()->fetchOne("select count(*) from tasks_messages where task_id=?", $this->id)){
        T3Db::api()->insert("tasks_messages", array(
            'task_id'       => $this->id, 
            'last_update'   => date("Y-m-d H:i:s"), 
            'from_name'     => T3Users::getCUser()->nickname,
            'status'        => 'open',
            'text'          => $text,
        )); 
        
        return T3Db::api()->lastInsertId();       
        //}
    }
    
    public function isNew(){
        return T3Db::api()->fetchOne("select `new` from tasks where id=?", $this->id);
    } 
    
    public function setNew($flag){
        T3Db::api()->update("tasks", array(
            'new' => (string)(int)$flag,
        ), "id=".$this->id);
        
        if($this->responsibleUser){
            T3Task_General::reindexUser($this->responsibleUser);
        }
    }  
    
    public function updateAutoDescription(){
        $text = str_replace(">", "> ", T3Db::api()->fetchOne("select `text` from tasks_messages where task_id=? and `status`='open' order by id desc limit 1", $this->id)); 
        $text = str_replace(array("\r", "\n", "  ", "   ", "    "), array(" ", " ", " ", " ", " "), $text);
        $text = strip_tags($text);
        
        if(strlen($text)) $text = " - " . substr($text, 0, 200) . "...";
        else              $text = "";
        
        $text = preg_replace("/!([a-zа-я0-9]|\.|\ |\,)/i", '', $text);
        
        
        try{
            T3Db::api()->update("tasks", array(
                'auto_description' => $text,
            ), "id=".$this->id);
        }
        catch(Exception $e){
            
        } 
    }
    
    public function isAccessToComplite(){
        $addComplite = false;
        if(
        in_array($this->status, array('open', 'wait')) && 
        in_array(T3Users::getCUser()->id, array(1000000, $this->responsibleUser))
        ){
            $addComplite = true;   
        }  
        return $addComplite;   
    }
    
    public function complite(){
        $result = array(
            'status' => 'error', // ok, error
            'reason' => 'Unknown',    
        ); 
        
        $reindexUsers = array();
        
        if($this->isAccessToComplite()){
            if($this->status == 'open'){
                $result['status'] = 'ok';
                
                if($this->concatenationArray['current'] >= count($this->concatenationArray['users']) - 1){
                    // Если это последний человек, то у его убираем и закрываем 
                    $reindexUsers[] = $this->concatenationArray['users'][$this->concatenationArray['current']]['id'];
                    
                    $this->concatenationArray['users'][$this->concatenationArray['current']]['endTime'] = date("Y-m-d H:i:s"); 
                    
                    $this->concatenationArray['current']++; 
                    $this->concatenationArrayToJson();
                    
                    
                    $this->responsibleUser = 0;
                    $this->status = 'close';
                    $this->saveToDatabase(); 
                    
                    $this->runEvent('close');  
                }
                else {
                    // Если кто то еще есть, то от этого убираем, другому добавляем 
                    $reindexUsers[] = $this->concatenationArray['users'][$this->concatenationArray['current']]['id']; 
                    $reindexUsers[] = $this->concatenationArray['users'][$this->concatenationArray['current']+1]['id'];
                    
                    $this->concatenationArray['users'][$this->concatenationArray['current']]['endTime'] = date("Y-m-d H:i:s");
                    
                    $this->responsibleUser = $this->concatenationArray['users'][$this->concatenationArray['current']+1]['id'];  
                    
                    $this->concatenationArray['current']++; 
                    $this->concatenationArrayToJson();
                    
                    $this->setNew(true);
                    $this->status = 'open';
                    $this->saveToDatabase();    
                    
                    $this->sendMessageNewTask();
                }
            }
            else if($this->status == 'wait'){
                $result['status'] = 'ok';
                
                // просто закрыть задачу. Она не у кого не стоит, и после закрытия тоже не у кого не будте
                $this->responsibleUser = 0;
                $this->status = 'close';
                $this->saveToDatabase();
                
                $this->runEvent('close'); 
            }
            else {
                $result['reason'] = 'Status not Open or Wait';     
            }   
            
            // Логирование комплитов
            if($result['status'] == 'ok'){
                T3Db::api()->insert("tasks_complite", array(
                    'task_id'   => $this->id,
                    'date'      => date("Y-m-d H:i:s"),
                    'user'      => T3Users::getCUser()->id,
                    'ip'        => myHttp::get_ip_num(),
                ));
            } 
        }
        else {
            $result['reason'] = 'Not Access';
        }  
        
        if(count($reindexUsers)){
            foreach($reindexUsers as $user){
                T3Task_General::reindexUser($user);     
            }
        }
        
        return $result;  
    }
    
    public function handOver($to, $re = false, $complite = true){
        // добавить нового(ых) юзверя(ей)
        $newArray = array();
        $i = 0;
        foreach($this->concatenationArray['users'] as $user){
            $newArray[] = $user;
            if($i == $this->concatenationArray['current']){
                $newArray[] = array(
                    'id'        => $to,
                    'name'      => T3Users::getUserById($to)->nickname,
                    'endTime'   => NULL, 
                ); 
                if($re) $newArray[] = $user;     
            } 
            $i++;
        } 
        $this->concatenationArray['users'] = $newArray; 
        
        // подчинить масив
        $this->concatenationArrayRepire();
        
        // сделать complite
        if($complite) $this->complite();        
    }
    
    protected function concatenationArrayRepire(){
        $newArray = $this->concatenationArray['users'];
        if(count($newArray)){
            $goodArray = false;
            do {
                $newArrayTemp = array();
                $goodArray = true;
                $lastID = null;
                
                foreach($newArray as $el){
                    if(is_null($lastID) || $lastID != $el['id']){
                        // норм
                        $newArrayTemp[] = $el; 
                        $lastID = $el['id'];    
                    }
                    else {
                        // повтор  
                        $goodArray = false;  
                    }
                }
                
                $newArray = $newArrayTemp;       
            } 
            while (!$goodArray);
        }  
        $this->concatenationArray['users'] = $newArray;   
    }
    
    public function sendMessageNewTask(){
        /** @var T3User */
        $user = T3Users::getUserById($this->responsibleUser);
        
        $messages = T3Db::api()->fetchAll("select `text`,from_name from tasks_messages where task_id=? and `status`='open' and LENGTH(`text`) > 10 order by id desc", $this->id);
        $text = '';
        
        if(count($messages)){
            foreach($messages as $ms){
                $text.= "<div style='margin-top:30px; margin-bottom:2px;border-bottom:#dddddd solid 1px;'><b>{$ms['from_name']}</b></div>";
                $text.= "{$ms['text']}";    
            }    
        }
        
        $impArr = T3Task_General::getImportanceTitles();  
        $imp = ifset($impArr[$this->importance], $this->importance);
        
        /**   
        * @var T3Mail_Message
        */
        $message = T3Mail::createMessage('newTask', array (
          'importance'      => $imp,
          'link'            => 'https://account.t3leads.com/en/account/tasks/viewer?id='.$this->id,
          'title'           => $this->title,
          'text'            => $text,
          'concatenation'   => T3Ui_Concatenation::renderTextForEmail($this),
        ));
        
        $message->setSubject("({$imp}) {$this->title}");
        
        $message->SendMail($user->email, $user->nickname);     
    }
    
    /**
    * Добавить вызов свой функции на определеннре собитые с этим таском.
    * В вашу функцию будет передан 2 параметра: TaskID и $additionalParams
    * $additionalParams - уникальные дополнительные параметры, у каждого типа свои параметры
    * 
    * Рекомендуется писать свои фунции как статические методы класса, 
    * при этом не надо забитится о том что бы файл с классом был подгружен, 
    * auto loader, который работает во всей партнерке сделает это за вас.
    * 
    * Пример использования:
    * $task = T3Task_General::createTaskLigths(1000000, "Test Task", "...");
    * $task->addEvent('close', array('SeoProjects', 'endTask'));
    * 
    * При такой настройках, при событии close, будет вызвана функция:
    * SeoProjects::endTask($taskID, $additionalParams);
    * 
    * 
    * @param mixed $type
    * @param mixed $function
    * @return T3Task_Item
    */
    public function addEvent($type, $function){
        T3Db::api()->insert("tasks_events", array(
            'task_id'       =>  $this->id,
            'event_type'    =>  $type,
            'function'      =>  serialize($function),
        ));   
        
        return $this;     
    }
    
    /**
    * Добавить вызов свой функции при закрытии таска
    * 
    * @param mixed $function
    */
    public function addEventClose($function){
        $this->addEvent('close', $function);    
    }
    
    public function runEvent($type, $additionalParams = array()){
        $events = T3Db::api()->fetchAll("select id, task_id, `function` from tasks_events where task_id=? and event_type=?", array($this->id, $type));
        if(count($events)){
            $insert = array();
            
            foreach($events as $event){
                $startTime = microtime(1);
                $startMemory = memory_get_usage();
                
                $result = call_user_func_array(unserialize($event['function']), array($this->id, $additionalParams)); 
                
                $insert[] = array(
                    'event_id'      => $event['id'], 
                    'task_id'       => $event['task_id'],
                    'run_end_date'  => date("Y-m-d H:i:s"),
                    'run_seconds'   => microtime(1) - $startTime,
                    'run_memory'    => memory_get_usage() - $startMemory, 
                    'return'        => serialize($result), 
                );
            }
            
            T3Db::api()->insertMulty("tasks_events_log", array_keys($insert[0]), $insert);
        }    
    }
    
    public function addPlugin($className, $options = array()){
        T3Db::api()->insert("tasks_plugins", array(
            'task_id'   => $this->id,
            'plugin'    => $className,
            'options'   => serialize($options),
        ));      
    }
    
    public function getPlugins(){
        $return = array();
        
        $all = T3Db::api()->fetchAll("select `plugin`, `options` from tasks_plugins where task_id=?", $this->id);
        
        if(count($all)){
            $i = 0;
            foreach($all as $el){
                $return[$i] = new $el['plugin']($this->id ,unserialize($el['options']));
                $i++;
            }   
        }
        
        return $return;    
    }
    
    public function getPlugin($name){
        $return = array();
        
        $all = T3Db::api()->fetchAll("select `plugin`, `options` from tasks_plugins where task_id=? and `plugin`=?", array($this->id, $name));
        
        if(count($all)){
            $i = 0;
            foreach($all as $el){
                $return[$i] = new $el['plugin']($this->id ,unserialize($el['options']));
            }   
        }
        
        return ifset($return[0]);    
    }
    
}