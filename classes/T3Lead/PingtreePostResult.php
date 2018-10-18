<?

/**
* Результат работы может быть следующий:
* 1) Статус: 
*     Sold        -  продан
*     Reject      -  не продан (потомучто баеры не взяли)
*     Filtered    -  все отфильтровали
*     Duplicate   -  все уже видели, и по этой причине лид никому не отправлялся
*     NotPingTree -  не нашлось PingTree, при поиске PingTree для канала  
*     SystemError -  При работе скрипта произошли внутренние ошибки (например отсутсвует файл PingTree) 
*     Unknown     -  Другое (Когда все будет готово, такого статуса не будет)
* 
* Параметры:
* 1) Цена
* 2) Редирект линк
* 3) Комментарий 
*/

/**
* продан
*/
define("PostResult_PingTree_Sold",'Sold');

/**
* не продан (потомучто баеры не взяли) 
*/
define("PostResult_PingTree_Reject",'Reject');

/**
* все отфильтровали  
*/
define("PostResult_PingTree_Filtered",'Filtered');

/**
* все уже видели, и по этой причине лид никому не отправлялся  
*/
define("PostResult_PingTree_Duplicate",'Duplicate');

/**
* не нашлось PingTree, при поиске PingTree для канала
*/
define("PostResult_PingTree_NotPingTree",'NotPingTree');

/**
* При работе скрипта произошли внутренние ошибки (например отсутсвует файл PingTree) 
*/
define("PostResult_PingTree_SystemError",'SystemError');

/**
* Другое (Когда все будет готово, такого статуса не будет)  
*/
define("PostResult_PingTree_Unknown",'Unknown');



TableDescription::addTable('pingtrees', array(
    'status',
    'price',
    'redirectLink',
    'comment',                                               
));

class T3Lead_PingtreePostResult extends DbSerializable {
    /**
    * Стутус отправки по Pingtree
    * 
    * @var string
    */
    public $status;
    
    /**
    * Цена которую получил WebMaster за эту отправку
    * 
    * @var float
    */
    public $price = 0;
    
    
    /**
    * Цена за которую продался лид
    * 
    * @var float
    */
    public $totalPrice = 0;
    
    /**
    * Лид на который необходимо осуществить редирект
    * 
    * @var string
    */
    public $redirectLink;
    
    /**
    * Коментарий. например описание почему лид был Reject 
    * 
    * @var string
    */
    public $comment;
    
    /**
    * Массив объектов продажи каждому баеру из этого PingTree
    * 
    * @var array T3BuyerChannel_PostResult Objects
    */
    public $arrayPostResults = array();
    
    /**
    * Создать новый объект этого каласса
    * 
    * @return T3Lead_PingtreePostResult
    */
    static public function newObject(){
        $className = __CLASS__;
        return new $className();   
    }
    
    public function  __construct() {
        $this->status = PostResult_PingTree_Unknown;
        
        return $this;
    }
    
    /**
    * Лид продан
    * 
    * @param float $price
    * @param string $redirectLink
    */
    public function setSold($price,$redirectLink){
        $this->status = PostResult_PingTree_Sold;
        $this->price = $price;
        $this->redirectLink = $redirectLink;
        
        return $this;    
    }
    
    /**
    * Лид не продан
    * 
    * @param mixed $comment
    */
    public function setReject($comment = null){
        $this->status = PostResult_PingTree_Reject;
        $this->comment = $comment;
        
        return $this;     
    }
    
    /**
    * Все баеры отфильтровали
    */
    public function setFiltered(){
        $this->status = PostResult_PingTree_Filtered;
        
        return $this;  
    }
    
    /**
    * Не один баер не видел, по причине что все баеры выдели этот лид раньше
    */
    public function setDuplicate(){
        $this->status = PostResult_PingTree_Duplicate;
        
        return $this;   
    }
    
    /**
    * не нашлось PingTree, при поиске PingTree для канала 
    */
    public function setNotPingTree(){
        $this->status = PostResult_PingTree_NotPingTree;
        
        return $this;    
    }
    
    /**
    * При работе скрипта произошли внутренние ошибки (например отсутсвует файл PingTree)
    * 
    * @param mixed $comment
    */
    public function setSystemError($comment){
        $this->status = PostResult_PingTree_SystemError;
        $this->comment = $comment;
        
        return $this; 
    }
    
    /**
    * Добавление PostResult объекта
    * 
    * @param T3BuyerChannel_PostResult $postResult
    */
    public function addPostResult(T3BuyerChannel_PostResult $postResult){
        if(!is_null($postResult)){ 
            $this->arrayPostResults[] = $postResult->getParams(); 
        }       
    } 
    
    /**
    * Получить массив PostResults этой отправки
    * 
    * @return array T3BuyerChannel_PostResult
    */
    public function getPostResults(){
        return $this->arrayPostResults;
    }
    
    
    
    public function isSold(){
        return ($this->status == PostResult_PingTree_Sold);    
    }
    
    public function isReject(){
        return ($this->status == PostResult_PingTree_Reject);    
    }
    
    public function isFiltered(){
        return ($this->status == PostResult_PingTree_Filtered);    
    }
    
    public function isDuplicate(){
        return ($this->status == PostResult_PingTree_Duplicate);    
    }
    
    public function isNotPingTree(){
        return ($this->status == PostResult_PingTree_NotPingTree);    
    }
    
    public function isSystemError(){
        return ($this->status == PostResult_PingTree_SystemError);    
    }
    
    public function isUnknown(){
        return ($this->status == PostResult_PingTree_Unknown);    
    }
    
    
    
    public function getSoldPostingID(){
        if(count($this->arrayPostResults)){
            foreach($this->arrayPostResults as $el){
                if($el['status'] == PostResult_Buyer_Sold){
                    return $el['buyerChannelID'];   
                }    
            }
        }
        return null;
    }    
        
        
        
        
                

}