<?php

abstract class T3PostingFile_SendAbstract extends T3PostingFile_Abstract {
    
    /**
    * Результат работы скртипа
    * 
    * @var T3PostingFile_Result_Send
    */
    protected $result;
    
    /**
    * Запуск основного скрипта конечного класса
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $byuerChannel
    * @param mix $collectResult
    */
    public function run(T3Lead $lead, T3BuyerChannel $byuerChannel, T3PostingFile_Result_Collect $collectResult, $isTest = false){
        $this->result = new T3PostingFile_Result_Send();
        if(!$isTest)$this->result->setEnviromentObjects($lead, $byuerChannel);
        
        if($this->init($lead,$byuerChannel)){
            $this->RunWork($collectResult);
        }  
        
        return $this->result;
    }
    
    /**
    * @param mix $collectResult 
    * @return T3SendFunctionResult
    */
    abstract protected function runWork(T3PostingFile_Result_Collect $collectResult);
    
    
    /**
    * Добавление лога
    * 
    * @param mixed $request
    * @param mixed $request_type
    * @param mixed $responce
    */
    protected function addLog($request, $responce){
        $this->result->log[] = array(
            'request'  => array('object' => $request),
            'response' => array('object' => $responce),
        );    
    }

}