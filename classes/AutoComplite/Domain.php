<?php
class AutoComplite_Domain extends AP_UI_AutoComplete {
    protected $class = 'AutoComplite_Domain';
    
    static public function create($name)
    {
        return new self($name);
    } 
    
    public function init()
    { 
        /*******************   Настройка вывода списка   *********************/
        $this->list_value = AP_UI_Var::context('id');   
        $this->list_label = AP_UI_Var::value('@@domain@@')->addDecorator(
            new AP_UI_Decorator_Pattern()
        );
         
    }
    public function  getGoodValue($value)
    {
        return $value;
    }
    public function getList($value)
    {
        $value = $this->getGoodValue($value);
        $domains = file_get_contents("http://formsstats.t3leads.com/t3api.php?mode=domains&name=".urlencode($value));
        $domains = json_decode($domains, true);

        foreach($domains as $one)
        {
                $el['id'] = $one['_id']['domain'];
                $el['domain'] = $one['_id']['domain'];
                $result[] = array(
                    $this->list_value->setValues(null, $el)->render(),
                    $this->list_label->setValues(null, $el)->render(),
                );
        }
        return $result; 
    } 
    public function getValue($default = null){
        $main = $this->getInputValue($this->name, null);
        if(!is_null($main)){
            return $main;
        }

        $value = $this->getGoodValue($this->getInputValue($this->name . "_txt", null));
        if(!is_null($value)){
            return $value;
        }
        return $default;
    }  
    public function setValue($value)
    {

    }
}