<?php

class T3RevNet_Settings {
    static public $globalTypes = array(
        'js_form'       =>  'JavaScript Form',
        'post_channel'  =>  'Server Post',
    );
    
    static public $settingsPath = '/en/account/integrations/revnet/ajax';
    
    static public function getGlobalSettings($type){
        $all = T3Db::api()->fetchAll("select * from revnet_settings where account=? and webmaster=0", $type);
        $return = array();
        foreach($all as $el){
            $return[$el['channel_type']] = $el;        
        }
        return $return;
    } 
    
    static public function getUniqueSettings($type){
        $all = T3Db::api()->fetchAll("select * from revnet_settings where account=? and channel_type='all' and webmaster > 0", $type);
        $return = array();
        foreach($all as $el){
            $return[$el['webmaster']] = $el;        
        }
        return $return;
    }  
    
    static protected function initView(){
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Settings");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_"); 
        
        return $view;    
    }
    
    static public function render($type){
        MyZend_Site::addCSS(array(
            'revnet/settings.css',
        ));
        
        MyZend_Site::addJS(array(
            'revnet/settings.js',
        ));
        
        $view = self::initView();        
        $view->type = $type;
        return $view->render("main.phtml");
    }
    
    static public function renderWmTr($opt){
        $view = self::initView();        
        $view->opt = $opt;
        return $view->render("wm_tr.phtml");
    }
    
    static public function ajax($params){
        $result = array(
            'status' => 'ok', // error
            'reason' => '',
            'data' => '',
        );
        
        if(isset($params['ajaxAction'])){
            $actions = array(
                'setGlobalValue'    =>  'ajax_setGlobalValue',
                'setGlobalDays'     =>  'ajax_setGlobalDays',
                'setGlobalPost'     =>  'ajax_setGlobalPost',
                'setWMValue'        =>  'ajax_setWMValue',
                'setWMDays'         =>  'ajax_setWMDays',
                'setWMPost'         =>  'ajax_setWmPost',
                'deleteWM'          =>  'ajax_deleteWM',
                'createWM'          =>  'ajax_createWM',
            );
            
            if(isset($actions[$params['ajaxAction']])){
                call_user_func_array(array('self', $actions[$params['ajaxAction']]), array(&$params, &$result));
            }
            else {
                $result['status'] = 'error'; 
                $result['reason'] = 'Action Not Found';    
            } 
        }
        else {
            $result['status'] = 'error'; 
            $result['reason'] = 'Action is Required';        
        }
        
        return Zend_Json::encode($result);
    }
    
    static protected function ajax_setGlobalValue(&$params, &$result){
        $value = (float)ifset($params['value'], 0);
        
        if($value < 0)$value = 0;
        if($value > 100)$value = 100;
        $value = round($value, 1);
        
        $result['data'] = $value; 
        
        $value = $value / 100; 
        $value = round($value, 3);
        
        T3Db::api()->update("revnet_settings", array(
            $params['moneyType'] => $value
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type=" . T3Db::api()->quote($params['channelType']) . " and webmaster=0");          
    }
    
    static protected function ajax_setGlobalDays(&$params, &$result){
        $value = (int)ifset($params['value'], 0);
        
        if($value < 0) $value = 0;
        if($value > 255) $value = 255;
        
        $result['data'] = $value; 
        
        T3Db::api()->update("revnet_settings", array(
            'days' => $value
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type=" . T3Db::api()->quote($params['channelType']) . " and webmaster=0");          
    }
    
    static protected function ajax_setWMValue(&$params, &$result){
        $value = (float)ifset($params['value'], 0);
        
        if($value < 0)$value = 0;
        if($value > 100)$value = 100;
        $value = round($value, 1);
        
        $result['data'] = $value; 
        
        $value = $value / 100; 
        $value = round($value, 3);
        
        T3Db::api()->update("revnet_settings", array(
            $params['moneyType'] => $value
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type='all' and webmaster=" . T3Db::api()->quote($params['webmaster']));          
    } 
    
    static protected function ajax_setWMDays(&$params, &$result){
        $value = (int)ifset($params['value'], 0);
        
        if($value < 0) $value = 0;
        if($value > 255) $value = 255;
        
        $result['data'] = $value; 
        
        
        T3Db::api()->update("revnet_settings", array(
            'days' => $value
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type='all' and webmaster=" . T3Db::api()->quote($params['webmaster']));          
    } 
    
    static protected function ajax_setGlobalPost(&$params, &$result){
        $result['data'] = (int)(bool)ifset($params['value'], 0);
        
        T3Db::api()->update("revnet_settings", array(
            'post' => $result['data']
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type=" . T3Db::api()->quote($params['channelType']) . " and webmaster=0");        
    }
    
    static protected function ajax_setWmPost(&$params, &$result){
        $result['data'] = (int)(bool)ifset($params['value'], 0);
        
        T3Db::api()->update("revnet_settings", array(
            'post' => $result['data']
        ), "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type='all' and webmaster=" . T3Db::api()->quote($params['webmaster']));        
    }
    
    static protected function ajax_deleteWM(&$params, &$result){
        T3Db::api()->delete("revnet_settings", "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type='all' and webmaster=" . T3Db::api()->quote((int)$params['webmaster']));        
    }
    
    static protected function ajax_createWM(&$params, &$result){
        try{
            T3Db::api()->insert("revnet_settings", array(
                'account' => $params['accountType'],
                'webmaster' => (int)$params['webmaster'],
            ));
            
            $result['data'] = T3RevNet_Settings::renderWmTr(
                T3Db::api()->fetchRow("select * from revnet_settings where id=?", T3Db::api()->lastInsertId())
            );
        }
        catch(Exception $e){
            $result['status'] = 'error';
            $result['reason'] = 'Duplicate Rule';    
        }
        
        //T3Db::api()->delete("revnet_settings", "account=" . T3Db::api()->quote($params['accountType']) . " and channel_type='all' and webmaster=" . T3Db::api()->quote($params['webmaster']));        
    }
    
    
    static $accountSettingsCache = array();
    
    static public function getAccountSetting($type, $value = null){
        if(!isset(self::$accountSettingsCache[$type])){
            self::$accountSettingsCache[$type] = T3Db::api()->fetchRow("select * from revnet_accounts where system_name=?", $type);
        } 
          
        if(is_string($value)){
            return ifset(self::$accountSettingsCache[$type][$value], null);     
        }
         
        return self::$accountSettingsCache[$type];
    }
    
    
}