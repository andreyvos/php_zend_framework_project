<?php

abstract class T3Thank_AbstractBlock {
    protected $params = array();
    
    protected $cssArray = array();
    protected $jsArray  = array();
    protected $jsCodes  = array();
    
    protected $server = 'https://f.t3leads.com';
    
    public function getServer(){
        return isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) ? "https://{$_SERVER['HTTP_HOST']}" : "http://{$_SERVER['HTTP_HOST']}") : $this->server;
    }  
    
    protected function getResourcesRoot(){
        return $this->getServer() . "/system/thank.you.page/resources/";    
    } 
    
    /**
    * Добавить один или несколько JS файлов
    * Если добавить один файл больше одного раза, то вторая и последующие добавления будут проигнорированны
    * 
    * Варианты адресации:
    * 1. Абсолютная
    *    - Файл с другого домена Ex: `http://css.com/main.js`
    *    - Абсоляюный путь, относительно корня сайта. Ex: `/js/calendar.js`
    * 
    * 2. Относительная для библиотеки AP
    *  Можно указывать относительные пути, которые будет ссылатся на библиотеку AP. Корневая папка указывается в настройках.
    *  Ex: `js/jquery.flot.js`
    * 
    * @param string|array $files
    */
    public function addJS($files, $addToEnd = true){
        $this->jsArray = self::addFileAbstract($files, $this->jsArray, $this->getResourcesRoot() . "js/", $addToEnd);      
    }
    
    /**
    * Добавить JS код, который будет добавлен в HEAD и вызовется после загрузки страницы
    * 
    * @param mixed $code
    */
    public function addJSCode($code){
        $this->jsCodes[] = $code;   
    } 
    
    /**
    * Добавить один или несколько CSS файлов
    * Если добавить один файл больше одного раза, то вторая и последующие добавления будут проигнорированны
    * 
    * Варианты адресации:
    * 1. Абсолютная
    *    - Файл с другого домена Ex: `http://css.com/main.css`
    *    - Абсоляюный путь, относительно корня сайта. Ex: `/css/calendar.css`
    * 
    * 2. Относительная для библиотеки Thank You Pages (SEVER/system/)
    *  Можно указывать относительные пути, которые будет ссылатся на библиотеку AP. Корневая папка указывается в настройках.
    *  Ex: `js/jquery.flot.css`
    * 
    * @param string|array $files
    */
    public function addCSS($files, $addToEnd = true){
        $this->cssArray = self::addFileAbstract($files, $this->cssArray, $this->getResourcesRoot() . "css/", $addToEnd);      
    }
    
    protected function addFileAbstract($newFiles, $currentFiles, $root, $addToEnd){
        $addToStart = array();
        
        if(is_string($newFiles))$newFiles = array($newFiles);
        
        if(is_array($newFiles) && count($newFiles)){
            foreach($newFiles as $file){
                if(substr($file, 0, 7) != "http://" && substr($file, 0, 8) != "https://" && substr($file, 0, 1) != "/"){
                    $file = "{$root}{$file}";        
                } 
                if(substr($file, 0, 1) == "/"){
                    $file = $this->getServer() . "{$file}";    
                }
                if(strlen($file) && !in_array($file, $currentFiles)){
                    if($addToEnd){
                        $currentFiles[] = $file;
                    }
                    else {
                        $addToStart[] = $file;
                    }
                }   
            }
        } 
        
        // Если файлы надо было добавить в начало, то добавляем
        if(count($addToStart)){
            $currentFiles = array_merge($addToStart, $currentFiles);  
        }       
        
        return $currentFiles;    
    }
    
    public function renderCSS(){
        $data = "";
        if(count($this->cssArray)){
            foreach($this->cssArray as $file){
                if(is_string($file)){
                    $data.= "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$file}\" />";
                } 
            }
        }
        return $data;
    }
    
    public function renderJS($addJQuery = true){  
        $data = "";
        if(count($this->jsArray)){
            foreach($this->jsArray as $file){
                if(is_string($file)){
                    $data.= "<script type=\"text/javascript\" src=\"{$file}\"></script>";
                } 
            }
        }
        
        if(count($this->jsCodes)){
            $data.= "<script>jQuery(function(){\r\n";
            foreach($this->jsCodes as $code){
                $data.= "{$code}\r\n";    
            }
            $data.= "});</script>";    
        }
        return $data;
    }
    
    /*****************************************************************************************************/
    protected $params_name = 'abstract';
    
    public function setParams($params){
        if(is_array($params)){
            $this->params = $params;
            
            if(isset($this->params[$this->params_name]) && is_array($this->params[$this->params_name]) && count($this->params[$this->params_name])){
                foreach($this->params[$this->params_name] as $k => $v){
                    $this->params[$k] = $v;    
                }
            }
        }
        return $this;
    }
    
    protected function getParam($name, $default = null){
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }
    
    protected function getParamString($name, $default = null){
        return strlen(($str = (string)$this->getParam($name))) ? $str : (string)$default;
    }    
}