<?php

TableDescription::addTable('pingtrees', array(
    'id',
    'product',
    'title',
    'create_date',
    'tree',
    'tree_text',                                               
));

class T3PingTree extends DbSerializable {

    public $id;
    public $title; 
    public $product; 
    public $create_date;
    
    /**
    * схема, порядок + правила, на основе которых определяется порядок постинга 
    * @var array
    */
    public $tree; 
    public $tree_text; 
    
    /**
    * порядок постингов, полученный в результате схемы 
    * @var array
    */
    public $order;
    
    /**
    * директория с модулями для обработки различных типов веток.  
    * @var string dir (dir1/dir2/)
    */
    protected $dir_branchTypes;


    /**
    * асоциативный массив уже загруженных деревьев, для предотвращения повторной загрузки в одном цикле
    * @var array
    */
    protected $load_tree_array = array();
    
    /**
    * Констрактор класса
    * Передав в него $loadInfo можно сразу получить готовый, загруженный и обработанный объект.
    * 1. T3Lead - загрузит Pingtree для лида
    * 2. int - загрузит Pingtree по ID
    * 
    * @param mixed $loadInfo
    * @param mixed $options
    */
    public function  __construct($loadInfo = null, $options = null) {
        if (!isset($this->className)) $this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('pingtrees');
        
        $this->dir_branchTypes = T3SYSTEM_BRANCHES . DIRECTORY_SEPARATOR;   
        
        if(isset($loadInfo)){
            // насйтрока объекта в зависимости от опций
            $this->setOptions($options);    
            
            // поиск лида разными типами в зависимости от объекта $loadInfo, и получение конечных order данных
            if(is_numeric($loadInfo))           $this->runTreeByID($loadInfo);        // загрузка пингтрии с определенным ID 
            else if(is_a($loadInfo, "T3Lead"))  $this->runTreeForLead($loadInfo);     // загрузка Pingtree для лида       
        } 
    }
    
    /**
    * Установка опций
    * true или false устанавливает $this->orderStyleFull
    * 
    * @param mixed $options
    */
    public function setOptions($options){
          
    }
    
    /**
    * Загрузка PingTree по ID
    * 
    * @param mixed $id
    * @param bool $lite - загрузка только данных которые нужны для отправки лида 
    */
    public function runTreeByID($id, $lite = false){
        if(is_numeric($id)){
            if($lite){
                if($this->fromDatabase($id, array(), array('id', 'tree'))){
                    $this->runTree($this->tree);    
                }    
            }
            else {
                if($this->fromDatabase($id)){
                    $this->runTree($this->tree);    
                } 
            }
        }        
    }
    
    /**
    * Загрузка PingTree для Лида
    * 
    * @param T3Lead $lead
    */
    public function runTreeForLead(T3Lead $lead){        
        $id = self::searchTree(
            $lead->product,
            $lead->affid,
            $lead->get_method,
            $lead->channel_id    
        );
        
        if($id) $this->runTreeByID($id, 1); // загружать только часть объекта, которой достаточно для отправки лида  
    }
    
    /**
    * Обработка одного уровня дерева
    * 
    * @param array $tree
    */
    function runTree($tree) {
        foreach($tree as $branch) {

            // проверка и корректировака значения rate
            if (!isset($branch['rate'])) {
                // rate не задан, ставиться значение по умолчанию
                $branch['rate'] = 100;
            } 
            else if (!is_numeric($branch['rate']) || $branch['rate']<0 || $branch['rate']>100) {
                // rate задан не правильно !!!

                // логирование ошибки
                $this->errorLog("InvalidRate", "Invalid Rate\r\n" . var_export($branch,true));

                // ставим значение по умолчанию
                $branch['rate'] = 100;
            } 
            else {
                $branch['rate'] = ceil($branch['rate']);
            }

            // отсеивание по rate (процентному добавления ветки)
            if ($branch['rate'] == 100 || rand(1,100)<=$branch['rate']) { // добавление ветви
                $this->addBranch($branch);
            } 
        }
    }
    
    
    /**
    * Обрабодка звена. Запуск соответсвующей функции
    * 
    * @param array $branch
    */
    function addBranch($branch) {
        if (isset($branch['type'])) {
            if (is_file($this->dir_branchTypes . "{$branch['type']}.branch.pingTree.php")) {
                // присоединение файла модуля
                include_once($this->dir_branchTypes . "{$branch['type']}.branch.pingTree.php");
                if (function_exists("{$branch['type']}__branch_pingTree")) {
                    $function = "{$branch['type']}__branch_pingTree";
                    $function($this, $branch);
                } 
                else {
                    // логирование ошибки. фунция не найденна
                    $this->errorLog("FileNotFound","Function {$branch['type']}__branch_pingTree Not Found \r\n".
                                    "File: " . $this->dir_branchTypes . "{$branch['type']}.branch.pingTree.php");
                }
            } 
            else {
                // логирование ошибки. файл не найден
                $this->errorLog("FileNotFound","File: " . $this->dir_branchTypes . "{$branch['type']}.branch.pingTree.php - NOT Found");
            }
        } 
        else {
            // логирование ошибки. тип не указан
            $this->errorLog("InvalidType","Invalid Type\r\n" . var_export($branch,true));
        }
    }
    
    /**
    * Поиск ID Pingtree по параметрам
    * 
    * @param mixed $product
    * @param mixed $webmaster
    * @param mixed $channelType
    * @param mixed $channelID
    * @return string
    */
    static function searchTree($product, $webmaster, $channelType, $channelID) {
        return T3Db::api()->fetchOne(
            "select scheme from pingtrees_allocation where product=? and `status` = 'activ' and (channel_type='*' or channel_type=?) and (idcompany='*' or idcompany=?)" .
            " and (channel='*' or channel=?) order by channel desc,idcompany desc ,channel_type desc limit 1",
            array($product, $channelType, $webmaster, $channelID)
        );
    }
    
    /**
    * Преобразование простого синтаксиса PingTree к сложному
    * Можно передать в функцию массив со сложным или смешенным синтаксисом. Сложный синтаксис останется без изменений.
    * Простой синтаксис используется для настйроки Pingtree в веб интерфейсе через textarea 
    * 
    * @param array $treeArray
    * 
    * @return array
    */
    public function treeConvertor($treeArray){
        $result = array();
        if(is_array($treeArray)){
            foreach($treeArray as $key => $el){
                if(is_array($el)){
                    // короткий тип написания для shuffle
                    if(!isset($el['type']) && is_int($key)){
                        $el = array('type' => 'shuffle', 'array' => $el);
                        $result[] = $this->treeConvertor($el);         
                    }
                    else if($key === 'array'){
                        $result[$key] = $this->treeConvertor($el);    
                    }
                    else if(isset($el['type'])){
                        $result[] = $this->treeConvertor($el);    
                    } 
                    else {
                        $result[$key] = $el;       
                    }
                       
                }
                else if(is_numeric($el) && is_int($key)){
                    $result[] = array('type' => 'post', 'id' => $el);       
                }
                else if(is_string($el) && preg_match('/^[1-9][0-9]*,[1-9][0-9]*$/', $el)){
                    $r = explode(",", $el);
                    $result[] = array('type' => 'post', 'id' => $r[0], 'weight' => $r[1]);         
                }
                else {
                    $result[$key] = $el;   
                }
            }
        }
        return $result;    
    }

    
    
    /**
    * Создание нового PingTree
    * 
    * @param mixed $title
    * @param mixed $product
    * 
    * @return T3PingTree
    */
    static public function createNewPingtree($title, $product){
        
        $newPingTree = new T3PingTree;
        
        $newPingTree->title = $title;
        $newPingTree->product = $product;
        $newPingTree->create_date = date("Y-m-d H:i:s");
        
        $newPingTree->insertIntoDatabase();
        
        return $newPingTree;   
    }
    
    /**
    * Предварительная обработка перед заисью в базу данных
    */
    public function toArray($tables = null){
        $tempSendLog = $this->tree;
        
        $this->tree = $this->treeConvertor($this->tree);
        $this->tree = serialize($this->tree);
        
        $return = parent::toArray($tables);
        
        $this->tree = $tempSendLog;
        
        return $return;
    }

    /**
    * Обработка после получения из базы данных
    */
    public function fromArray(&$array){
        parent::fromArray($array);
        $this->tree = unserialize($this->tree);
    }

    function errorLog($name,$text) {
        //echo "TreeID:{$this->currentTreeID}\r\n\r\n{$text}";
        $name = "pingTree_" . $name;
        $text = "TreeID:{$this->currentTreeID}\r\n\r\n{$text}";

        /*
        $Q = "insert into errorsLog(`name`,`text`,`datetime`) values ('{$name}','" . mysql_real_escape_string($text) . "',NOW())";
        mysql_query($Q);
        */
    }

    /**
     * Построить пингри из активных каналов продукта
     */
    public function loadAllActiveChannels($product){
        $activePostings = T3Db::api()->fetchAll(
            "SELECT `id` FROM `buyers_channels` WHERE `product`=? AND `status`='active' AND `filter_datetime`=1",
            array($product)
        );

        $this->id       = 0;
        $this->order    = array();
        $this->product  = $product;
        $this->title    = 'All Active Channels';

        $allCount = count($activePostings);
        for($i = 0; $i < $allCount; $i++){
            $rand = rand(0, count($activePostings) - 1);
            $this->order[] = $activePostings[$rand]['id'];
            unset($activePostings[$rand]);
            $activePostings = array_values($activePostings);
        }
    }
}
