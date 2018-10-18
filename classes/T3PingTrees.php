<?

throw new Exception('Not Implemented');

class T3PingTrees {
    protected static $_instance = null;
    
    /**
    * @var T3System
    */
    protected $system; 
    
    /**
    * Ссылка на Объект базы данных
    * @var Zend_Db_Adapter_Abstract
    */
    protected $database; 



    protected function initialize() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
    }


    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }


    protected function treeFileNameFromId($id) {
        return T3SYSTEM_PINGTREES . DIRECTORY_SEPARATOR . "$id.pingTree.php";
    }

    /**
    * Получение списка pingtree из базы данных
    * 
    * @return array
    */
    public function getPingTreesList($product = null) {
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select()
        ->from("pingtrees", array(
            'id',
            'title',
            'create_date',
            'product',
            'useCountAll'   => new Zend_Db_Expr("(select count(*) from pingtrees_allocation where pingtrees_allocation.scheme = pingtrees.id)"),
            'useCountActiv' => new Zend_Db_Expr("(select count(*) from pingtrees_allocation where pingtrees_allocation.scheme = pingtrees.id and pingtrees_allocation.status = 'activ')"), 
        ));
        
        if(T3Users::getCUser()->isRoleBuyerAgent()){
            $select->where("product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')");   
        }
        
        if($product){
            $select->where("product=?", $product);    
        }
        
        return $this->database->fetchAll($select);
    }
    
    /**
    * Получение списка pingtree из базы данных
    * 
    * @return array
    */
    public function getPingTreeAlocattionRules($product = null, $webmaster = null) {
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select()->from("pingtrees_allocation", array(
            'id',
            'product',
            'channel_type',
            'idcompany',
            'channel',
            'pingtreeID' => 'scheme',
            'pingtreeTitle' => new Zend_Db_Expr("(select title from pingtrees where pingtrees.id=pingtrees_allocation.scheme)"), 
            'status',   
        ))->order(array('product', 'status'));
        
        if($product)    $select->where("product=?", $product);   
        if($webmaster)  $select->where("idcompany=?", $webmaster); 

        return T3Db::api()->fetchAll($select);
    }


    // /account/pingtree/list/
    // Список всех текущих pingTree
    // /account/pingtree/rules/
    // Список правил, по которым задаются pingTree
    public function getPingTrees() {
        $ar = $this->getPingTreesList();
        $result = array();
        foreach($ar as $v) {
            $result[$v] = $this->getPingTreeById($v);
        }
        return $result;
    }


    public function createPingTree($data) {

        $database = T3Db::api();

        
        $database->insert('pingtrees', array(
          'data' => $data
        ));
        
        return $database->lastInsertId();

        /*
        $list = $this->getPingTreesList();
        $max = -1;
        foreach($list as $v)
        if (is_numeric($v)) {
            $int = (int)$v;
            if ($int > $max)
                $max = $int;
        }
        $newId = $max+1;
        $this->editPingTree($newId, $data);
        return $newId;
        */
    }

    // /account/posting/pingtree/?id=XXXX
    // /account/pingtree/editor/?id=XXXX
    // Редактор PingTree
    // Управление положением постинга в pingtree
    // PostMain - данная таблица отвечает за схему продажи - PingTree
    public function getPingTree($id) {

        if(is_numeric($id))
        {
            $database = T3Db::api();
            $ar = $database->fetchRow("SELECT * FROM pingtrees WHERE id = ".$database->quote($id));
            
            /*
            $result = $this->system->readFileContents($this->treeFileNameFromId($id));
            if ($result===false)
                throw new Exception('Not Implemented');// запись в предусмотренные ошибки
            */
            if ($ar != false)
                return $ar['data'];
            else
                return $ar;
        }
        else
        {
            return false;
        }
    }
    
    static public function getPingtreesListOrderPriducts(){
        $result = T3System::getConnect()->fetchAll("SELECT
pingtrees.id,
pingtrees.title,
leads_type.title as Product 
FROM
    pingtrees
    INNER JOIN leads_type 
        ON (pingtrees.product = leads_type.name);");
        
        $return = array();
        
        if(is_array($result) && count($result)){
            foreach($result as $res){
                /*
                if(!isset($return[$res['Product']]))$return[$res['Product']] = array();
                $return[$res['Product']][$res['id']] = $res['title'];
                */
                $return[$res['id']] = "{$res['title']} ({$res['Product']})";
            } 
        }
        
        return $return;  
    }

    public function editPingTree($id, $data) {

        if (is_numeric($id))
        {
            $database = T3Db::api();
            $arr = array('data' => $data);
            $database->update("pingtrees", $arr, "id = " . $database->quote($id));
        }
        
        //$this->system->writeFile($this->treeFileNameFromId($id), $data);
       
    }


    public function deletePingTree($id) {

        if (is_numeric($id))
        {
            $database = T3Db::api();
            $database->delete("pingtrees", 'id = '. $database->quote($id));            
        }

        //  $this->system->deleteFile($this->treeFileNameFromId($id));
    }
    
    /**
    * Получеие количесва правил в которых используется PingTree с заданным ID
    * 
    * @param int $id
    * @return int
    */
    public function countAllocations($id){
        $id = (int)$id;
        return $this->database->fetchOne("select count(*) from pingtrees_allocation where pingtrees_allocation.scheme = '{$id}'");       
    }
}