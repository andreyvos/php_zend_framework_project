<?php

abstract class T3PostingFile_AnalysisAbstract extends T3PostingFile_Abstract {
    
    /**
    * Результат работы скртипа
    * 
    * @var T3PostingFile_Result_Analysis
    */
    protected $result;
    
    
    /**
    * Запуск основного скрипта конечного класса
    * 
    * @param T3Lead $lead
    * @param T3BuyerChannel $byuerChannel
    * @param T3PostingFile_Result_Send $sendResult 
    */
    public function run(T3Lead $lead, T3BuyerChannel $byuerChannel, T3PostingFile_Result_Send $sendResult, $isTest = false){
        $this->result = new T3PostingFile_Result_Analysis();
        /*if(!$isTest)*/$this->result->setEnviromentObjects($lead, $byuerChannel);
        
        if($this->init($lead,$byuerChannel)){
            $this->RunWork($sendResult);
        } 
        
        return $this->result; 
    }
    
    protected function addReason($text){
        if(strlen(trim($text))){
            T3BuyerChannel_Reasons::add(trim($text), $this->lead->id, $this->buyerChannel->id);     
        }    
    }
    
    /**
    * @param T3PostingFile_Result_Send $sendResult 
    * @return T3PostingFile_Result_Analysis
    */
    abstract protected function runWork(T3PostingFile_Result_Send $sendResult);
    
    /**
    * Создание объекта из XML документа и проверка в нем наличие обязательных элементов  
    * 
    * @param string $XMLDocumentString XML Document
    * @param array $requireElements
    */
    protected function XMLStringToObject(&$XMLDocumentString){
        if(isset($XMLDocumentString)){
            $dom = @DOMDocument::loadXML($XMLDocumentString);
            if($dom){
                return simplexml_import_dom($dom);
            }
            else {
                $this->result->setAnalysisParserError("Parser Error: Responce no valid XML Document");    
            }
              
        }
        return null;
    }
    
    
   private function recursiveHelper(&$nodeList, &$xpath, $currentPath){       
       
        for($x=0; $x < $nodeList->length; $x++ ){
            if( $xpath == $currentPath . '/' . $nodeList->item($x)->nodeName ){
                return $nodeList->item($x)->nodeValue;      
            }
        }
         
        $finalAnswer = false;         
        for($x=0; $x < $nodeList->length; $x++ )
            if( $nodeList->item($x)->hasChildNodes()  ){    
                $answer = $this->recursiveHelper( $nodeList->item($x)->childNodes, $xpath, $currentPath . '/' .  $nodeList->item($x)->nodeName );
                if( $answer !== false ) $finalAnswer = $answer;
            }
                  
        return $finalAnswer;             
             
    }

    protected function beginParse(&$xml, &$xpath){
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($xml);    
        
        $rootName = '/' . $xmlDoc->nodeName;    
                  
        if( $xpath == $rootName ){                                                 
            return (string)$xmlDoc->nodeValue;
        }                                            
        else if( $xmlDoc->hasChildNodes() ){     
            return $this->recursiveHelper($xmlDoc->childNodes, $xpath, '' ); 
        }    
        else {
            return false; 
        }  

    }
 
    protected function XMLStringToArray(&$XMLDocumentString,$options){
        if(isset($XMLDocumentString)){
            $dom = @DOMDocument::loadXML($XMLDocumentString);
            if($dom){
                $xml = simplexml_import_dom($dom);
                $result = array();
                //$DOMXPath = new DOMXPath($dom);   
                
                foreach($options as $key => $option){
                    if(isset($option['xpath'])){                            
                        //$newSearch = $DOMXPath->query($option['xpath']);
                        //varExport($newSearch->length);
                        
                        $searchValue = $this->beginParse( $XMLDocumentString , $option['xpath'] );                       
                        
                        //varExport($searchValue);
                        
                        if(is_bool($searchValue) && $searchValue == false){
                            if(!isset($option['require']))$option['require'] = true;
                            
                            if($option['require'] === false || $option['require'] === 0){
                                $searchValue = ifset($option['default'], null);    
                            }
                            else {
                                $this->result->setAnalysisLogicError("Not found XML Elements: {$option['xpath']}");
                                return null;
                            }    
                        }
                        // Преобразование переменных
                        $result[$key] = (string)$searchValue;
                    }      
                }
                
                return $result;
            }
            else {
                $this->result->setAnalysisParserError("Parser Error: Responce no valid XML Document");    
            }
              
        }
        return null; 
    }
    
    
}