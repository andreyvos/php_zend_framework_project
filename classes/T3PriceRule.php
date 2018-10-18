<?php

// Добавление описания таблицы
TableDescription::addTable('post_prices', array(
   'id',
   'buyerChannel',
   'channelType',
   'webmasterID',
   'channelID',
   'ruleType',
   'firstWM',
   'roundType',
   'roundMultiple',
));

class T3PriceRule extends DbSerializable {
    public $id = null;

    public $buyerChannel    = '0';
    public $channelType     = '*';
    public $webmasterID     = '0';
    public $channelID       = '0';

    /* Данные правила, после поиска по базе данных */
    public $ruleType = 'rate';
    public $firstWMAbstract = null;
    public $roundType = 'none';
    public $roundMultiple = '0.1';

    /* Данные после расчета */
    public $minConstPrice = 0;
    public $firstWM = 50;

    public $priceWM = 0;
    public $priceAgent = 0;
    public $priceReferal = 0;
    public $priceT3Leads = 0;
    public $priceExternal = 0;
    public $priceTTL = 0;

    /**
    * Включение различных игр с ценами для достижения наибольше прибольности при определенных условиях
    *
    * null      - цены назначаются жесткими правилами
    * byChannel - варьируемые цены на канал. Применяется когда на ServerPOST канал, на не нулевой минпрайс(ы) идет трафик с одного источника*
    *             Минпрайсы лучше выбирать так что бы они соотвествовали большим объемамот баеров на этой цене.
    *
    * *(один источник) - трафик с одной финансовой основой. Одна рекламная компания, один партнер... Нельзя включать игры на
    *                    трафик со смешанными источниками, поскольку часть денег с одного лида может пойти за другой. А если
    *                    у них разные владельцы, то это приведет к перетеканию финансов.
    *
    * @var null|string
    */
    public $pricesGame = null; // null, byChannel

    /**
    * Переменные по которым проходит игра.
    *
    * @var mixed
    */
    public $pricesGameOptions = array();

    private $lead = null;
    private $buyerChannelObject = null;

    private $cache_array = array();

    public function getCache(){
        return $this->cache_array;
    }

    public function  __construct() {
        if (!isset($this->className))$this->className = __CLASS__;
        parent::__construct();
        $this->tables = array('post_prices');
    }

    public function setTestLeadAndPosting($webmasterID = null){
        $this->lead = new T3Lead();
        $this->lead->id = 999;

        if($webmasterID){
            $this->lead->affid = $webmasterID;
            $this->lead->fillAgentAndCalculatePercents();
            $this->lead->fillReferalAndCalculatePercents();
        }

        $this->buyerChannelObject = new T3BuyerChannel();
        $this->buyerChannelObject->id = 1;
    }

    /**
    * @return T3PriceRule
    */
    static public function newObject() {
        $class = __CLASS__;
        return new $class();
    }

    private function clear() {
        $this->id = null;

        $this->lead = null;
        $this->buyerChannel = null;
        $this->cache_array = array();

        $this->ruleType         =  'rate';
        $this->firstWMAbstract  =  null;
        $this->roundType        =  'none';
        $this->roundMultiple    =  '0.1';

        $this->minConstPrice = 0;
        $this->firstWM = 50;

        $this->priceWM = 0;
        $this->priceAgent = 0;
        $this->priceReferal = 0;
        $this->priceT3Leads = 0;
        $this->priceTTL = 0;
    }


    static protected $cacheChannelGameSettings = array();
    static protected $cacheFormsGameSettings = array();
    static protected $cacheMainSettings = array();


    /**
    * put your comment there...
    *
    * @param T3Lead $lead
    * @param T3BuyerChannel $buyerChannel
    *
    * @return T3PriceRule
    */
    public function searchRule(T3Lead $lead, T3BuyerChannel $buyerChannel, $minPrice = 0) {
        $this->clear();
        $methodFinded = false;

        if(!$methodFinded){
            /**
            * Уникальное задание цены для продуктов по страховке
            * Отключены для пейдеев что бы не создаать лишние запросы
            */
            if ($lead->product != 'payday' && $lead->product != 'ukpayday'){
                $groupID = T3PriceRules::getInsurancePriceGroup($lead);

                $fixedPrice = T3Db::api()->fetchOne("select price from prices_insurance where product=" . T3Products::getID($lead->product) . " and " .
                "(webmaster=0 or webmaster={$lead->affid}) and " .
                "(`group`=0 or `group`={$groupID}) and " .
                "(state='*' or state='" . $lead->data_state . "') and " .
                "(zip=0 or zip=" . (int)ifset($lead->getBodyFromDatabase()->zip) . ") " .
                "order by webmaster desc, zip desc, state desc, `group` desc limit 100");

                if($fixedPrice !== false){
                    $methodFinded = true;
                    $this->pricesGame = 'fixWebmasterIfSold';

                    $this->ruleType         =  'const';
                    $this->firstWMAbstract  =  $fixedPrice;
                    $this->roundType        =  'none';
                    $this->roundMultiple    =  '0.1';
                }
            }
            else{
                $fixedPrice = false;
            }
        }


        if(!$methodFinded && $buyerChannel->priceGame){
            /**
            * Channel Game - Игры с ценами на определенном канале, настраивается только на Server POST каналы,
            * потому что на каналах с нулевым минпрайсом это игра не имеет смысла.
            */
            if($lead->get_method == 'post_channel'){
                // кеширование получения настроек прайс гейма для канала
                if(!isset(self::$cacheChannelGameSettings[$lead->channel_id])){
                    self::$cacheChannelGameSettings[$lead->channel_id] = T3Db::api()->fetchRow(
                        "select * from prices_games_by_channel where channel_id=? and `status`='active' limit 1", $lead->channel_id
                    );
                }
                $channelGame = self::$cacheChannelGameSettings[$lead->channel_id];

                if($channelGame){
                    $methodFinded = true;
                    $this->pricesGame = 'byChannel';
                    $this->pricesGameOptions['data'] = $channelGame;

                    /*
                        Схема откругления минпрайсов:
                        (Округление всегда идет в большую сторону, шаг зависит от цены)
                        (Продажа будет проходить по округленному минпрайсу)
                        10+     = 1
                        5 - 10  = 0.5
                        2 - 5   = 0.25
                        0 - 2   = 0.1
                    */
                    /*
                    if($minPrice < 2)       $this->pricesGameOptions['minPrice'] = ceil($minPrice * 10) / 10;
                    else if($minPrice < 5)  $this->pricesGameOptions['minPrice'] = ceil($minPrice * 4 ) / 4;
                    else if($minPrice < 10) $this->pricesGameOptions['minPrice'] = ceil($minPrice * 2 ) / 2;
                    else                    $this->pricesGameOptions['minPrice'] = ceil($minPrice);
                    */

                    $this->pricesGameOptions['minPrice'] = $minPrice;

                    $this->pricesGameOptions['data']['prices'] = unserialize($this->pricesGameOptions['data']['prices']);
                    if(!is_array($this->pricesGameOptions['data']['prices'])){
                        $this->pricesGameOptions['data']['prices'] = array(array('minprice', 1));
                    }

                    $this->ruleType         =  'round';
                    $this->firstWMAbstract  =  $this->pricesGameOptions['data']['wmPercent'];
                    $this->roundType        =  'none';
                    $this->roundMultiple    =  'none';
                }
            }
        }

        if(!$methodFinded && $buyerChannel->priceGame){
            /**
            * Forms Game - Игры с ценами на лидах с форм для Product+Webmaster
            * Основная цель такой игры - показать большие цены. Для этого некоторые цены расчитываются по заниженному коэфиценту, а некоторые наоборот по завышенному.
            */
            if($lead->get_method == 'js_form' && $minPrice == 0){
                // кеширование получения настроек прайс гейма для форм
                if(!isset(self::$cacheFormsGameSettings["{$lead->affid}_{$buyerChannel->product}"])){
                    self::$cacheFormsGameSettings["{$lead->affid}_{$buyerChannel->product}"] = T3Db::api()->fetchRow(
                        "select * from prices_games_by_forms where webmaster=? and product=? and `status`='active' limit 1",
                        array($lead->affid, $buyerChannel->product)
                    );
                }
                $formsGame = self::$cacheFormsGameSettings["{$lead->affid}_{$buyerChannel->product}"];

                if($formsGame){
                    $methodFinded = true;
                    $this->pricesGame = 'byForms';
                    $this->pricesGameOptions['data'] = $formsGame;
                    $this->pricesGameOptions['minPrice'] = $minPrice;

                    $this->ruleType         =  'round';
                    $this->firstWMAbstract  =  $this->pricesGameOptions['data']['wmPercent'];
                    $this->roundType        =  'none';
                    $this->roundMultiple    =  'none';
                }
            }
        }

        if(!$methodFinded){
            // Стандартный метод, жестких цен

            // кеширование получения настроек
            if(!isset(self::$cacheMainSettings["{$lead->id}_{$buyerChannel->id}"])){
                self::$cacheMainSettings["{$lead->id}_{$buyerChannel->id}"] = T3Db::api()->fetchRow(
                    "select id,ruleType,firstWM,roundType,roundMultiple from post_prices where " .
                    "(buyerChannel='*' or buyerChannel='{$buyerChannel->id}') and " .
                    "(channelType='*' or channelType='{$lead->get_method}') and " .
                    "(webmasterID='0' or webmasterID='{$lead->affid}') and " .
                    "(leadProduct='*' or leadProduct='{$buyerChannel->product}') and " .
                    "(channelID='0' or channelID='{$lead->channel_id}') and " .
                    "(priceMin<={$buyerChannel->minConstPrice} and priceMax>={$buyerChannel->minConstPrice}) " .
                    "order by buyerChannel desc,leadProduct desc,channelID desc,webmasterID desc,channelType desc,(priceMax-priceMin) asc limit 1"
                );
            }
            $row = self::$cacheMainSettings["{$lead->id}_{$buyerChannel->id}"];

            if ($row !== false) {
                $methodFinded = true;
                $this->id               =  $row['id'];
                $this->ruleType         =  $row['ruleType'];
                $this->firstWMAbstract  =  $row['firstWM'];
                $this->roundType        =  $row['roundType'];
                $this->roundMultiple    =  $row['roundMultiple'];
            }
        }

        if($methodFinded){
            // Определение переменных по которым в дальнейшем будет проверятся загружено правило или нет. при текущей политике не может быть такого что правило не найдется
            // Потому что в стндартном методе есть настройки по умолчанию для всех продуктов, эта настройка имеет наименьший приоритет и включается в самом конце если других настроек не найдено.
            $this->lead                 = $lead;
            $this->buyerChannelObject   = $buyerChannel;
            $this->buyerChannel         = $buyerChannel->id;
        }

        return $this;
    }

    /**
    * округление $value типом $roundType до кратного $roundMultiple
    * ВАЖНО: moneyRound(X) = -moneyRound(-X)
    *
    * @param float|integer $value
    * @param string $roundType - ceil,floor,round
    * @param float $roundMultiple
    */
    private function moneyRound($value, $roundType = 'ceil', $roundMultiple = 0.01) {
        if ($roundType == "ceil" || $roundType == "floor" || $roundType == "round") {
            $roundMultiple = round($roundMultiple,2);
            if ($roundMultiple<=0)$roundMultiple = 0.01;

            if ($value>0) return $roundType($value/$roundMultiple)*$roundMultiple;
            else          return -$roundType(abs($value)/$roundMultiple)*$roundMultiple;
        } else {
            return round($value,2);
        }
    }

    public function getSearchResult(){
        return array(
            'id'                => $this->id,
            'ruleType'          => $this->ruleType,
            'firstWMAbstract'   => $this->firstWMAbstract,
            'roundType'         => $this->roundType,
            'roundMultiple'     => $this->roundMultiple,
        );
    }

    /**
    * Получение объекта канала баера
    *
    * @param mixed $lazy
    * @return T3BuyerChannel
    */
    public function getBuyerChannelObject($lazy = true){
        if($lazy && is_null($this->buyerChannelObject) && $this->buyerChannel){
            $this->buyerChannelObject = new T3BuyerChannel();
            $this->buyerChannelObject->fromDatabase($this->buyerChannel);
        }

        return $this->buyerChannelObject;
    }

    static public function getDefaultRoundMultiple($webmasterPrice){
        /**
        *   0 - 0.2 = 0.01   .. - 20
        * 0.2 - 0.5 = 0.05    4 - 10
        * 0.5 - 1   = 0.1     5 - 10
        *   1 - 2   = 0.25    4 - 8
        *   2 - 4   = 0.5     4 - 8
        *   4 - ... = 1       4 - ..
        */

        $steps = array(
            0.2     => 0.01,
            0.5     => 0.05,
            1       => 0.1,
            2       => 0.25,
            4       => 0.5,
        );

        foreach($steps as $max => $value){
            if($webmasterPrice <= $max){
                return $value;
            }
        }

        return 1;
    }



    protected $calcMaxPrice = null;
    /**
    * Устанвоить максимальную цену для вебмастра
    * @param mixed $maxPrice
    */
    protected function setCalcMaxPrice($maxPrice){
        $this->calcMaxPrice = $maxPrice;
    }

    protected $calcRoundType = 'floor';
    /**
    * Устанвоить максимальную цену для вебмастра
    * @param string $maxPrice
    */
    protected function setCalcRoundType($roundType){
        $this->calcRoundType = $roundType;
    }

    /**
    * Расчитвать первичную цену, цену без округления и конечную цену для определенного процента
    *
    * @param float $wmKoef
    * @param null|float $maxWM
    * @return array((float)$first, (float)$price_nr, (float)$price)
    */
    protected function calcPotencialAmount($wmPercent, $maxWM = null){
        if(is_null($maxWM)){
            if(!is_null($this->calcMaxPrice)){
                $maxWM = $this->calcMaxPrice;
            }
        }

        $first    = round($this->priceTTL * ($wmPercent/100), 2);

        $price_nr = round(
            $first
            - round($first * $this->lead->agentWMProcent / 100, 2)
            - round($first * $this->lead->refWMProcent/100, 2)
        , 2);

        $price    = $this->moneyRound(
            $price_nr,
            $this->calcRoundType,
            self::getDefaultRoundMultiple($price_nr)
        );

        if(!is_null($maxWM) && $price > $maxWM){
            $first_delta    = $first - $price;
            $price_nr_delta = $price_nr - $price;

            $price      = round($maxWM, 2);
            $first      = round($maxWM + $first_delta, 2);
            $price_nr   = round($maxWM + $price_nr_delta, 2);
        }

        return array(
            'first' => $first,
            'nr'    => $price_nr,
            'price' => $price ,
            'delta' => round($price_nr - $price, 2)
        );
    }

    /**
    * Выбрать из массиа цен одну цену, которыую можно примернить в заданном диапозоне цен
    *
    * @param float $min
    * @param float $max
    */
    protected function getOnePriceInArray($min, $max){
        if(
            isset($this->pricesGameOptions['data']['prices']) &&
            is_array($this->pricesGameOptions['data']['prices']) &&
            count($this->pricesGameOptions['data']['prices'])
        ){
            $prices = array();
            $total = 0;

            foreach($this->pricesGameOptions['data']['prices'] as $el){
                if($el[0] == 'minprice') $el[0] = max($min, 2);

                if($el[0] >= $min && $el[0] <= $max){
                    $total+= $el[1];
                    $prices[] = $el;
                }
            }
            //$prices = array_reverse($prices);

            if($total > 0){

                $rand = rand(1, $total);
                $min  = 1;


                foreach($prices as $el){
                    if($rand >= $min && $rand < $min+$el[1]){
                        return $el[0];
                    }
                    $min+= $el[1];
                }
            }
        }

        return $min;
    }

    /**
    * Вычисление сумм для пользователей на основе текущего правила
    *
    * @param mixed $totalPrice
    * @param mixed $lazy
    * @param bool $gameComplite - вызывается перед расчетами по оплате, для того что бы игры могли записать свои результаты в таблицы
    *
    * @return T3PriceRule
    */
    public function runProcents($totalPrice = null, $lazy = true, $gameComplite = false, $externalCommissions = 0) {
        // определение начальной цены
        if (is_null($totalPrice) || $totalPrice < 0)    $totalPrice = $this->getBuyerChannelObject()->minConstPrice;
        else                                             $totalPrice = (float)$totalPrice;

        $totalPrice = round((float)$totalPrice, 2);

        if (!$lazy || !isset($this->cache_array[(string)$totalPrice])) {
            if (!is_null($this->lead) && !is_null($this->buyerChannel)) {
                $this->priceExternal = round($totalPrice * $externalCommissions, 2);


                if($this->pricesGame == 'byChannel' && !T3PriceRules::isFixedWebmasterPrice() && $this->getBuyerChannelObject()->priceGame){
                    ////////////// Установить некоторые параметры  //////////////////////////////////////////////
                    $this->setCalcRoundType('floor');                                                           // Тип округления
                    $this->setCalcMaxPrice(ifset($this->pricesGameOptions['data']['webmasterMaxPrice'], 115));  // Потолок цены
                    $this->priceTTL  = $totalPrice = round($totalPrice - $this->priceExternal, 2);              // Цена баера (с корекцией для FirstLook схемы)
                    ///////////////////////////////////////////////////////////////////////////////////////////
                    $gameType = null;
                    $gameOpt =& $this->pricesGameOptions['data'];

                    // Цены по стандартному проценту
                    $realPrice = $this->calcPotencialAmount($gameOpt['wmPercent']);

                    /***************************************************************************************
                    * 1. (Расход) Большая цена
                    * - процент включения
                    * - подходит по цене баера
                    * - банк
                    * - подходит по minprice
                    */
                    if(
                        // тип игры еще не выбран
                        $gameType === null &&

                        // процент включения
                        rand(1,100) <= ifset($gameOpt['highPricePercents'], 25) &&

                        // подходит по цене баера
                        $totalPrice >= $gameOpt['minHighPrice'] &&

                        // Банк подходит, в зависимости от настройки
                        (
                            (   // Банк больше или равен максимуму
                                $gameOpt['bankTypeForHighPrice'] == 'max' &&
                                $gameOpt['current_bank'] >= $gameOpt['bank_max']
                            )
                            ||
                            (   // Банк больше минимума
                                $gameOpt['bankTypeForHighPrice'] == 'min' &&
                                $gameOpt['current_bank'] >  $gameOpt['bank_min']
                            )
                        )
                    ){
                        // расчитываем проценты
                        $potencialPrice = $this->calcPotencialAmount($gameOpt['highMaxCoefficient']);
                        if($potencialPrice['price'] >= $this->pricesGameOptions['minPrice']){
                            // Если подходит под минпрайс, то... ту-ду !!!
                            $gameType = 1;
                        }
                    }

                    /***************************************************************************************
                    * 2. (Расход) Неподходит по минпрайсу
                    * - процент включения (всегда 100%)
                    * - банк
                    * - не подходит под стандатный процент
                    * - ну подходит по Lower Procent ценам
                    */
                    if(
                        // тип игры еще не выбран
                        $gameType === null &&

                        // Банк больше минимума
                        $gameOpt['current_bank'] > $gameOpt['bank_min'] &&

                        // не подходит под стандатный процент
                        $realPrice['price'] < $this->pricesGameOptions['minPrice']
                    ){
                        // расчитываем lower
                        $lower = $this->calcPotencialAmount($gameOpt['lowerMaxCoefficient']);

                        if($lower['price'] >= $this->pricesGameOptions['minPrice']){
                            /**
                            * Если подходит под минпрайс, подбираем цену
                            * Цена может быть в промежутке:
                            * минимальная:  $this->pricesGameOptions['minPrice']
                            * максимальная: $lower['price']
                            *
                            * Выбираем подходящую цену из массива цен
                            * Расчитываем проценты от этой цены
                            */
                            $onePrice = $this->getOnePriceInArray($this->pricesGameOptions['minPrice'], $lower['price']);
                            $potencialPrice = $this->calcPotencialAmount($gameOpt['lowerMaxCoefficient'], $onePrice);
                            $gameType = 2;
                        }
                    }

                    /***************************************************************************************
                    * 3. (Пополнение) Режем цены
                    * - процент включения
                    * - реальная цена больше 2$
                    * - подходит по стандартному проценту
                    * - банк
                    *
                    */
                    if(
                        // тип игры еще не выбран
                        $gameType === null &&

                        // процент включения
                        rand(1,100) <= ifset($gameOpt['probabilityCutToMinprice'], 100) &&

                        // реальная цена больше 2$
                        $realPrice['price'] > 2 &&

                        // подходит по стандартному проценту
                        $realPrice['price'] >= $this->pricesGameOptions['minPrice'] &&

                        // банк меньше максимума
                        $gameOpt['current_bank'] < $gameOpt['bank_max']
                    ){
                        /**
                        * можно обрезать цену
                        *
                        * минимальная цена:  $this->pricesGameOptions['minPrice']
                        * максимальная цена: $realPrice['price']
                        */
                        $onePrice = $this->getOnePriceInArray($this->pricesGameOptions['minPrice'], $realPrice['price']);
                        $potencialPrice = $this->calcPotencialAmount($gameOpt['wmPercent'], $onePrice);
                        $gameType = 3;
                    }

                    /***************************************************************************************
                    * 4. Стандартный процент
                    */
                    if($gameType === null){
                        // Просто забиваем стандартный процент
                        $potencialPrice = $realPrice;
                        $gameType = 4;
                    }

                    // Стандартные расчеты из полученных данных
                    $this->firstWM    =   $potencialPrice['first'];
                    $this->priceWM    =   $potencialPrice['price'];

                    $this->priceAgent = round(
                        round($this->firstWM * ($this->lead->agentWMProcent / 100), 2) +
                        round(($totalPrice - $this->firstWM) * ($this->lead->agentADMProcent / 100), 2)
                    , 2);

                    $this->priceReferal = round(
                        round($this->firstWM * ($this->lead->refWMProcent / 100), 2) +
                        round(($totalPrice - $this->firstWM) * ($this->lead->refADMProcent / 100), 2)
                    , 2);

                    $this->priceT3Leads     =   round($this->priceTTL - $this->priceAgent - $this->priceReferal - $this->priceWM, 2);

                    // возвращение первочиной кореции для схемы FirstLook
                    $this->priceTTL  = $totalPrice = round($totalPrice + $this->priceExternal, 2);

                    $this->cache_array[(string)$totalPrice] = array(

                        'priceTTL'      =>  $this->priceTTL,
                        'priceAgent'    =>  $this->priceAgent,
                        'priceReferal'  =>  $this->priceReferal,
                        'priceWM'       =>  $this->priceWM,
                        'priceT3Leads'  =>  $this->priceT3Leads,
                        'priceExternal' =>  $this->priceExternal,

                        // раздница между ценой которую вебмастр должен был получить по реальному проценту и тому что получит по игре
                        'deltaWMPrice'  =>  round($realPrice['price'] - $potencialPrice['price'], 2),

                        // остаток от округления, который надо положить в банк
                        'roundingToBank' =>  ($this->pricesGameOptions['data']['roundingToBank']) ? $potencialPrice['delta'] : 0,

                        /**
                        * Сценарий ингры, который был применени в данном случае
                        *
                        * 1 - High
                        * 2 - Lower
                        * 3 - Cut
                        * 4 - Standart
                        *
                        */
                        'gameType'      =>  $gameType,

                        'realPrice'     => $realPrice['price'],
                    );
                }
                else if($this->pricesGame == 'byForms' && !T3PriceRules::isFixedWebmasterPrice() && $this->getBuyerChannelObject()->priceGame){
                    // корекция цены баера для 3-й стороны
                    $totalPrice = round($totalPrice - $this->priceExternal, 2);

                    // Для этой игры выбирается свое условие округления.
                    $gameRoundType = 'floor';

                    $roundingAmount = 0;
                    $gameAmount = 0;

                    $potencialFirst = round($totalPrice*$this->firstWMAbstract/100, 2);
                    $potencialRealPriceNotRound = round($potencialFirst - round($potencialFirst*$this->lead->agentWMProcent/100, 2) - round($potencialFirst*$this->lead->refWMProcent/100, 2), 2);
                    $potencialRealPrice = $this->moneyRound($potencialRealPriceNotRound, $gameRoundType, self::getDefaultRoundMultiple($potencialRealPriceNotRound));


                    if(
                        // Условия пополнений банка (Lower Percent)
                        $this->pricesGameOptions['data']['current_bank'] < $this->pricesGameOptions['data']['bank_max'] &&
                        $totalPrice >= $this->pricesGameOptions['data']['addingMinPrice'] &&
                        $totalPrice <= $this->pricesGameOptions['data']['addingMaxPrice'] &&
                        rand(1,100) <= $this->pricesGameOptions['data']['addingProbabilityPercent']
                    ){
                        $nowPercent = $this->pricesGameOptions['data']['addingPubliserPercent'];
                        $this->firstWM = round($totalPrice*$nowPercent/100, 2);
                        $notRoundPriceWM = round($this->firstWM - round($this->firstWM*$this->lead->agentWMProcent/100, 2) - round($this->firstWM*$this->lead->refWMProcent/100, 2), 2);
                        $this->priceWM = $this->moneyRound($notRoundPriceWM, $gameRoundType, self::getDefaultRoundMultiple($notRoundPriceWM));
                    }
                    else if(
                        // Условия выдачи HighPrice
                        $this->pricesGameOptions['data']['current_bank'] > $this->pricesGameOptions['data']['bank_min'] &&
                        $totalPrice >= $this->pricesGameOptions['data']['highPriceMinBuyerValue'] &&
                        rand(1,100) <= $this->pricesGameOptions['data']['highPriceProbabiliyPercents']
                    ){
                        $nowPercent = $this->pricesGameOptions['data']['highMaxCoefficient'];
                        $this->firstWM = round($totalPrice*$nowPercent/100, 2);
                        $notRoundPriceWM = round($this->firstWM - round($this->firstWM*$this->lead->agentWMProcent/100, 2) - round($this->firstWM*$this->lead->refWMProcent/100, 2), 2);
                        $this->priceWM = $this->moneyRound($notRoundPriceWM, $gameRoundType, self::getDefaultRoundMultiple($notRoundPriceWM));

                        // Потолок максимальнгй цены
                        $webmasterMaxPrice = ifset($this->pricesGameOptions['data']['webmasterMaxPrice'], 115);
                        if($this->priceWM > $webmasterMaxPrice){
                            $this->firstWM      = $webmasterMaxPrice;
                            $notRoundPriceWM    = $webmasterMaxPrice;
                            $this->priceWM      = $webmasterMaxPrice;
                        }
                    }
                    else {  // Стандартный процент
                        $nowPercent = $this->pricesGameOptions['data']['wmPercent'];
                        $this->firstWM = $potencialFirst;
                        $notRoundPriceWM = $potencialRealPriceNotRound;
                        $this->priceWM = $potencialRealPrice;
                    }

                    // корекция цены баера для 3-й стороны
                    $totalPrice = round($totalPrice + $this->priceExternal, 2);

                    // Стандартные расчеты из полученных данных
                    $this->priceTTL         =   $totalPrice;
                    $this->priceAgent       =   round(round($this->firstWM*$this->lead->agentWMProcent/100, 2) + round(($totalPrice-$this->firstWM)*$this->lead->agentADMProcent/100, 2), 2);
                    $this->priceReferal     =   round(round($this->firstWM*$this->lead->refWMProcent/100, 2) + round(($totalPrice-$this->firstWM)*$this->lead->refADMProcent/100, 2), 2);
                    $this->priceT3Leads     =   round($this->priceTTL - $this->priceAgent - $this->priceReferal - $this->priceWM, 2);

                    $this->cache_array[(string)$totalPrice] = array(
                        'priceTTL'          =>  $this->priceTTL,
                        'priceAgent'        =>  $this->priceAgent,
                        'priceReferal'      =>  $this->priceReferal,
                        'priceWM'           =>  $this->priceWM,
                        'priceT3Leads'      =>  $this->priceT3Leads,
                        'priceExternal'     =>  $this->priceExternal,
                        'deltaWMPrice'      =>  round($potencialRealPrice - $this->priceWM, 2),
                        'roundingAmount'    =>  round($potencialRealPrice - $potencialRealPriceNotRound, 2), // На сколько изменена цена при округлении. Если с 91.5 изменена на 91, то amount = -0.5
                    );
                }
                else if($this->pricesGame == 'fixWebmasterIfSold'){
                    $this->firstWM = $this->firstWMAbstract;

                    // Если есть хардкод по цене, то даем вебмастру столько сколькл захардкодано
                    if(T3PriceRules::isFixedWebmasterPrice()){
                        $this->firstWM = T3PriceRules::getFixedWebmasterPrice($totalPrice);
                    }

                    $this->priceTTL         =   $totalPrice;
                    $this->priceAgent       =   0;
                    $this->priceReferal     =   0;
                    $this->priceWM          =   $this->firstWM;
                    $this->priceT3Leads     =   round($this->priceTTL - $this->priceAgent - $this->priceReferal - $this->priceWM, 2);

                    $this->cache_array[(string)$totalPrice] = array(
                        'priceTTL'      =>  $this->priceTTL,
                        'priceAgent'    =>  $this->priceAgent,
                        'priceReferal'  =>  $this->priceReferal,
                        'priceWM'       =>  $this->priceWM,
                        'priceT3Leads'  =>  $this->priceT3Leads,
                        'priceExternal' =>  $this->priceExternal,
                    );
                }
                else {
                    // определение начального процента вебмастера, без учета вычетов для агента и по реф программе
                    if ($this->ruleType == 'const')  $this->firstWM = $this->firstWMAbstract * (1 - $externalCommissions);
                    else                             $this->firstWM = round(($totalPrice - $this->priceExternal) * $this->firstWMAbstract / 100,2);

                    // Если есть хардкод по цене, то даем вебмастру столько сколькл захардкодано
                    if(T3PriceRules::isFixedWebmasterPrice()){
                        $this->firstWM = T3PriceRules::getFixedWebmasterPrice($totalPrice);
                    }


                    $agentWMprice = 0;
                    $agentADMprice = 0;
                    $refWMprice = 0;
                    $refADMprice = 0;

                    // проценты агента
                    if ($this->lead->agentID) {
                        $agentWMprice = round($this->firstWM*$this->lead->agentWMProcent/100,2);
                        $agentADMprice = round(($totalPrice-$this->firstWM)*$this->lead->agentADMProcent/100,2);

                        if($this->lead->product == 'auto_insurance'){
                            $agentWMprice = '0';
                            $agentADMprice = '0';
                        }
                    }

                    // проценты реферала
                    if ($this->lead->refaffid) {
                        $refWMprice = round($this->firstWM*$this->lead->refWMProcent/100,2);
                        $refADMprice = round(($totalPrice-$this->firstWM)*$this->lead->refADMProcent/100,2);
                    }

                    $this->priceTTL         =   $totalPrice;
                    $this->priceAgent       =   round($agentWMprice + $agentADMprice, 2);
                    $this->priceReferal     =   round($refWMprice + $refADMprice, 2);
                    $this->priceWM          =   $this->moneyRound($this->firstWM - $agentWMprice - $refWMprice, $this->roundType, $this->roundMultiple);
                    $this->priceT3Leads     =   round($this->priceTTL - $this->priceAgent - $this->priceReferal - $this->priceWM, 2);

                    $this->cache_array[(string)$totalPrice] = array(
                        'priceTTL'      =>  $this->priceTTL,
                        'priceAgent'    =>  $this->priceAgent,
                        'priceReferal'  =>  $this->priceReferal,
                        'priceWM'       =>  $this->priceWM,
                        'priceT3Leads'  =>  $this->priceT3Leads,
                        'priceExternal' =>  $this->priceExternal,
                    );
                }
            }
        }
        else {
            $this->priceTTL         =   $this->cache_array[(string)$totalPrice]['priceTTL'];
            $this->priceExternal    =   $this->cache_array[(string)$totalPrice]['priceExternal'];
            $this->priceAgent       =   $this->cache_array[(string)$totalPrice]['priceAgent'];
            $this->priceReferal     =   $this->cache_array[(string)$totalPrice]['priceReferal'];
            $this->priceWM          =   $this->cache_array[(string)$totalPrice]['priceWM'];
            $this->priceT3Leads     =   $this->cache_array[(string)$totalPrice]['priceT3Leads'];
        }

        // Если по лидам по которым идет игра цен происходит оплата, то необходимо что то записать в результирующие таблицы
        if($gameComplite && !T3PriceRules::isFixedWebmasterPrice()){
            if($this->pricesGame == 'byChannel' && !T3PriceRules::isFixedWebmasterPrice() && $this->getBuyerChannelObject()->priceGame){
                $amount = round($this->cache_array[(string)$totalPrice]['deltaWMPrice'] + $this->cache_array[(string)$totalPrice]['roundingToBank'], 2);

                // Запись в лог
                if($amount!=0 || $this->priceWM!=0 || $this->priceTTL!=0 || $this->cache_array[(string)$totalPrice]['realPrice']!=0){
                    T3Db::api()->insert("prices_games_by_channel_log", array(
                        'datetime'              => date("Y-m-d H:i:s"),
                        'channel'               => $this->lead->channel_id,
                        'type'                  => $this->cache_array[(string)$totalPrice]['gameType'],
                        'wm'                    => $this->priceWM,
                        't3'                    => $this->priceT3Leads,
                        'ttl'                   => $this->priceTTL,
                        'min_price'             => $this->pricesGameOptions['minPrice'],
                        'rounding_to_bank'      => $this->cache_array[(string)$totalPrice]['roundingToBank'],
                        'delta_price_to_bank'   => $this->cache_array[(string)$totalPrice]['deltaWMPrice'],
                        'amount'                => $amount,
                        'real_price'            => $this->cache_array[(string)$totalPrice]['realPrice']
                    ));
                }

                // Изменение банка
                if($amount != 0){
                    T3Db::api()->update("prices_games_by_channel", array(
                        'current_bank' => new Zend_Db_Expr("round(current_bank+{$amount},2)")
                    ), "channel_id='{$this->lead->channel_id}'");
                }
            }
            else if($this->pricesGame == 'byForms' && !T3PriceRules::isFixedWebmasterPrice() && $this->getBuyerChannelObject()->priceGame){
                $amount = 0;

                if($this->cache_array[(string)$totalPrice]['deltaWMPrice'] != 0){
                    T3Db::api()->insert("prices_games_by_forms_log", array(
                        'datetime'  => date("Y-m-d H:i:s"),
                        'webmaster' => $this->lead->affid,
                        'product'   => $this->buyerChannelObject->product,
                        'wm'        => $this->priceWM,
                        't3'        => $this->priceT3Leads,
                        'ttl'       => $this->priceTTL,
                        'amount'    => $this->cache_array[(string)$totalPrice]['deltaWMPrice'],
                    ));
                    $amount+= $this->cache_array[(string)$totalPrice]['deltaWMPrice'];
                }


                if($this->pricesGameOptions['data']['roundingToBank']){
                    $roundAmount = round(0-$this->cache_array[(string)$totalPrice]['roundingAmount'], 2);

                    if($roundAmount != 0){
                        T3Db::api()->insert("prices_games_by_forms_rounding_log", array(
                            'webmaster'     => $this->lead->affid,
                            'product'       => $this->buyerChannelObject->product,
                            'create_date'   => date("Y-m-d H:i:s"),
                            'amount'        => $roundAmount,
                        ));
                        $amount+= $roundAmount;
                    }
                }

                $amount = round($amount, 2);

                if($amount != 0){
                    T3Db::api()->update("prices_games_by_forms", array(
                        'current_bank' => new Zend_Db_Expr("round(current_bank+{$amount},2)")
                    ), "id='{$this->pricesGameOptions['data']['id']}'");
                }
            }
        }

        return $this;
    }

    /**
    * @return T3Lead
    */
    public function getLead(){
        return $this->lead;
    }

}
