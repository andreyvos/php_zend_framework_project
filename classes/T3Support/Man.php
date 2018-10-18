<?php
TableDescription::addTable('support', array(
    'id',
    'type',
    'role',
    'name',
    'work_phone',
    'cell_phone',
    'skype',
    'icq',
    'aim',    
    'email',    
    'languages',
    'work_time',
    'order',    
    'timezone',    
    'avatar',  
));


class T3Support_Man extends DbSerializable {
    public $id;
    public $name;
    public $type;
    public $role;
    public $work_phone;
    public $cell_phone;
    public $skype;
    public $icq;
    public $aim;
    public $email;
    public $languages;
    public $work_time;
    public $order;
    public $timezone; 
    public $avatar; 
    
    public function __construct() { 
        if(!isset($this->className))$this->className = __CLASS__;
        parent::__construct();            
        $this->tables = array('support');
    }       
    
    public function getAllInfo(){
        $array = array(
            'languages'     => array('title' => 'Languages'), 
            'work_time'     => array('title' => 'Business Hours'), 
            'work_phone'    => array('title' => 'Phone'),
            'cell_phone'    => array('title' => 'Cell'),
            'skype'         => array('title' => 'Skype'),
            'icq'           => array('title' => 'ICQ'),
            'aim'           => array('title' => 'AIM'),    
            'email'         => array('title' => 'Email'),     
        );
        
        $data = array();
        
        foreach($array as $name => $options){
            if(isset($this->$name) && strlen(trim($this->$name))){
                $data[] = array(
                    'name' => ifset($options['title'], $name),
                    'data'  => $this->$name,
                    'newName' => $this->getNewName($name, $this->$name)
                );    
            }     
        }       
        
        return $data; 
    }
    
    protected function getNewName($name, $data){
        if($name == 'icq' && is_numeric($data)){
            return "<img src='http://status.icq.com/online.gif?icq=" . urlencode($data) . "&img=5&rnd=" . rand(100000,999999) . "' alt='ICQ'>";
        }
        return null;   
    }
    
    public function getGroupTitle(){
        return $this->groupTitle;   
    }
}