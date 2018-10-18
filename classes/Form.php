<?php

class Form extends AP_Form {
    public function addElementProduct($name, $first = 'Select One...', $label = 'Product'){
        // Для баер агента, проверяем какие продукты для него доступны, и показываем только их.
        if(T3Users::getCUser()->isRoleBuyerAgent()){
            $prodTemp = T3Products::getProducts_MiniArray();
            $prodAgent = T3UserBuyerAgents::getProducts();
            $prod = array();
            foreach($prodTemp as $key => $val){
                if(in_array($key, $prodAgent)){
                    $prod[$key] = $val;
                }
            }
        }
        else {
            $prod = T3Products::getProducts_MiniArray();
        }

        if(strlen($first)) $prod = array('' => $first) + $prod;
        
        return $this->addElementSelect($name, $label, $prod);
    }

    public function addElement_Webmaster($name, $label = 'Webmaster', $required = true){
        $el = $this->addElementAutoComplite(new AutoComplite_Webmaster($name), $label);

        if(!$required){
            $el->setNotRequired();
        }

        return $el;
    }

    public function addElement_WebmasterAgent($name, $label = 'Agent', $required = true){
        $el = $this->addElementAutoComplite(new AutoComplite_WebmasterAgent($name), $label);

        if(!$required){
            $el->setNotRequired();
        }

        return $el;
    }
    
    public function addElement_WebmasterChannel($name, $label = 'Channel', $required = true, $relationsParams = array()){  
        $el = $this->addElementAutoComplite(new AutoComplite_WebmasterChannel($name, $relationsParams), $label);
        
        if(!$required){
            $el->setNotRequired();
        }
        
        return $el;
    }
    
    public function addElement_Buyer($name, $label = 'Buyer', $required = true){  
        $el = $this->addElementAutoComplite(new AutoComplite_Buyer($name), $label);
        
        if(!$required){
            $el->setNotRequired();
        }
        
        return $el;
    }

	public function addElement_User($name, $label = 'User', $required = true){
		$el = $this->addElementAutoComplite(new AutoComplite_User($name), $label);

		if(!$required){
			$el->setNotRequired();
		}

		return $el;
	}
    
    public function addElement_Posting($name, $label = 'Posting', $required = true){  
        $el = $this->addElementAutoComplite(new AutoComplite_Posting($name), $label);
        
        if(!$required){
            $el->setNotRequired();
        }
        
        return $el;
    }
    
    public function addElement_PostingProduct($name,$product,$agent = false, $label = 'Posting', $required = true){
    
        $auto_complite = new AutoComplite_PostingProduct($name);
        $el = $this->addElementAutoComplite($auto_complite, $label);
        
        if(!$required){
            $el->setNotRequired();
        }
        
        return $el;
    }
    public function addElement_BuyerAgent($name, $label = 'Agent', $required = true){
        $el = $this->addElementAutoComplite(new AutoComplite_BuyerAgent($name), $label);

        if(!$required){
            $el->setNotRequired();
        }

        return $el;
    }
}