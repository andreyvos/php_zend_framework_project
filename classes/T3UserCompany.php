<?php



TableDescription::addTable('users_company', array(
                               'id',
                               'company_name',
                               'status',
                               'points',
                               'pay_period',
                               'pay_info',
                               'pay_mininmal',
                               'pay_accept',
                               'seller',
                               'buyer',
                               'refaffid',
                               'auto_subacc',
                               'seller_agent_id',
                               'buyer_agent_id',
                               'products_interest',
                               'comments',
                               'secure_id',
                               'balance',
                               'reg_date'

                           ));



class T3UserCompany extends DbSerializable {

    public $id;
    public $company_name;
    public $status;
    public $points;
    public $pay_period;
    public $pay_info;
    public $pay_mininmal;
    public $pay_accept;
    public $seller;
    public $buyer;
    public $refaffid;
    public $auto_subacc;
    public $seller_agent_id;
    public $buyer_agent_id;
    public $products_interest;
    public $comments;
    public $secure_id;
    public $balance;
    public $reg_date;
    public $defaultProcCallVerify;
    public static $_instance;

    protected $usersDistribution = array();


    protected function initialize() {


        if (!isset($this->className))$this->className = __CLASS__;

        parent::__construct();
        $this->tables = array('users_company');

        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();

        $keys = array(
                    'id', 'email'
                );

        foreach($keys as $v)
        $this->usersDistribution[$v] = array();

    }
    
    public static function getStatusTitle($name, $defaultErrorText = 'Unknown'){
        $array = array(
            'lock'   => 'Lock',
            'hold'   => 'Hold',
            'noappr' => 'No Approve',
            'activ'  => 'Active',
        ); 
        
        if(isset($array[$name]))    return $array[$name];
        else                        return $defaultErrorText;    
    }
    
    public static function getStatusTitleHtml($name, $defaultErrorText = 'Unknown'){
        $array = array(
            'lock'   => 'Lock',
            'hold'   => 'Hold',
            'noappr' => 'No Approve',
            'activ'  => 'Active',
        ); 
        
        if(isset($array[$name]))    $t = $array[$name];
        else                        $t = $defaultErrorText;    
        
        if(T3Users::getCUser()->isRoleWebmaster() && T3WebmasterCompanys::getCompany(T3Users::getCUser()->company_id)->status == 'hold'){
            $t = "
<style>
a.helpMessageTop {
white-space:nowrap;
cursor:help;
text-decoration:none;
}

a.helpMessageTop:hover span{
display:inherit;
position:absolute;
}

a.helpMessageTop span {
position:absolute;
display:none;
width:0px;
}

a.helpMessageTop span div {
position:relative;
top:30px;
left:10px;
border:#06C solid 1px;
background:#DAE4EB;
padding:7px;
width:200px;
white-space:normal;
font-weight:normal;
font-size:11px;
font-family:Tahoma, Geneva, sans-serif;
text-align:left;
z-index:9999999;

text-shadow:0px 0px 3px #FFF;

background-color: #DAE4EB;
background-color: rgba(218, 228, 235, 0.95);  /* FF3+, Saf3+, Opera 10.10+, Chrome */
filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#EEDAE4EB',endColorstr='#EEDAE4EB'); /* IE6,IE7 */
-ms-filter: \"progid:DXImageTransform.Microsoft.gradient(startColorstr='#EEDAE4EB',endColorstr='#EEDAE4EB')\"; /* IE8 */

-moz-border-radius: 0px 15px 15px 15px; /* FF1+ */
-webkit-border-radius: 7px; /* Saf3+, Chrome */
border-radius: 7px; /* Opera 10.5, IE 9 */
}
</style>
<a class='helpMessageTop'><b style='color:#D00'>{$t}</b> <img src='/img/warning.png'>
<span><div>There is a hold on your account, please contact your Account Manager</div></span></a>";    
        }
        
        return $t;
    }
    
    


    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }



    public function getCompanyById($id) {
        return $this->getCompany($id);
    }



    protected function getCompany($field, $value = null) {
        if (is_null($value)) {
            $value = $field;
            $field = 'id';
        }
        if (!isset($this->usersDistribution[$field][$value])) {
            $object = T3UserCompany::createFromDatabase(array($field => $value));
            if ($object===false)
                return false;
            $this->insertUserIntoArray($object);
        }
        return $this->usersDistribution[$field][$value];
    }



    protected function insertUserIntoArray($object) {
        foreach($this->usersDistribution as $k => $v)
        if (isset($object->$k))
            $this->usersDistribution[$k][$object->$k] = $object;
        $this->users[] = $object;
    }

}