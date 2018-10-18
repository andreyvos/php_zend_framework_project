<?php

class T3Ui_Select {
    static public function getAjaxURL(){
        if(isset($_SERVER['HTTPS'])) $protocol = 'https://';
        else                         $protocol = 'http://';

        return $protocol . $_SERVER['HTTP_HOST'] .  "/system/ui/select.php";
    }

    static public function webmaster($inputName = 'webmaster', $value = '', $width = '250px', $relations = array()){
        return self::render($inputName, 'webmaster', $value, $width, $relations);
    }

    static public function user($inputName = 'user', $value = '', $width = '250px'){
        return self::render($inputName, 'user', $value, $width);
    }

    static public function buyer($inputName = 'buyer', $value = '', $width = '250px', $relations = array()){
        return self::render($inputName, 'buyer', $value, $width, $relations);
    }

    // by Hrant
    static public function channel($inputName = 'channel', $value = '', $width = '250px'){
        return self::render($inputName, 'channel', $value, $width);
    }

    // By Так надо было :)
    static public function channels($inputName = 'channel', $value = '', $width = '50%', $relations = array()){
        return self::render($inputName, 'channels', $value, $width, $relations);
    }

    static public function posting($inputName = 'posting', $value = '', $width = '50%', $relations = array()){
        return self::render($inputName, 'posting', $value, $width, $relations);
    }

    /**
    * Рисование элемента выбора продукта
    *
    * @param mixed $inputName
    * @param mixed $value
    * @param mixed $firstOption - Элемент значение которого = ''. (Пример: `Select One...`)
    */
    static public function product($inputName = 'product', $value = '', $firstOption = null){
        return T3Products::renderSelectObject($inputName, $firstOption)->setValue($value)->render();
    }

    static protected function render($inputName, $type, $value = '', $width = '250px', $relations = array()){
        MyZend_Site::addCSS('ui/select.css');
        MyZend_Site::addJS('ui/select.js');

        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "Select");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_");

        $view->value        = '';
        $view->valueTitle   = '';

        if(strlen($value) && method_exists(new self, "resultOne_{$type}")){
            list($view->value, $view->valueTitle) = call_user_func_array(array('T3Ui_Select', "resultOne_{$type}"), array($value));
        }

        $view->ajaxURL      = self::getAjaxURL();
        $view->type         = $type;
        $view->inputName    = $inputName;
        $view->width        = $width;
        $view->relations    = $relations;

        return $view->render("main.phtml");
    }

    static protected $types = array(
        'webmaster',
    );

    static public function ajax(){
        header('Content-type: application/json');

        $type = self::getValue('type');

        $result = array();

        if(method_exists(new self, "resultAll_{$type}")){
            $result = call_user_func_array(array('T3Ui_Select', "resultAll_{$type}"), array());
        }

        echo Zend_Json::encode($result);
    }

    static public function getValue($name, $default = null){
        return ifset($_GET[$name], ifset($_POST[$name], $default));
    }

    static protected function resultAll_webmaster(){
        $data = array();

        $value = trim(self::getValue('value'));
        $valueQuote = T3Db::api()->quote("%" . $value . "%");

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('users_company_webmaster', array('id', 'systemName', 'relevance'));

        if(strlen($value)){
            $a = explode(" ", $value);
            foreach($a as $b){
                $b = trim($b);
                $valueQuote = T3Db::api()->quote("%" . $b . "%");
                $select->where("id like {$valueQuote} or systemName like {$valueQuote} or products like {$valueQuote}");
            }
        }

        $select->order('relevance desc, length(systemName) asc');
        $select->limit(15);

        $cU =& T3Users::getInstance()->getCurrentUser();

        if($cU->isRoleWebmasterAgent() && !T3Users_AgentManagers::isPubManager() && !T3Users_AgentManagers::isWebmasterAgentManager()){
            $select->where('agentID=?', $cU->id);
        }
        else if($cU->isRoleWebmaster()){
            $select->where('id=?', $cU->company_id);
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>",
                'title_long'    => "{$el['id']} - {$el['systemName']} - <span style='color:#999'>{$el['relevance']}</span>",
            );
        }

        return $data;
    }

    static protected function resultOne_webmaster($value){
        $result = array('', '');
        $value = (int)trim($value);
        //varExport($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('users_company_webmaster', array('systemName', 'balance', 'relevance'));
            $select->where("id=?", $value);

            $cU =& T3Users::getInstance()->getCurrentUser();

            if($cU->isRoleWebmasterAgent() && !T3Users_AgentManagers::isPubManager() && !T3Users_AgentManagers::isWebmasterAgentManager()){
                $select->where('agentID=?', $cU->id);
            }
            else if($cU->isRoleWebmaster()){
                $select->where('id=?', $cU->company_id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                $result = array(
                    $value,
                    "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>"
                );
            }
        }
        return $result;
    }

    static protected function resultAll_posting(){
        $data = array();

        $value = trim(self::getValue('value'));

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('buyers_channels', array('id','product','title','buyer_id','status','minConstPrice'));
        $select->joinInner("users_company_buyer", "users_company_buyer.id = buyers_channels.buyer_id", array('systemName'));
        $select->where("buyers_channels.isDeleted=0");

        if(strlen($value)){
            $a = explode(" ", $value);
            foreach($a as $b){
                $b = trim($b);
                $valueQuote = T3Db::api()->quote("%" . $b . "%");
                $select->where("
                buyers_channels.id like {$valueQuote} or
                buyers_channels.title like {$valueQuote} or
                buyers_channels.product like {$valueQuote} or
                buyers_channels.minConstPrice like {$valueQuote} or
                users_company_buyer.systemName like {$valueQuote} or
                users_company_buyer.id like {$valueQuote}
                ");
            }
        }

        $select->order('users_company_buyer.relevance desc');
        $select->limit(15);

        $cU =& T3Users::getInstance()->getCurrentUser();

        /*
        if($cU->isRoleBuyerAgent()){
            $select->where('agentID=?', $cU->id);
        }
        else
        */
        if($cU->isRoleBuyer()){
            $select->where('buyer_id=?', $cU->company_id);
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            if($el['status'] == "just_created") $st = "<span style=color:#999>New</span>";
            else if($el['status'] == "active")  $st = "<span style=color:#090>Active</span>";
            else if($el['status'] == "paused")  $st = "<span style=color:#A00>Paused</span>";

            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$st} <span style=color:#777>{$el['systemName']}</span> : {$el['title']} <span style=color:#999>(" . T3Products::getTitle($el['product']) . ")</span> $"."{$el['minConstPrice']}",
                'title_long'    => "{$st} <span style=color:#777>{$el['systemName']}</span> : {$el['title']} <span style=color:#999>(" . T3Products::getTitle($el['product']) . ")</span> $"."{$el['minConstPrice']}",
            );
        }

        return $data;
    }

    static protected function resultOne_posting($value){
        $result = array('', '');
        $value = (int)trim($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('buyers_channels', array('id','product','title','buyer_id','status','minConstPrice'));
            $select->joinInner("users_company_buyer", "users_company_buyer.id = buyers_channels.buyer_id", array('systemName'));
            $select->where("buyers_channels.id=?", $value);

            $cU =& T3Users::getInstance()->getCurrentUser();

            /*if($cU->isRoleWebmasterAgent()){
                $select->where('agentID=?', $cU->id);
            }
            else
            */
            if($cU->isRoleBuyer()){
                $select->where('buyers_channels.buyer_id=?', $cU->company_id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                if($el['status'] == "just_created") $st = "<span style=color:#999>New</span>";
                else if($el['status'] == "active")  $st = "<span style=color:#090>Active</span>";
                else if($el['status'] == "paused")  $st = "<span style=color:#A00>Paused</span>";

                $result = array(
                    $value,
                    "{$st} <span style=color:#777>{$el['systemName']}</span> : {$el['title']} <span style=color:#999>(" . T3Products::getTitle($el['product']) . ")</span> $"."{$el['minConstPrice']}"
                );
            }
        }
        return $result;
    }

    static protected function resultAll_channels(){
        $data = array();

        $value = trim(self::getValue('value'));

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('channels', array('id', 'channel_type', 'product', 'title'));
        $select->joinLeft("channels_post", "channels_post.id = channels.id", array('getID'));
        $select->joinInner("users_company_webmaster", "users_company_webmaster.id = channels.company_id", array('systemName'));
        if(strlen($value)){
            $a = explode(" ", $value);
            foreach($a as $b){
                $b = trim($b);
                if(substr($b, 0, 7) == 'http://') $b = substr($b, 7);
                if(substr($b, 0, 8) == 'https://') $b = substr($b, 8);

                if(in_array($b, array('p', 'post'))){
                    $select->where("channels.channel_type='post_channel'");
                }
                else if(in_array($b, array('f', 'form'))){
                    $select->where("channels.channel_type='js_form'");
                }
                else if(strlen($b)){
                    $valueQuote = T3Db::api()->quote("%" . $b . "%");
                    $select->where("channels.id like {$valueQuote} or channels.title like {$valueQuote} or
                    users_company_webmaster.systemName like {$valueQuote} or users_company_webmaster.id like {$valueQuote} or
                    channels.product like {$valueQuote} or
                    (channels.channel_type='post_channel' and channels_post.getID like {$valueQuote})");
                }
            }
        }

        $select->order('users_company_webmaster.relevance desc');
        $select->limit(15);


        $cU =& T3Users::getInstance()->getCurrentUser();

        if($cU->isRoleWebmasterAgent()){
            //$select->where('agentID=?', $cU->id);
        }
        else if($cU->isRoleWebmaster()){
            $select->where('channels.company_id=?', $cU->company_id);
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            $wm = "";
            if(!T3Users::getCUser()->isRoleWebmaster()){
                $wm = "{$el['systemName']}: ";
            }

            if($el['channel_type'] == "post_channel"){
                $short = "{$wm}<span style=color:#5A5 >post:</span> <b>{$el['getID']}</b> - {$el['title']} <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
                $long = "{$wm}<span style=color:#5A5 >post:</span> <b>{$el['getID']}</b> - {$el['title']} <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
            }
            else if($el['channel_type'] == "js_form"){
                $short = "{$wm}<span style=color:#339 >{$el['title']}</span> <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
                $long  = "{$wm}<span style=color:#339 >{$el['title']}</span> <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
            }
            else {
                $short = "Unknown";
                $long = "Unknown";
            }

            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => $short,
                'title_long'    => $long,
            );
        }

        return $data;
    }

    static protected function resultOne_channels($value){
        $result = array('', '');
        $value = (int)trim($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('channels', array('id', 'channel_type', 'product', 'title'));
            $select->joinLeft("channels_post", "channels.id = channels_post.id", array('getID'));
            $select->joinInner("users_company_webmaster", "users_company_webmaster.id = channels.company_id", array('systemName'));
            $select->where("channels.id=?", $value);


            $cU =& T3Users::getInstance()->getCurrentUser();

            if($cU->isRoleWebmasterAgent()){
                //$select->where('agentID=?', $cU->id);
            }
            else if($cU->isRoleWebmaster()){
                $select->where('channels.company_id=?', $cU->company_id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                if($el['channel_type'] == "post_channel"){
                    $long = "{$el['systemName']} <span style=color:#5A5 >post:</span> <b>{$el['getID']}</b> - {$el['title']} <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
                }
                else if($el['channel_type'] == "js_form"){
                    $long = "{$el['systemName']} <span style=color:#339 >{$el['title']}</span> <span style=color:#999 >(" . T3Products::getTitle($el['product']) . ")</span>";
                }
                else {
                    $long = "Unknown";
                }

                $result = array(
                    $value,
                    $long
                );
            }
        }
        return $result;
    }

    static protected function resultAll_buyer(){
        $data = array();

        $value = trim(self::getValue('value'));
        $valueQuote = T3Db::api()->quote("%" . $value . "%");

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('users_company_buyer', array(
            'users_company_buyer.id',
            'users_company_buyer.systemName',
            'users_company_buyer.relevance')
        );

        if(strlen($value)){
            $a = explode(" ", $value);
            foreach($a as $b){
                $b = trim($b);
                $valueQuote = T3Db::api()->quote("%" . $b . "%");
                $select->where("users_company_buyer.id like {$valueQuote} or
                users_company_buyer.systemName like {$valueQuote} or
                users_company_buyer.products like {$valueQuote}
                ");
            }
        }

        $select->order('users_company_buyer.relevance desc, length(users_company_buyer.systemName) asc');
        $select->limit(15);

        $cU =& T3Users::getInstance()->getCurrentUser();

        if($cU->isRoleBuyerAgent()){
            $select
            ->joinInner("buyers_channels", "users_company_buyer.id = buyers_channels.buyer_id", null)
            ->where("buyers_channels.product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')")
            ->group("users_company_buyer.id");
        }
        else if($cU->isRoleBuyer()){
            $select->where('users_company_buyer.id=?', $cU->company_id);
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>",
                'title_long'    => "{$el['id']} - {$el['systemName']} - <span style='color:#999'>{$el['relevance']}</span>",
            );
        }

        return $data;
    }

    static protected function resultOne_buyer($value){
        $result = array('', '');
        $value = (int)trim($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('users_company_buyer', array('users_company_buyer.systemName', 'users_company_buyer.relevance'));
            $select->where("users_company_buyer.id=?", $value);

            $cU =& T3Users::getInstance()->getCurrentUser();

            if($cU->isRoleBuyerAgent()){
                $select
                ->joinInner("buyers_channels", "users_company_buyer.id = buyers_channels.buyer_id", null)
                ->where("buyers_channels.product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')")
                ->group("users_company_buyer.id");
            }
            else if($cU->isRoleBuyer()){
                $select->where('users_company_buyer.id=?', $cU->company_id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                $result = array(
                    $value,
                    "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>"
                );
            }
        }
        return $result;
    }

    static protected function resultAll_user(){
        $data = array();


        $value = trim(self::getValue('value'));
        $valueQuote = T3Db::api()->quote("%" . $value . "%");

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('users', array('id', 'login', 'company_id'));
        if(strlen($value)) $select->where("users.id like {$valueQuote} or users.login like {$valueQuote} or users.company_id like {$valueQuote}");

        $select->limit(15);

        $cU =& T3Users::getInstance()->getCurrentUser();

        if($cU->isRoleWebmasterAgent() && !T3Users_AgentManagers::isPubManager()){
            $select->joinInner('users_company_webmaster', 'users_company_webmaster.id=users.company_id', array('balance'));
            $select->order('balance desc, length(users.login) asc');
            $select->where('agentID=?', $cU->id);
        }
        else if($cU->isRoleWebmasterAgent() && T3Users_AgentManagers::isPubManager()){
            $select->where("users.`role`='webmaster'");
        }
        else if($cU->isRoleWebmaster()){
            $select->joinInner('users_company_webmaster', 'users_company_webmaster.id=users.company_id', array('balance'));
            $select->where('users.company_id=?', $cU->company_id);
        }
        else {
            $select->joinInner('users_company', 'users_company.id=users.company_id', array('balance'));
            $select->order('balance desc, length(users.login) asc');
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$el['login']}", // - <span style=color:#999>Relevance:{$el['balance']}</span>
                'title_long'    => "{$el['login']}",
            );
        }

        return $data;
    }

    static protected function resultOne_user($value){
        $result = array('', '');
        $value = (int)trim($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('users', array('login', 'company_id'));
            $select->where("id=?", $value);

            $cU =& T3Users::getInstance()->getCurrentUser();

            if($cU->isRoleWebmasterAgent()){
                //$select->where('agentID=?', $cU->id);
            }
            else if($cU->isRoleWebmaster()){
                $select->where('id=?', $cU->id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                $result = array(
                    $value,
                    "{$el['login']} - <span style=color:#999>cid:{$el['company_id']}</span>"
                );
            }
        }
        return $result;
    }




    // by Hrant
	static protected function resultAll_channel(){
        $data = array();

        $value = trim(self::getValue('value'));

        header('Content-Type: text/html');

        $filters = array('u' => null, 't' => null, 'c' => null, 's' => null);

        $fltrs = explode(' ', $value);
        foreach ( $fltrs as $f ) {
        	$f = explode(':', $f);

        	if ( count($f) == 2 ) {
        		$filters[$f[0]] = trim($f[1]);
        	}
        	else {
        		$filters['s'] = trim($f[0]);
        	}
        }

        foreach ( $filters as $k => $v ) {
        	if ( is_numeric($v) ) {
        		$filters[$k] = intval($v);
        	}
        }

        // <--

        // Base select

        $db = T3Db::api();

        $sql = '
        	SELECT bc.id, bc.product, ucb.systemName, ucb.relevance
        	FROM buyers_channels bc
        	INNER JOIN users_company_buyer ucb ON ucb.id = bc.buyer_id
        ';
        $sql_1 = '
        	SELECT c.id, c.product, ucw.systemName, ucw.relevance
        	FROM channels c
        	INNER JOIN users_company_webmaster ucw ON ucw.id = c.company_id
        ';

        $where = $where_1 = array();

        /* --> Filtration by User Request */
        if ( ! is_null($filters['u']) ) {
        	if ( is_int($filters['u']) ) {
        		$where[] = 'ucb.id = ' . $filters['u'];
        		$where_1[] = 'ucw.id = ' . $filters['u'];
        	}
        	else {
        		$where[] = 'ucb.systemName LIKE ' . $db->quote('%' . $filters['u'] . '%');
        		$where_1[] = 'ucw.systemName LIKE ' . $db->quote('%' . $filters['u'] . '%');
        	}
        }

        if ( strlen($filters['s']) ) {
        	if ( is_int($filters['s']) ) {
        		$where[] = 'bc.id = ' . $filters['s'];
        		$where_1[] = 'c.id = ' . $filters['s'];
        	}
        	else {
        		$where[] = 'bc.title = ' . $db->quote($filters['s']);
        		$where_1[] = 'c.title = ' . $db->quote($filters['s']);
        	}
        }

        if ( ! is_null($filters['c']) ) {

        }


        if ( ! is_null($filters['t']) ) {
        	if ( $filters['t'] == 'post' ) {
        		$f = 'post_channel';
        	}
        	elseif ( $filters['t'] == 'form' ) {
        		$f = 'js_form';
        	}
        	else {
        		$f = null;
        	}

        	if ( ! is_null($f) ) {
        		$sql = '';
        		$where_1[] = 'c.channel_type = ' . $db->quote($f);
        	}
        }



        /* <-- */

        if ( strlen($sql) ) $sql .= count($where) ? ' WHERE ' . implode(' AND ', $where) . ' ' : '';
        $sql_1 .= count($where_1) ? ' WHERE ' . implode(' AND ', $where_1) . ' ' : '';

        if ( strlen($sql) ) {
        	$union = "($sql) UNION ($sql_1)";
        }
        else {
        	$union = $sql_1;
        }

        $sql = "SELECT * FROM ($union) x ORDER BY x.relevance DESC LIMIT 15";

        $a = T3Db::api()->fetchAll($sql);
        foreach ($a as $el) {
            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$el['systemName']} - ({$el['relevance']})",
                'title_long'    => "{$el['product']} - {$el['systemName']} ({$el['relevance']})",
            );
        }

        return $data;



        // The code below is just kept for example

        $valueQuote = T3Db::api()->quote("%" . $value . "%");

        /**
        * @var Zend_Db_Select
        */
        $select = T3Db::api()->select();
        $select->from('users_company_buyer', array(
            'users_company_buyer.id',
            'users_company_buyer.systemName',
            'users_company_buyer.relevance')
        );
        if(strlen($value)) $select->where("users_company_buyer.id like {$valueQuote} or users_company_buyer.systemName like {$valueQuote}");
        $select->order('users_company_buyer.relevance desc, length(users_company_buyer.systemName) asc');
        $select->limit(15);

        $cU =& T3Users::getInstance()->getCurrentUser();

        if($cU->isRoleBuyerAgent()){
            $select
            ->joinInner("buyers_channels", "users_company_buyer.id = buyers_channels.buyer_id", null)
            ->where("buyers_channels.product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')")
            ->group("users_company_buyer.id");
        }
        else if($cU->isRoleBuyer()){
            $select->where('users_company_buyer.id=?', $cU->company_id);
        }

        $a = T3Db::api()->fetchAll($select);
        foreach($a as $el){
            $data[] = array(
                'id'            => $el['id'],
                'title_short'   => "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>",
                'title_long'    => "{$el['id']} - {$el['systemName']} - <span style='color:#999'>{$el['relevance']}</span>",
            );
        }

        return $data;
    }

    static protected function resultOne_channel($value){
        $result = array('', '');
        $value = (int)trim($value);
        if($value > 0){
            /**
            * @var Zend_Db_Select
            */
            $select = T3Db::api()->select();
            $select->from('users_company_buyer', array('users_company_buyer.systemName', 'users_company_buyer.relevance'));
            $select->where("users_company_buyer.id=?", $value);

            $cU =& T3Users::getInstance()->getCurrentUser();

            if($cU->isRoleBuyerAgent()){
                $select
                ->joinInner("buyers_channels", "users_company_buyer.id = buyers_channels.buyer_id", null)
                ->where("buyers_channels.product in ('" . implode("','", T3UserBuyerAgents::getProducts()) . "')")
                ->group("users_company_buyer.id");
            }
            else if($cU->isRoleBuyer()){
                $select->where('users_company_buyer.id=?', $cU->company_id);
            }

            $el = T3Db::api()->fetchRow($select);

            if($el !== false){
                $result = array(
                    $value,
                    "{$el['systemName']} - <span style=color:#999>{$el['relevance']}</span>"
                );
            }
        }
        return $result;
    }
}