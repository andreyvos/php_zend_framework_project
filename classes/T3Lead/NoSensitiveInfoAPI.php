<?php

class T3Lead_NoSensitiveInfoAPI {

    /**
     * Получить ключ лида, по которому можно загрузить no sensitive information
     *
     * @param T3Lead $lead
     * @return string ключ вида string-int4-int4-int4
     */
    static public function getKey(T3Lead $lead){
        return AP_IdEncryptor::encode($lead->id) . "-" . self::getKeyHash($lead);
    }

    /**
     * Сгенерировать постоянный ключ лида int-int-int
     *
     * @param T3Lead $lead
     * @return string int-int-int
     */
    static protected function getKeyHash(T3Lead $lead){
        return implode("-", array(
            strtotime($lead->datetime),
            crc32($lead->datetime . $lead->data_email . $lead->data_ssn),
            crc32($lead->num . $lead->affid)
        ));
    }

    /**
     * Получение данных лида по ключу
     *
     * @param $key
     * @return array
     */
    static protected function getInfoArray($key){
        $result = array();

        $key = explode("-", $key, 2);
        if(count($key) != 2){
            // ключ не в формате CryptLeadID-KeyHash
            return $result;
        }

        $leadID = AP_IdEncryptor::decode($key[0]);
        if(!$leadID){
            // нет id лида
            return $result;
        }

        $lead = new T3Lead();
        $lead->fromDatabase($leadID);
        if(!$lead->id){
            // не найден лид
            return $result;
        }

        if(self::getKeyHash($lead) != $key[1]){
            // не правильный KeyHash
            return $result;
        }

        // формирование информации
        $lead->getBodyFromDatabase();

        // настрйока отдаваемого массива
        $result = $lead->getBody()->getParams();
        unset($result['id']);

        return $result;
    }

    /**
     * Получить имя клиента по ключу
     *
     * @param $key
     * @return array (name: string or null)
     */
    static public function getName($key){
        $result = self::getInfoArray($key);

        if(count($result)){
            return array(
                'name'      => $result['first_name'] . " " . $result['last_name'],
                'email'     => $result['email'],
                'ssn'       => substr($result['ssn'], 0, 5),
                'timezone'  => (isset($result['state']) ? AP_Geo::getTimeZone_FromState($result['state'], null) : null),
            );
        }

        return array(
            'name'      => null,
            'email'     => null,
            'ssn'       => null,
            'timezone'  => null,
        );
    }

    /**
     * Получение данных по Payday USA
     *
     * @param $key
     * @param $ssn
     *
     * @return array
     */
    static public function getArray_PaydayUS($key, $ssn){
        $result = self::getInfoArray($key);

        if(count($result)){
            if($result['ssn'] == trim($ssn)){
                // удаление секретной информации (для отправки дополнительного лида, её надо будет вводить заново)
                unset(
                    $result['ssn']
                    /*$result['bank_account_number']*/
                );
            }
            else {
                // неверный ssn, не отдавать данные
                $result = array();
            }
        }

        return $result;
    }
}