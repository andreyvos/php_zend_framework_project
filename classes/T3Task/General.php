<?php

class T3Task_General {
    static protected $types = array(
        'supportMessage'    => array('name' => 'supportMessage',    'title' => 'Support Message'),
        'googleParse'       => array('name' => 'googleParse',       'title' => 'Google Parse'),
        'default'           => array('name' => 'default',           'title' => 'Default'),
    );
    
    static public function getTypesTitles(){
        $r = array();
        if(count(self::$types)){
            foreach(self::$types as $t){
                $r[$t['name']] = $t['title'];    
            }    
        } 
        return $r;
    }
    
    static public function getTypesDefault(){
        return "default";
    } 
    
    static protected $importance = array(
        array('value' => '10', 'title' => "Low"),
        array('value' => '20', 'title' => "Middle"),   
        array('value' => '30', 'title' => "High"),   
        array('value' => '40', 'title' => "ASAP"),   
    );
    
    static public function getImportanceTitles(){
        $r = array();
        if(count(self::$importance)){
            foreach(self::$importance as $t){
                $r[$t['value']] = $t['title'];    
            }    
        } 
        return $r;
    } 
    
    static public function getImportanceDefault(){
        return 20;
    }  
    
    
    static protected $fullUsers;
    static public function getUsers(){
        if(self::$fullUsers === null){
            self::$fullUsers = array(); 
            $all = T3Db::api()->fetchAll("select id,nickname as `name`, ifnull(taskGroup,role) as `group` from users where role in ('admin', 'webmaster_agent', 'buyer_agent') and ban='0' order by `group`, nickname"); 
            
            if(count($all)){
                foreach($all as $el){
                    self::$fullUsers[$el['id']] = $el;    
                }
            }
        }
        
        return self::$fullUsers;   
    }
    
    static public function getUsersForZendSelect(){
        $result = array();
        $all = self::getUsers();
        if(count($all)){
            foreach($all as $el){
                $result[$el['group']][$el['id']] = $el['name'];         
            }    
        }
        return $result;
    }
    
    static public function getUsersForZendValidation(){
        $result = array();
        $all = self::getUsers();
        if(count($all)){
            foreach($all as $el){
                $result[] = $el['id'];    
            }    
        }
        return $result;
    }  
    
    function cmp($a, $b) {
        if ($a['value'] == $b['value']) {
            return 0;
        }
        return ($a['value'] < $b['value']) ? 1 : -1;
    }
    
    static public function reindexUser($user){
        $allTemp = T3Db::api()->fetchAll("select importance, sum(`new`) as `new`, count(*) as `all` from tasks where responsibleUser=? and `status`='open' group by importance order by importance desc", $user);
        $all = array();
        if(count($allTemp)){
            foreach($allTemp as $el){
                $all[$el['importance']] = array(
                    'new' => $el['new'],
                    'all' => $el['all'],
                );
            }    
        }
        $result = array();
        
        $iTs = self::$importance;
        usort($iTs, array('T3Task_General', 'cmp'));
        
        foreach($iTs as $iT){
            $result[] = array(
                'title' => $iT['title'],
                'value' => $iT['value'],
                'new' => ifset($all[$iT['value']]['new'], 0),
                'all' => ifset($all[$iT['value']]['all'], 0),
            );      
        }
        
        
        T3Db::api()->delete("tasks_users_tasks_count", "user=".(int)$user);
        
        if(count($allTemp)){
            T3Db::api()->insert("tasks_users_tasks_count", array(
                "user" => $user,
                "info" => serialize($result),
            )); 
        }

    } 
    
    /**
    * put your comment there...
    * 
    * @param mixed $toUserId
    * @param mixed $title
    * @param mixed $htmlText
    * @param mixed $type
    * @param mixed $importance
    * @param mixed $createrName
    * @return T3Task_Item
    */
    static public function createTaskLigths($toUserId, $title, $htmlText = null, $type = 'default', $importance = 20, $createrName = 'Automatic'){
        $task = T3Task_Item::create()
        ->setTitle($title)
        ->setTaskType($type)
        ->setImportance((int)$importance) 
        ->setConcatenationOneUser((int)$toUserId)
        ->setCreator($createrName);

        $id = $task->insertIntoDatabase();

        // Если надо добавить какой то комментарий
        if(strlen($htmlText)){
            $task->createFirstMessage($htmlText, $createrName);
        }

        $task->updateAutoDescription();

        // Письмо и реиндекс виджета для ответсвенного 
        if($task->responsibleUser && $task->status == 'open'){
            $task->sendMessageNewTask();
            T3Task_General::reindexUser($task->responsibleUser);     
        } 
        
        return $task;    
    }
    
    /**
    * @param int $id
    * @return T3Task_Item
    */
    static public function getTask($id){
        $task = new T3Task_Item();
        $task->fromDatabase($id);
        return $task;   
    }
}