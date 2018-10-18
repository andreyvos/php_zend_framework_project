<?php

class T3Ui_WebmasterList {

    protected $index;

    public $type;
    public $data;
    public $action;

    public $default = array(
        'type'   => 'one',
        'data'   => '',
        'action' => 'include',
    );

    public $values;

    static protected $instance = array();
    /**
    * Получить копию объекта под определенным индексом
    *
    * @param string $index
    * @return T3Ui_WebmasterList
    */
    static public function getInstance($index = 'default'){
        if(!isset(self::$instance[$index])){
            self::$instance[$index] = new self($index);
        }
        return self::$instance[$index];
    }

    public function __construct($index){
        $this->index = $index;
    }

    public function run(){
        if(!$this->values){
            $type = $this->getValue('type');
            if(in_array($type, array('one', 'list'))){
                $this->type = $type;
            }
            else{
                $this->type = $this->default['type'];
            }

            $action = $this->getValue('action');
            if(in_array($action, array('include', 'exclude'))){
                $this->action = $action;
            }
            else {
                $this->action = $this->default['action'];
            }

            if($type == 'one'){
                $this->data = $this->getValue("dataOne");
            }
            else if($type == 'list'){
                $this->data = $this->getValue("dataList");
            }
        }
        return $this->values;
    }

    public function getPrefix(){
        return 'wmList_' . $this->index . '_';
    }

    protected function getValue($name){
        return ifset(
            $_POST[$this->getPrefix() . $name],
            ifset(
                $_GET[$this->getPrefix() . $name],
                ifset(
                    $this->default[$name],
                    null
                )
            )
        );
    }

    public function getWebmasters(){
        $result = array();

        if(T3Users::getCUser()->isRoleWebmaster()){
            $result[] = T3Users::getCUser()->company_id;
        }
        else {
            $a = explode(",", $this->data);

            if(count($a)){
                foreach($a as $wm){
                    $wm = trim($wm);
                    if(is_numeric($wm)){
                        if(T3Users::getCUser()->isRoleAdmin() || T3Users::getCUser()->isRoleBuyerAgent() || T3Users_AgentManagers::isPubManager() || T3Users_AgentManagers::isWebmasterAgentManager()){
                            if(T3WebmasterCompanys::isWebmaster($wm)){
                                $result[] = $wm;
                            }
                        }
                        else if(T3Users::getCUser()->isRoleWebmasterAgent()){
                            if(T3WebmasterCompanys::isWebmaster($wm, T3Users::getCUser()->id)){
                                $result[] = $wm;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getLists(){
        $lists = array();
        $lists[''] = "Select One...";

        $a = T3Db::api()->fetchAll("select `data`, `title` from lists_webmasters where `user`=?", T3Users::getCUser()->id);

        $isThisData = false;
        if($this->data == ''){
            $isThisData = true;
        }

        if(is_array($a) && count($a)){
            foreach($a as $el){
                if($el['data'] != ''){
                    $a = explode(",", $el['data']);

                    $lists[$el['data']] = $el['title'] . " (" . count($a) . ")";
                    if($el['data'] == $this->data)$isThisData = true;
                }
            }
        }

        if(!$isThisData){
            $lists[$this->data] = "Unknown List";
        }

        return $lists;
    }

    public function render(){
        $this->run();

        MyZend_Site::addCSS('ui/wm-list.css');
        MyZend_Site::addJS('ui/wm-list.js');

        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "WebmasterList");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_");

        $view->prefix = $this->getPrefix();
        $view->index = $this->index;

        $view->type     = $this->type;
        $view->data     = $this->data;
        $view->action   = $this->action;

        $view->lists = $this->getLists();

        return $view->render("main.phtml");
    }
}