<?php

abstract class T3PostingFile_CollectAbstract extends T3PostingFile_Abstract {

    /**
     * Результат работы скртипа
     * 
     * @var T3PostingFile_Result_Collect
     */
    public $result;

    /**
     * Флаг,уазывающий на постинг с пингом
     * 
     */
    protected $is_ping;
    protected $is_sendtest;

    /**
     * Функция вызывется до колекта в тех постингах где включен тест моде, в неё можно ставить код преобразования переменных  боди или других переменных окружения
     * 
     * @param mixed $testModeOptions
     */
    public function testMode($testModeOptions, $b) {
        /* Example

          // Edit Body
          $b->first_name = 'TestName';

          // Change Config Values (Send URL)
          $this->conf['send_URL'] = "https://domain.ltd/path?query";
         */
    }

    /**
     * Запуск основного скрипта конечного класса
     * 
     * @param T3Lead $lead
     * @param T3BuyerChannel $byuerChannel
     * 
     * 
     * @return T3PostingFile_Result_Collect $collectResult
     */
    public function run(T3Lead $lead, T3BuyerChannel $byuerChannel, $isTest = false) {
        $this->result = new T3PostingFile_Result_Collect();
        $this->is_sendtest = $isTest;
        if (!$isTest)
            $this->result->setEnviromentObjects($lead, $byuerChannel);

        if ($this->init($lead, $byuerChannel)) {
            if ($this->buyerChannel->testMode) {
                $this->testMode($this->buyerChannel->getTestModeOptions(), $this->lead->body);

                if ($this->lead->id != 999999) {
                    $emails = T3System::getValue("testModeEmailsSend");
                    if (is_array($emails) && count($emails)) {
                        /** @var T3Mail_Message */
                        $message = T3Mail::createMessage('runTestModePosting', array(
                                    'date' => DateFormat::main(date("Y-m-d H:i:s")),
                                    'posting' => T3Cache_BuyerChannel::get($byuerChannel->id, true),
                                    'lead' => "{$lead->getId} ({$lead->id})",
                        ));

                        foreach ($emails as $email) {
                            $message->SendMail($email);
                        }
                    }
                }
            }

            $this->result->status = T3POSTING_STATUS_GLOBAL_OK;
            $this->result->data = $this->RunWork($this->body);

            if ($this->is_ping === true) {
                $this->result->data_ping = $this->RunWork_ping($this->body);
            }
        }

        return $this->result;
    }

    /**
     * @param mix T3LeadBody_Abstract 
     * @return T3SendFunctionResult
     */
    //abstract protected function runWork(T3LeadBody_Abstract $b);

    /**
     * Преобразование массива в POST строку
     * $sendType = 'post'|'get'|null  По умолчанию пытается определить автоматически
     * 
     * @param array $array Data Array. Ex: array('a' => 1, 'b' => 2, 'c' => 3)
     * @return string POST Query String. Ex: a=1&b=2&c=3
     */
    protected function create_post_string($array, $sendType = null) {
        if (is_null($sendType)) {
            // автоматическое определение типа
            if (isset($this->conf['send_HTTP_SEND_TYPE']) && is_string($this->conf['send_HTTP_SEND_TYPE']) && $this->conf['send_HTTP_SEND_TYPE'] == 'get') {
                $sendType = 'get';
            } else {
                $sendType = 'post';
            }
        } else if ($sendType == 'post' || $sendType == 'get') {
            $this->conf['send_HTTP_SEND_TYPE'] = $sendType;
        }

        // передача в результат, типа строки
        if ($sendType == 'post') {
            $this->result->setDateType_POSTString();
        } else if ($sendType == 'get') {
            $this->result->setDateType_GETString();
        }

        // формирование строки
        $data = "";

        if (isset($array) && is_array($array) && count($array)) {
            foreach ($array as $var => $val) {
                if ((is_string($val) || is_numeric($val)) && strlen($val)) {
                    if (strlen($data))
                        $data.= "&";
                    $data.= "{$var}=" . urlencode($val);
                }
            }
        }
        return $data;
    }

    protected function create_post_string_blankvalue($array, $sendType = null) {
        if (is_null($sendType)) {
            // автоматическое определение типа
            if (isset($this->conf['send_HTTP_SEND_TYPE']) && is_string($this->conf['send_HTTP_SEND_TYPE']) && $this->conf['send_HTTP_SEND_TYPE'] == 'get') {
                $sendType = 'get';
            } else {
                $sendType = 'post';
            }
        } else if ($sendType == 'post' || $sendType == 'get') {
            $this->conf['send_HTTP_SEND_TYPE'] = $sendType;
        }

        // передача в результат, типа строки
        if ($sendType == 'post') {
            $this->result->setDateType_POSTString();
        } else if ($sendType == 'get') {
            $this->result->setDateType_GETString();
        }

        // формирование строки
        $data = "";

        if (isset($array) && is_array($array) && count($array)) {
            foreach ($array as $var => $val) {
                if (strlen($data))
                    $data.= "&";
                $data.= "{$var}=" . urlencode($val);
            }
        }
        return $data;
    }

    protected function create_post_string_rawurlencode($array, $sendType = null) {
        if (is_null($sendType)) {
            // автоматическое определение типа
            if (isset($this->conf['send_HTTP_SEND_TYPE']) && is_string($this->conf['send_HTTP_SEND_TYPE']) && $this->conf['send_HTTP_SEND_TYPE'] == 'get') {
                $sendType = 'get';
            } else {
                $sendType = 'post';
            }
        } else if ($sendType == 'post' || $sendType == 'get') {
            $this->conf['send_HTTP_SEND_TYPE'] = $sendType;
        }

        // передача в результат, типа строки
        if ($sendType == 'post') {
            $this->result->setDateType_POSTString();
        } else if ($sendType == 'get') {
            $this->result->setDateType_GETString();
        }

        // формирование строки
        $data = "";

        if (isset($array) && is_array($array) && count($array)) {
            foreach ($array as $var => $val) {
                if (strlen($data))
                    $data.= "&";
                $data.= "{$var}=" . rawurlencode($val);
            }
        }
        return $data;
    }

    protected function renderEmailDefaultContent($topText = null, $bottomText = null) {
        $array = array();
        foreach ($this->lead->getBody()->zendForm->getElements() as $element) {
            /** @var Zend_Form_Element */
            $element;

            $array[] = array($element->getLabel(), $this->lead->getBody()->getParam($element->getName()));
        }

        return $this->renderEmailContent($array, $topText, $bottomText);
    }

    protected function renderEmailContent($array, $topText = null, $bottomText = null) {
        $return = "{$topText} <table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";

        if (is_array($array) && count($array)) {
            foreach ($array as $el) {
                $return.= "<tr>";
                $return.= "<th style='padding:10px;white-space:nowrap;font-family:Verdana, Geneva, sans-serif;font-size:14px;border-bottom:#EEE solid 1px;font-weight:100;background:#FFF;color:#777;text-align:right;'>{$el[0]}:</th>";
                $return.= "<td style='padding:10px;white-space:nowrap;font-family:Verdana, Geneva, sans-serif;font-size:14px;border-bottom:#EEE solid 1px;text-align:left;font-weight:100;background:#FFF;color:#000'>{$el[1]}</td>";
                $return.= "</tr>";
            }
        }

        $return.= "</table> {$bottomText}";

        return $return;
    }

    /**
     * Преобразование массива в GET строку
     * отправка будет идти методом GET
     * 
     * @param array $array Data Array. Ex: array('a' => 1, 'b' => 2, 'c' => 3)
     * @return string POST Query String. Ex: a=1&b=2&c=3
     */
    protected function create_get_string($array) {
        return $this->create_post_string($array, 'get');
    }

    /**
     * Изменить тип отправки на POST (используется по умолчанию)
     * Только для Send Файла httpPOST
     */
    protected function setHttpPost_SendType_POST() {
        $this->conf['send_HTTP_SEND_TYPE'] = 'post';
    }

    /**
     * Изменить тип отправки на GET
     * Только для Send Файла httpPOST
     */
    protected function setHttpPost_SendType_GET() {
        $this->conf['send_HTTP_SEND_TYPE'] = 'get';
    }

    /**
     * Set ping limit to channel, limit period by default is 1 day
     * @param type $ping_limit

     */
    protected function setPingLimit($ping_limit) {
        $current_date = date("Y-m-d H:i:s");
        $channel_timezone = $this->buyerChannel->timezone;

        $channel_date = substr(TimeZoneTranslate::translate('pst', $channel_timezone, $current_date), 0, 10);
        $channel_id = $this->buyerChannel->id;
        
        $channel_daily_data = T3Db::api()->fetchRow("SELECT * FROM ping_count_log WHERE date_created = ? AND channel_id = ?", array($channel_date, $channel_id));
        
        if (isset($channel_daily_data['ping_count']) && ($channel_daily_data['ping_count'] >= $ping_limit)) {
            $this->exitPosting("Daily limit of {$ping_limit} pings exceeded.");
        } else {
            if (isset($channel_daily_data['ping_count']) && ($channel_daily_data['ping_count']) > 0) {
                T3Db::api()->update("ping_count_log", array(
                    "ping_count" => ($channel_daily_data['ping_count'] + 1)
                ), "`id` = " . $channel_daily_data['id']);
            } else {
                T3Db::api()->insert("ping_count_log", array(
                    "date_created" => $channel_date,
                    "channel_id" => $channel_id,
                    "ping_count" => "1"
                ));
            }
        }
    }

}