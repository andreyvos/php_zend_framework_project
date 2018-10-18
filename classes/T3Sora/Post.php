<?php

class T3Sora_Post {
    /**
     * ID сора лида
     * Присваивается при вызове функции post.
     * Каждая новая отправка формирует новый сора лид
     *
     * @var int
     */
    protected $soraLeadID = 0;

    ////////////////////////////////////////////////////////////////

    /**
     * Ссылка, на которую будет производиться отправка
     *
     * @var string
     */
    protected $url = 'http://sora.com/public/api/channel/redirect';

    /**
     * Таймаут запроса к соре в секундах
     *
     * @var int
     */
    protected $timeoutSeconds = 5;

    /**
     * Время потраченное на отправку запроса
     *
     * @var int
     */
    protected $runtime = 0;

    /**
     * Ключ партенра на соре
     *
     * @var string
     */
    protected $apiKey   = '2cpgveSTZ14rd8o';
    
     /**
     * Ключ партенра на соре for RU payday
     *
     * @var string
     */
    protected $apiKeyRu   = 'c3qILU0xYPrZ1jBQ37';

    /**
     * Продукт посылаемого клиента
     *
     * @var string
     */
    protected $product  = '';

    /**
     * Массив дополнительных параметров
     *
     * @var array
     */
    protected $params = array(
        'id'            => '',
        'source'        => '',
        'partner_sub'   => '',
    );

    protected $type = T3Sora_Types::TYPE_UNKNOWN;

    protected $minPrice = 0;

    ////////////////////////////////////////////////////////////////

    /**
     * Статус отправки
     *
     * @var int
     */
    protected $status = T3Sora_Statuses::STATUS_UNKNOWN;

    protected $price = null;

    /**
     * Текст ошибки
     *
     * @var string
     */
    protected $errorReason = "";

    /**
     * Заголовки запроса
     *
     * @var string
     */
    protected $request_headers  = '';

    /**
     * Запрос без заголовков
     *
     * @var string
     */
    protected $request  = '';

    /**
     * Ответ с заголовками
     *
     * @var string
     */
    protected $response = '';

    /**
     * Ответ без заголовков
     *
     * @var string
     */
    protected $return   = '';

    /**
     * Разобранный в SimpleXML ответ от сервера соры, если ответа не было или его не удалось разобрать, то переменная хранит null
     *
     * @var null|SimpleXMLElement
     */
    protected $returnSimpleXML;

    /**
     * Тело запроса
     *
     * @var string
     */
    protected $requestXML = '';

    ////////////////////////////////////////////////////////////////

    /**
     * Задать тип запроса, используется для статистики в т3лидс
     *
     * @param $type
     * @return $this
     */
    public function setType($type){
        $this->type = (int)$type;
        return $this;
    }

    /**
     * Задать параметр, отправлемый в запросе
     *
     * @param $name
     * @param $value
     * @return $this
     */
    public function setRequestParam($name, $value){
        $this->params[$name] = $value;
        return $this;
    }

    public function setMinPrice($price){
        $this->minPrice = round($price, 2);
    }

    /**
     * Установить API Key
     *
     * @param $apiKey
     * @return $this
     */
    public function setAPIKey($apiKey){
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Установить URL, на который будет отправлен запрос
     *
     * @param $url
     * @return $this
     */
    public function setRequestURL($url){
        $this->url = $url;
        return $this;
    }

    /**
     * Установить URL, на который будет отправлен запрос
     *
     * @param $subaccount
     * @return $this
     */
    public function setSubaccount($subaccount){
        $subaccount = substr(trim($subaccount), 0, 64);

        $type = (int)T3Db::api()->fetchOne(
            "SELECT `id` FROM `sora_subaccounts` WHERE `name`=?",
            $subaccount
        );

        if(!$type){
            try {
                T3Db::api()->insert('sora_subaccounts', array(
                    'name' => $subaccount
                ));

                $type = (int)T3Db::api()->lastInsertId();
            }
            catch(Exception $e){
                $type = (int)T3Db::api()->fetchOne(
                    "SELECT `id` FROM `sora_subaccounts` WHERE `name`=?",
                    $subaccount
                );
            }
        }

        $this->type = $type;

        $this->setRequestParam('partner_sub', $subaccount);
        return $this;
    }

    /**
     * Установить продукт
     * Он переустанавливается при формировании запроса, а значит и при вызове функции send(), т.к. она вызывает функцию формирования ответа
     * Установить можно только продукт из задонного списка, иначе будет вызванно исключение
     *
     * @param $product
     * @throws Exception
     * @return $this
     */
    protected function setProduct($product){
        $product = trim($product);

        if(!in_array($product, array(
            '',
            'us.loan.payday',
            'us.loan.mortgage.refinance',
            'us.insurance.auto',
            'us.insurance.home',
            'us.insurance.health',
            'us.insurance.life',
            'us.insurance.lite',
            'uk.loan.payday',
            'ca.loan.payday',
            'au.loan.payday',
        ))){
            throw new Exception("Invalid Product");
        }

        $this->product = $product;
        return $this;
    }

    ////////////////////////////////////////////////////////////////

    /**
     * Формирование XML запроса
     *
     * @param T3Lead $lead
     * @return string xml
     */
    protected function createRequest(T3Lead $lead){
        $this->setRequestParam('id',     $this->soraLeadID);
        $this->setRequestParam('source', $lead->affid);
        $this->setRequestParam('channel_id', $lead->channel_id);
        $this->setRequestParam('channel_title', T3Db::api()->fetchOne(
            "SELECT title FROM `channels` WHERE `id`=?", $lead->channel_id
        ));
        $this->setRequestParam('domain', (string)T3Db::api()->fetchOne(
            'SELECT `url_domain` FROM `channels_js_forms` WHERE id=?', $lead->channel_id
        ));
        $this->setRequestParam('status',  $lead->status);
        $this->setRequestParam('user_ip', AP_Http::get_ip_str($lead->ip_address));

        /*
        if(T3Products::getProduct($lead->product, 'solds_card')){
            $soldsCards = array();
            $soldPostings = T3Db::api()->fetchCol(
                "SELECT channel_id FROM `buyers_leads_sellings` WHERE `lead_id`=?",
                $lead->id
            );

            if(count($soldPostings)){
                $soldsCards = T3Db::api()->fetchAll(
                    "SELECT `logo`, `title`, `description` FROM `buyers_channels_sold_card` WHERE id in (" .
                    implode(",", $soldPostings) .
                    ")"
                );
            }

            $this->setRequestParam('solds_cards', json_encode($soldsCards, JSON_PRETTY_PRINT));
        }
        */

        $q = new SimpleXMLElement("<form></form>");

        $q->api_key             = ($lead->product == "rupayday") ? $this->apiKeyRu : $this->apiKey;
        $q->product             = $this->product;
        $q->min_price           = $this->minPrice;



        foreach($this->params as $k => $v){
            $q->params->$k = $v;
        }

        if($lead->product == 'payday'){
            /** @var T3LeadBody_PaydayLoan $b */

            $lead->getBodyFromDatabase();
            $b = $lead->getBody();

            $q->product             = "us.loan.payday";



            $q->client->requested_amount    = $b->requested_amount;
            $q->client->email               = $b->email;
            $q->client->home_phone          = $b->home_phone;
            $q->client->cell_phone          = $b->cell_phone;
            $q->client->work_phone          = $b->work_phone;
            $q->client->work_phone_ext      = $b->work_phone_ext;
            $q->client->first_name          = $b->first_name;
            $q->client->last_name           = $b->last_name;
            $q->client->state               = $b->state;
            $q->client->zip                 = $b->zip;
            $q->client->gender              = "";
            $q->client->city                = $b->city;
            $q->client->dob                 = $b->birth_date;
            $q->client->salary              = $b->monthly_income;
            $q->client->own_home            = $b->own_home;
            $q->client->employed            = $b->income_type == 'EMPLOYMENT' ? '1' : '0';
            $q->client->students            = '';
        }
        else if($lead->product == 'autotitlepaydayloan'){

            $lead->getBodyFromDatabase();
            $b = $lead->getBody();

            $q->product             = "us.loan.payday"; 

            $q->client->requested_amount    = $b->requested_amount;
            $q->client->email               = $b->email;
            $q->client->home_phone          = $b->home_phone;
            $q->client->cell_phone          = $b->cell_phone;
            $q->client->work_phone          = $b->work_phone;
            $q->client->first_name          = $b->first_name;
            $q->client->last_name           = $b->last_name;
            $q->client->state               = $b->state;
            $q->client->zip                 = $b->zip;
            $q->client->gender              = "";
            $q->client->city                = $b->city;
            $q->client->dob                 = $b->birth_date;
            $q->client->salary              = $b->monthly_income;
            $q->client->own_home            = $b->own_home;
            $q->client->employed            = $b->income_type == 'EMPLOYMENT' ? '1' : '0';
            $q->client->students            = '';
        }
        else if($lead->product == 'capayday'){
            $lead->getBodyFromDatabase();


            $b = $lead->getBody();

            $q->product             = "ca.loan.payday";

            $q->client->email           = $b->email;
            $q->client->home_phone      = '';
            $q->client->cell_phone      = '';
            $q->client->work_phone      = '';
            $q->client->work_phone_ext  = '';
            $q->client->first_name      = $b->first_name;
            $q->client->last_name       = $b->last_name;
            $q->client->state           = '';
            $q->client->zip             = '';
            $q->client->gender          = "";
            $q->client->city            = $b->city;
            $q->client->dob             = $b->birth_date;
            $q->client->salary          = $b->monthly_income;
            $q->client->own_home        = $b->own_home;
            $q->client->employed        = '';
            $q->client->students        = '';
        }
        else if($lead->product == 'auto_insurance_pingpost'){
            /** @var T3LeadBody_AutoInsurancePingPost $b */
            $lead->getBodyFromDatabase();

            $b = $lead->getBody();

            $q->product             = "us.insurance.auto";

            $q->client->email           = $b->email;
            $q->client->home_phone      = $b->home_phone;
            $q->client->cell_phone      = $b->cell_phone;
            $q->client->work_phone      = $b->work_phone;
            $q->client->work_phone_ext  = '';
            $q->client->first_name      = $b->first_name;
            $q->client->last_name       = $b->last_name;
            $q->client->state           = $b->state;
            $q->client->zip             = $b->zip;
            $q->client->gender          = $b->drv_gender == 'Male' ? 'male' : 'female';
            $q->client->city            = $b->city;
            $q->client->dob             = $b->drv_dob;
            $q->client->salary          = '';
            $q->client->own_home        = $b->own_home == 'Own' ? 1 : 0;
            $q->client->employed        = '';
            $q->client->students        = '';
        }
        else if($lead->product == 'mortgagerefinance'){
            /** @var T3LeadBody_MortgageRefinance $b */
            $lead->getBodyFromDatabase();

            $b = $lead->getBody();

            $q->product                 = "us.loan.mortgage.refinance";

            $q->client->email           = $b->email;
            $q->client->home_phone      = $b->primary_phone;
            $q->client->cell_phone      = '';
            $q->client->work_phone      = $b->secondary_phone;
            $q->client->work_phone_ext  = '';
            $q->client->first_name      = $b->first_name;
            $q->client->last_name       = $b->last_name;
            $q->client->state           = $b->state;
            $q->client->zip             = $b->zip;
            $q->client->gender          = "";
            $q->client->city            = $b->city;
            $q->client->dob             = "";
            $q->client->salary          = "";
            $q->client->own_home        = "";
            $q->client->employed        = '';
            $q->client->students        = '';
        }
        else if($lead->product == 'rupayday'){
            /** @var T3LeadBody_rupayday $b */
            $lead->getBodyFromDatabase();

            $b = $lead->getBody();

            $q->product                 = "ru.loan.payday";
            
        }
        else {
            $q->client->email           = $lead->data_email;
            $q->client->home_phone      = '';
            $q->client->cell_phone      = '';
            $q->client->work_phone      = '';
            $q->client->work_phone_ext  = '';
            $q->client->first_name      = "";
            $q->client->last_name       = "";
            $q->client->state           = $lead->data_state;
            $q->client->zip             = '';
            $q->client->gender          = "";
            $q->client->city            = $lead->data_city;
            $q->client->dob             = "";
            $q->client->salary          = "";
            $q->client->own_home        = "";
            $q->client->employed        = '';
            $q->client->students        = '';
        }

        if ($lead->product == "payday") {
            if ($lead->getRegisterValue('lendupreject') == 'true'){
                $q->client->pixellendup = 1;
            }else{
                $q->client->pixellendup = 0;
            }
        }
        $all = $lead->getBody()->getParams();
        if(is_array($all) && count($all)){
            foreach($all as $k => $v){
                $q->all->$k = $v;
            }
        }
        // $q->all = $lead->getBody()->getParams();
        if ($lead->product == "rupayday") {
            $outputXML = $q->asXML();
            $outputXML = str_replace('<?xml version="1.0"?>', '<?xml version="1.0" encoding="UTF-8"?>', $outputXML);
            return $outputXML;
        }
        return $q->asXML();
    }

    ////////////////////////////////////////////////////////////////

    /**
     * Указать ошибку, которая произошла в процессе отправки даннных или анализа ответа
     *
     * @param string $reason
     * @param int $status
     */
    protected function setPostError($reason, $status = T3Sora_Statuses::STATUS_ERROR){
        $this->errorReason  = (string)$reason;
        $this->status       = (int)$status;
    }

    /**
     * Отправить данные на Sora.com
     *
     * @param T3Lead $lead
     * @return bool true в случае если сора принела лид, false в случае ошибки или reject
     */
    public function send(T3Lead $lead){
        $this->status = T3Sora_Statuses::STATUS_PROCESS;
        $this->errorReason = '';
        $this->runtime = 0;
        $this->price = null;

        $this->request_headers  = '';
        $this->request  = '';
        $this->response = '';
        $this->return   = '';
        $this->returnSimpleXML = null;

        T3Db::api()->insert("sora_leads", array(
            'datetime'      => date('Y-m-d H:i:s'),
            'type'          => $this->type,
            'lead'          => $lead->id,
            'webmaster'     => $lead->affid,
            'agent_id'      => T3WebmasterCompanys::getAgentID($lead->affid),
            'status'        => $this->status,
            'product'       => T3Products::getID($lead->product),
            'channel'       => $lead->channel_id,
            'subaccount'    => $lead->subacc,
            'apikey'        => ($lead->product == "rupayday") ? $this->apiKeyRu : $this->apiKey,
        ));

        $this->soraLeadID = T3Db::api()->lastInsertId();

        $headers = array("Content-Type: text/xml; charset=utf8");
        $postString = $this->createRequest($lead);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            $this->url                      );
        curl_setopt($ch, CURLOPT_POST,           0                               );
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $postString                     );
        curl_setopt($ch, CURLOPT_FAILONERROR,    0                               );
        curl_setopt($ch, CURLOPT_HEADER,         1                               );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1                               );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false                           );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false                           );
        curl_setopt($ch, CURLOPT_TIMEOUT,        $this->getTimeoutSeconds()      );
        curl_setopt($ch, CURLINFO_HEADER_OUT,    true                            );
        curl_setopt($ch, CURLOPT_HTTP_VERSION,   CURL_HTTP_VERSION_1_1           );
        curl_setopt($ch, CURLOPT_ENCODING,       'gzip'                          );
        curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers                        );

        $this->response         = curl_exec($ch);
        $this->request_headers  = trim(curl_getinfo($ch, CURLINFO_HEADER_OUT));
        $this->request          = $postString;
        $this->runtime          = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 5);

        // анализ отправки
        if(curl_errno($ch) == "28"){
            // Превышен максимальный лимит ожидания ответа
            $curl_info = curl_getinfo($ch);
            $this->setPostError("(" . $curl_info['total_time'] . ") " . curl_error($ch), T3Sora_Statuses::STATUS_ERROR_SEND);
        }
        else if(curl_errno($ch)){
            // Другая ошибка
            $this->setPostError(curl_error($ch), T3Sora_Statuses::STATUS_ERROR_SEND);
        }
        else {
            // Если отправка произведена успешно, добавить информацию об этом в суммарный репорт
            $ak = ($lead->product == "rupayday") ? $this->apiKeyRu : $this->apiKey;
            T3Report_Summary::addSoraClient($lead, $ak);

            $this->status = T3Sora_Statuses::STATUS_OK;

            $header_size    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->return   = trim(substr($this->response, $header_size));

            $dom = new DOMDocument();
            if($dom->loadXML($this->return, LIBXML_NOERROR)){
                $this->returnSimpleXML = simplexml_import_dom($dom);

                $s = $this->returnSimpleXML->xpath("/result/status");

                if(isset($s[0])){
                    $s = (string)$s[0];

                    if($s == 'ok'){
                        $price = $this->returnSimpleXML->xpath("/result/price");
                        if(isset($price[0])){
                            $this->price = round(max(0, $price[0]));

                            if($this->price < $this->minPrice){
                                $this->setPostError('Invalid Price');
                            }
                        }

                        /*
                        $url = $rxml->xpath($xpathResultUrl);
                        if(isset($url[0])){
                            $resultURL = (string)$url[0];
                        }
                        */
                    }
                    else if($s == 'reject') {
                        $this->status = T3Sora_Statuses::STATUS_REJECT;

                        $r = $this->returnSimpleXML->xpath("/result/reason");
                        if(isset($r[0])){
                            $this->errorReason = (string)$r[0];
                        }
                    }
                    else if($s == 'error') {
                        $this->setPostError('Unnown Error');

                        $r = $this->returnSimpleXML->xpath("/result/reason");
                        if(isset($r[0])){
                            $this->errorReason = (string)$r[0];
                        }
                    }
                    else {
                        $this->setPostError('Invalid status', T3Sora_Statuses::STATUS_ERROR_PARSE);
                    }
                }
                else {
                    $this->setPostError('Element /result/status not found', T3Sora_Statuses::STATUS_ERROR_PARSE);
                }
            }
            else {
                $this->setPostError('Responce no valid XML Document', T3Sora_Statuses::STATUS_ERROR_PARSE);
            }
        }

        // обновление
        T3Db::api()->update("sora_leads", array(
            'status' => $this->status,
        ), "id={$this->soraLeadID}");

        // todo: realtime оплата

        // логи
        $request_body = $this->request;
        if ($lead->product == 'payday' || $lead->product == 'personalloan'){
            $ssn = ifset($lead->body->ssn);
            if (strlen($ssn)>0){
                $ssn_crypt = T3SSN::encrypt($ssn);
                $request_body = str_replace($lead->body->ssn,$ssn_crypt['hash'],$request_body);
            }
        }

        T3Db::api()->insert("sora_leads_send_log", array(
            'soralead'  => $this->soraLeadID,
            'postdata'  => date('Y-m-d H:i:s'),
            'runtime'   => round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 5),
            'status'    => $this->status,
            'reason'    => $this->errorReason,
            'request'   => $this->request_headers . "\r\n\r\n" . $request_body,
            'responce'  => $this->response,
        ));

        // для поиска
        T3Db::api()->insert("sora_search_index", array(
            't3lead'        => $lead->id,
            'slead'         => $this->soraLeadID,
            'email'         => $lead->data_email,
            'redirect_url'  => "",
        ));

        return $this->status == T3Sora_Statuses::STATUS_OK ? true : false;
    }

    ////////////////////////////////////////////////////////////////

    /**
     * Получить ответ, полученный от сервера
     * @param bool $headers
     * @return string
     */
    public function getResponse($headers = false){
        return $headers ? $this->response : $this->return;
    }

    /**
     * Получить статус отправки
     *
     * @return int
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * Причина того что данные не приняты
     *
     * @return string
     */
    public function getReason(){
        return $this->errorReason;
    }

    /**
     * Поулчить время, которое было потраченно на отправку запроса на сору
     *
     * @return float
     */
    public function getRuntime(){
        return $this->runtime;
    }

    /**
     * Поулчить url на который отправляются данные
     *
     * @return string
     */
    public function getRequestURL(){
        return $this->url;
    }

    /**
     * Поулчить заголовки запроса
     *
     * @return string
     */
    public function getRequestHeaders(){
        return $this->request_headers;
    }

    /**
     * Поулчить тело запроса
     *
     * @return string
     */
    public function getRequestBody(){
        return $this->request;
    }

    /**
     * Поулчить ограничение таймаута при запросе к соре в секундах
     *
     * @return string
     */
    public function getTimeoutSeconds(){
        return $this->timeoutSeconds;
    }

    /**
     * Функция получает значение одного (первого) узла, по заданному правилу
     *
     * @param $xpath
     * @return string|null
     */
    public function getResponseXPath($xpath){
        if($this->returnSimpleXML instanceof SimpleXMLElement){
            $r = $this->returnSimpleXML->xpath($xpath);

            if(isset($r[0])){
                return (string)$r[0];
            }
        }
        return null;
    }

    public function getPrice(){
        return $this->price;
    }
}