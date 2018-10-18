<?

class T3PriceRules {

    protected static $_instance;

    public $system;
    public $database;

    public $rules;

    protected function initialize() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
    }

    /**
    * @return T3PriceRules
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }

    /**
    * Поиск правила
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $buyerChannel
    * @param float $minPrice
    * @param bool $lazy
    * 
    * @return T3PriceRule
    */
    public static function getRule(T3Lead $lead, T3BuyerChannel $buyerChannel, $minPrice = 0, $lazy = true) {
        $registerRules = T3PriceRules::getInstance();

        if (isset($lead->id) && isset($buyerChannel->id)) {
            if (!$lazy || !isset($registerRules->rules[$lead->id][$buyerChannel->id])) {
                $registerRules->rules[$lead->id][$buyerChannel->id] = T3PriceRule::newObject()->searchRule($lead, $buyerChannel, $minPrice);
            }  

            return $registerRules->rules[$lead->id][$buyerChannel->id];
        }

        return false;
    }
    
    
    static $fixedWebmasterPrice = null;
    static public function setFixedWebmasterPrice($price){
        self::$fixedWebmasterPrice = $price;
    }
    
    static public function isFixedWebmasterPrice(){
        return (self::$fixedWebmasterPrice !== null);
    }
    
    static public function getFixedWebmasterPrice($total){
        return self::$fixedWebmasterPrice;
    }

    
    static protected $insurancesPriceGroups = array();
    
    /**
    * Поиск ценовой категории к которой принадлежит этот лид.
    * Ценовая категория может зависить от любого параметра из окрежения лида
    * Если ценовая группа не найдена то вернется 0
    * 
    * @param T3Lead $lead
    * @return int
    */
    static public function getInsurancePriceGroup(T3Lead $lead){
        if(!isset(self::$insurancesPriceGroups[$lead->id])){
            /**
            * На данный момент поиске идет с учетом:
            * 1. продукта 
            * 2. штата
            */ 
            self::$insurancesPriceGroups[$lead->id] = (int)T3Db::api()->fetchOne(
                "select id from prices_insurance_groups where " .
                "(product=0 or product=" . T3Products::getID($lead->product) . ") and " .
                "`state` like '%{$lead->data_state}%' " .
                "order by product desc limit 1"
            );
        }
        return self::$insurancesPriceGroups[$lead->id]; 
    }
    
    static public function getAllInsurancePricesGrpups($product){
        return T3Db::api()->fetchAll("select * from prices_insurance_groups where product=0 or product=" . (int)T3Products::getID($product) . "");
    }  
}


