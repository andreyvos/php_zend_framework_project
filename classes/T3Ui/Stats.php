<?php

class T3Ui_Stats {
    public $summary = array();
    public $details = array();
    
    /**
    * @return T3Ui_Stats
    */
    static public function create(){
        return new T3Ui_Stats;   
    }
    
    /**
    * put your comment there...
    * 
    * @param mixed $symmary    
    * @param mixed $details
    * 
    * @return T3Ui_Stats
    */
    public function setData($summary, $details){
        $this->summary = $summary;    
        $this->details = $details;
        
        return $this;
    }
    
    public function render($type = 'default', $valueName = 'value', $valueTitle = 'Value', $options = null){
        MyZend_Site::addCSS(array(
            'tablesorter/style.css',
        ));
        
        MyZend_Site::addJS(array(
            'report/stats.report.js',
            'jquery.tablesorter.js',
        ));
        
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Stats");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_"); 
        
        $view->details = $this->details;
        $view->summary = $this->summary; 
        
        $view->valueName = $valueName; 
        $view->valueTitle = $valueTitle; 
        $view->options = $options;
        
        return $view->render("{$type}.phtml");    
    }    
}