<?php

class T3Sora_Main {
    /**
     * @var T3Sora_Post|null
     */
    static protected $post;

    /**
     * Получить объект последнего запроса к соре
     *
     * @return T3Sora_Post|null
     */
    static public function getLastPostObject(){
        return self::$post;
    }

    /**
     * Получение ссылки для thank you page монетизации с форм
     *
     * @param T3Lead $lead
     * @return string|null URL (Example: http://sora.com/public/api/channel/client?transit=519ca16dd865c)
     */
    static public function getLinkForFormsThankYouPage(T3Lead $lead){
        self::$post = new T3Sora_Post();

        self::$post->setSubaccount('LinkForFormsThankYouPage');

        if(self::$post->send($lead) && strlen($url = self::$post->getResponseXPath("/result/transit"))){
            return $url;
        }

        return "https://www.ameriadvance.com/";
    }

    /**
     * Получение ссылки для thank you page монетизации с server post каналов
     *
     * @param T3Lead $lead
     * @return string|null URL (Example: http://sora.com/public/api/channel/client?transit=519ca16dd865c)
     */
    static public function getLinkForServerPostThankYouPage(T3Lead $lead){
        self::$post = new T3Sora_Post();

        self::$post->setSubaccount('LinkForServerPostThankYouPage');

        if(self::$post->send($lead) && strlen($url = self::$post->getResponseXPath("/result/transit"))){
            return $url;
        }

        return "https://www.ameriadvance.com/";
    }

    /**
     * Получение ссылки редиректа клиента, который пришел на фид, но система считает что он не будет продан
     * Если ссылка не может быть получена, тогда функция вернет null
     *
     * @param T3Lead $lead
     * @return string|null URL (Example: http://sora.com/public/api/channel/client?transit=519ca16dd865c)
     */
    static public function getLinkForRepeatedClient(T3Lead $lead){
        self::$post = new T3Sora_Post();

        self::$post->setSubaccount('LinkForRepeatedClient');

        if(self::$post->send($lead) && strlen($url = self::$post->getResponseXPath("/result/transit"))){
            return $url;
        }

        return null;
    }

    /**
     * Получение JavaScript кода:
     * 1. вставдять на все страницы фида, на котором нужен данный тип монетизации
     * 2. при закрытии страницы, клиенту показывается виджет с соры
     *
     * @param T3Lead $lead
     * @return string javascript code
     */
    static public function getJavaScriptForClosePopup(T3Lead $lead){
        return "";

        /*
        self::$post = new T3Sora_Post();

        self::$post->setSubaccount('FeedClosePopup');

        if(self::$post->send($lead) && strlen($url = self::$post->getResponseXPath("/result/popup_transit"))){
            return file_get_contents($url);
        }

        return "";
        */
    }

    /**
     * Отправка данных о payday клиенте, для дальнейшей его монетизации как payday клиента
     *
     * @param T3Lead $lead
     * @param T3Lead_PingtreePostResult $postResult
     * @return bool показатель что клиент будет монетизирован на продует payday
     */
    static public function emailPaydayOffers(T3Lead $lead, T3Lead_PingtreePostResult $postResult){
        self::$post = new T3Sora_Post();

        self::$post->setRequestURL("http://sora.com/public/api/channel/mailer");
        self::$post->setSubaccount('emailUsPaydayOffers');

        self::$post->setRequestParam("type",      'UsPaydayOffers');
        self::$post->setRequestParam("key",       T3Lead_NoSensitiveInfoAPI::getKey($lead));
        self::$post->setRequestParam("status",    $postResult->isSold() ? 'sold' : 'reject');
        self::$post->setRequestParam("price",     $postResult->totalPrice);

        return self::$post->send($lead);
    }

    /**
     * Отправка лида на монетизацию по emails
     *
     * @param T3Lead $lead
     * @param T3Lead_PingtreePostResult $postResult
     * @return T3Sora_Post
     */
    static public function emailAllOffers(T3Lead $lead){
        self::$post = new T3Sora_Post();

        self::$post->setRequestURL("http://sora.com/public/api/channel/mailer");
        self::$post->setSubaccount('emailAllOffers');

        self::$post->send($lead);

        return self::$post;
    }

    /**
     * Отправка лида на монетизацию по emails
     *
     * @param T3Lead $lead
     * @param T3Lead_PingtreePostResult $postResult
     * @return T3Sora_Post
     */
    static public function postingSend(T3Lead $lead, T3BuyerChannel $posting){
        self::$post = new T3Sora_Post();

        self::$post->setSubaccount('Posting_' . $posting->id);
        self::$post->setMinPrice($posting->minConstPrice);

        self::$post->send($lead);

        return self::$post;
    }
}