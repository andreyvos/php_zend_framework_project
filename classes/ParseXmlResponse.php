<?php
  class ParseXmlResponse {
      
      public function XMLStringToObject(&$XMLDocumentString){
        if(isset($XMLDocumentString)){
            $dom = @DOMDocument::loadXML($XMLDocumentString);
            if($dom){
                return simplexml_import_dom($dom);
            }
            else {
                return false;    
            }
              
        }
        return null;
    }
    
    public function recursiveHelper(&$nodeList, &$xpath, $currentPath){       
       
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
    
    public function beginParse(&$xml, &$xpath){
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
    
    public function XMLStringToArray(&$XMLDocumentString,$options){
        if(isset($XMLDocumentString)){
            $dom = @DOMDocument::loadXML($XMLDocumentString);
            if($dom){
                $xml = simplexml_import_dom($dom);
                $result = array();   
                
                foreach($options as $key => $option){
                    if(isset($option['xpath'])){                            
                        
                        $searchValue = $this->beginParse( $XMLDocumentString , $option['xpath'] );                       
                        
                        if(is_bool($searchValue) && $searchValue == false){
                            if(!isset($option['require']))$option['require'] = true;
                            
                            if($option['require'] === false || $option['require'] === 0){
                                $searchValue = ifset($option['default'], null);    
                            }
                            else {
                               return false;
                            }    
                        }
                        $result[$key] = (string)$searchValue;
                    }      
                }
                
                return $result;
            }
            else {
                return false;    
            }
              
        }
        return null; 
    }
    
    
  }
?>
