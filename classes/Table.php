<?

class Table extends AP_Table {
    function addField_Webmaster($name, $label = 'Webmaster', $index = null){
        return $this->addField($name, $label, $index)
            ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_Webmaster')
        );
    }

    function addField_LeadID($name, $label = 'Lead', $index = null){
        return $this->addField($name, $label, $index)
            ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_LeadID')
        );
    }


    
    function addField_WebmasterChannel($name, $label = 'Channel', $index = null){
        return $this->addField($name, $label, $index)
        ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_WebmasterChannel')
        ); 
    } 
    
    /**
    * Продукт по ID
    * 
    * @param mixed $name
    * @param mixed $label
    * @param mixed $index
    * @return AP_Table_Field_Abstract
    */
    function addField_Product($name, $label = 'Product', $index = null){
        return $this->addField($name, $label, $index)
        ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_Product')
        ); 
    }
    
    /**
    * Продукт по NAME
    * 
    * @param mixed $name
    * @param mixed $label
    * @param mixed $index
    * @return AP_Table_Field_Abstract
    */
    function addField_ProductName($name, $label = 'Product', $index = null){
        return $this->addField($name, $label, $index)
        ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_ProductName')
        ); 
    }

    function addField_Posting($name, $label = 'Posting', $index = null){
        return $this->addField($name, $label, $index)
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_Posting')
            );
    }

    function addField_PostingSoldCard($name, $label = 'Sold Card', $index = 'sold_card'){
        return $this->addField($name, $label, $index)
            ->setStyleTH("width", "600px")
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_PostingSoldCardRender')
            );
    }

    function addField_PostingSoldCardIsSet($name, $label = '', $index = 'sold_card_is_set'){
        return $this->addField($name, $label, $index)
            ->setStyleTD("padding", "0px")
            ->setStyleTH("width", "1%")
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_PostingSoldCardIsSet')
            );
    }

    public function addField_CallQuality($name, $label = 'Quality', $index = null){
        return $this->addField($name, $label, $index)
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_Call')
            );
    }

    public function addField_PercentsLine($name,$label, $width = '200px',$index = null){

        return $this->addField($name, $label, $index)
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_Percent')
            );
    }

    public function addField_CallInfo($name, $label = 'Info', $index = null){
        return $this->addField($name, $label, $index)
            ->addDecorator(
                new AP_UI_Decorator_Cache('Cache_Info')
            );
    }
    
    public function addField_LinkAudio($name, $url, $title = null, $index = null){
        return $this->addField($name, $title, $index)->addDecorator("sprintf", array(
            AP_UI_Var::value("<a href=\"%s\" title='Play' class='sm2_button' type='audio/mpeg'>record</a>"),
            AP_UI_Var::value($url)
                ->addDecorator(new AP_UI_Decorator_Pattern())
                ->addDecorator("htmlspecialchars"),
            $name
        )); 
    }
    
    public function addField_LinkAudioDownload($name, $url, $title = null, $index = null){
        return $this->addField($name, $title, $index)->addDecorator("sprintf", array(
            AP_UI_Var::value("<a href=\"%s\">download</a>"),
            AP_UI_Var::value($url)
                ->addDecorator(new AP_UI_Decorator_Pattern())
                ->addDecorator("htmlspecialchars"),
            $name
        )); 
    }

    public function addField_LinkNewPage($name, $url, $title = null, $index = null){
        return $this->addField($name, $title, $index)->addDecorator("sprintf", array(
            AP_UI_Var::value("<a href=\"%s\" target='_blank'>%s</a>"),
            AP_UI_Var::value($url)
                ->addDecorator(new AP_UI_Decorator_Pattern())
                ->addDecorator("htmlspecialchars"),
            $name
        ));
    }
    
    function addField_Buyer($name, $label = 'Buyer', $index = null){
        return $this->addField($name, $label, $index)
        ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_Buyer')
        ); 
    }   
    
    function addField_Subaccount($name, $label = 'Subaccount', $index = null){
        return $this->addField($name, $label, $index)
        ->addDecorator(
            new AP_UI_Decorator_Cache('Cache_Subaccount')
        ); 
    }   
}