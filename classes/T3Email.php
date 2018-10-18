<?php

require_once 'T3Users.php';

class T3Email {

    protected static $_instance = null;

    public $messageText 	= '';
    public $messageTitle 	= '';
    public $dirMessages 	= '';
    public $headers 		= '';
    public $messageMarkers ;
    public $messageSignature = '';
    public $from;
    public $from_id;
    public $to_array;
    public $resourceString = '';

    // important system objects
    protected $users;
    protected $database;
    protected $system;

    protected function  initialize() {

        $this->system = T3System::getInstance();
        $this->users = T3Users::getInstance();
        $this->database = $this->system->getConnect();
        $this->headers =  "\nContent-Type: text/html";
        $this->resourceString = 'http://' . $_SERVER['HTTP_HOST'] . '/img/email_resources';

        if ( isset($this->users->email) )	$this->from = $this->users->email;
        if ( isset($this->users->id ) ) 	$this->from_id =  $this->users->id;
        $this->to_array = array();

    }


    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }



    public function loadTemplate( $id ) {


        $query = $this->database->select()
                 ->from( array('ets' => 'email_templates' ) )
                 ->joinLeft(array('et' => 'email_template'),'ets.template = et.id',  array('design'))
                 ->joinLeft(array( 'ec'=> 'email_custom'), 'ec.emailID = et.id' )
                 ->where( 'ets.id = ?', $id );

        $row = $this->database->fetchRow($query);

        if ( !$row['use_rus_version']  ) {

            if ( isset( $row['eng_body'] ) && $row['eng_body'] != '' )
                $this->messageText = str_replace('{body}', $row['eng_body'] . '{signature}', $row['design']);
            else
                $this->messageText = str_replace('{body}', $row['eng_email_text'] . '{signature}', $row['design']);

            if ( isset( $row['eng_subject'] ) && $row['eng_subject'] != '' )
                $this->messageTitle = $row['eng_subject'];
            else
                $this->messageTitle = $row['eng_email_title'];

        } else {

            if ( isset( $row['rus_body'] ) && $row['rus_body'] != '' )
                $this->messageText = str_replace('{body}', $row['rus_body'] . '{signature}', $row['design']);
            else
                $this->messageText = str_replace('{body}', $row['rus_email_text'] . '{signature}', $row['design']);

            if ( isset($row['rus_subject']) && $row['rus_subject'] != '' )
                $this->messageTitle = $row['rus_subject'];
            else
                $this->messageTitle = $row['rus_email_title'];

        }



        return $this->messageText;
    }


    public function sendEmail() {

        if ($this->messageText == '') {
            trigger_error( 'Email Not Loaded' , E_USER_NOTICE );
            return;
        }

        $this->messageMarkers = $this->getMarkers();
        $this->pushEmails();

    }

    public function addRecipientByChannel($channelID) {

        $query = $this->database->select()
                 ->from( array('leads_channels_openmarket') )
                 ->join( array('users' ), 'users.login = leads_channels_openmarket.contact', array('nickname', 'email') )
                 ->join( array('users_company'), 'users.idcompany = users_company.id', array('company_name') )
                 ->where( 'leads_channels_openmarket.id = ?', $channelID );



        $row =  $this->database->fetchAssoc( $query );
        array_push( $this->to_array, $row );

        return true;

    }


    public function addRecipientById( $recipient ) {

        $query =  $this->database->select()
                  ->from( 'users', array('nickname', 'email') )
                  ->join( 'users_company','users.idcompany=users_company.id', 'company_name' )
                  ->where('users.id = ?', $recipient )
                  ->limit(1);

        //var_export( $query->__toString() );

        $row = $this->database->fetchAssoc($Q, $recipient);
        array_push( $this->to_array, $row );


    }



    public function addRecipient( $recipient ) {

        $query = $this->database->select()
                 ->from('users', array('nickname','email'))
                 ->join( 'users_company','users.idcompany=users_company.id', 'company_name' )
                 ->where('users.id = ?', $recipient )
                 ->limit(1);

        $row = $this->database->fetchAssoc($query);
        //var_export($row);

        array_push( $this->to_array, $row );
    }


    public function addEmailRecipient( $recipient ) {
        $email_parts = explode('@', $recipient);
        array_push( $this->to_array, array('email'=>$recipient, 'nickname'=>$email_parts[0]) );
    }


    public function setFrom($from_email, $from_id) {
        $this->from = $from_email;
        $this->from_id = $from_id;
    }


    public function setSignature($sig) {


        $query = $this->database->select()
                 ->from('users_personal_info', array('first_name','last_name','phone', 'aim', 'icq', 'skype') )
                 ->join('users', ' users_personal_info.iduser = users.id ', array('email','nickname') )
                 ->where('users.id = ?', $this->from_id );


        $row = $this->database->fetchAssoc( $query );
        $markers =  $this->getSignatureMarkers();

        foreach($markers as $key => $value )
        $sig =  str_replace($value, $row[$key], $sig);

        $this->messageSignature = $sig;

    }

    private function getMarkers() {
        return array( 'nickname' => '{nickname}', 'company_name' => '{company_name}', 'email' => '{email}', 'link'=>'{link}', 'link_html'=>'{link_html}', 'http://t3.t3lead.com/img/email_resource' => '{resource}');
    }


    private function getSignatureMarkers() {
        return array( 'nickname' => '{myNickname}', 'email' => '{myEmail}', 'phone' => '{myPhone}', 'icq' => '{myICQ}', 'aim' => '{myAIM}', 'aim' => '{myAIM}', 'skype' =>'{mySkype}'  );
    }


    private function replaceMarkers($to_array) {

        $message =  $this->messageText;
        $message = str_replace('{resource}', $this->resourceString, $message );

        foreach( $this->messageMarkers as $key => $value ) {
            if ( isset($to_array[$key]) )
                $message = str_replace($value,  $to_array[$key],  $message );
        }

        $message = str_replace('{signature}', $this->messageSignature, $message );

        return $message;

    }



    public function sendActivation($link) {


        $query = $this->database->select()
                 ->from( 'email_templates', array('rus_email_title','rus_email_text','eng_email_title','eng_email_text', 'eng_email_signature') )
                 ->join( 'email_template', 'email_templates.template = email_template.id', 'design')
                 ->where('email_templates.name = ?', "activation");

        $row = $this->database->fetchRow($query);
        //var_export($row);

        $this->messageMarkers = $this->getMarkers();


        // here we decide whether or not to send russian or not.
        if ( true  ) {
            $this->messageText = str_replace('{body}', $row['eng_email_text'] . '{signature}', $row['design']);
            $this->messageTitle = $row['eng_email_title'];

        } else {
            $this->messageText = str_replace('{body}', $row['rus_email_text'] . '{signature}', $row['design']);
            $this->messageTitle = $row['rus_email_title'];
        }


        // similar to pushEmails
        for ( $i = 0 ; $i < count($this->to_array); $i++ ) {
            $this->to_array[$i]['link'] = $link;
            $this->to_array[$i]['link_html'] = $link;
            $msg = $this->replaceMarkers( $this->to_array[$i] );
            mail( $this->to_array[$i]['email'], $this->messageTitle,  $msg, "Content-Type: text/html" );
            echo("mail( {$this->to_array[$i]['email']}, $this->messageTitle,  $msg, \"Content-Type: text/html\" );");

            //$this->database->insert( addslashes($msg) . '\n\n -----------\n\n' . $this->to_array[$i]['email'] );
        }

    }


    public function sendPostTestPassed() {


        $query =  $this->database->select()
                  ->from('email_templates')
                  ->join('email_template','email_templates.template =  email_template.id')
                  ->where('email_templates.name = ?', 'Post Channel Awaiting Activation');

        //var_export( $query->__toString() );

        $row  = $this->database->fetchAssoc( $sql );

        $this->messageMarkers = $this->getMarkers();

        if ( !$this->chooseLanguage($row) )
            return 	false;
        else
            $this->pushEmails();

    }



    // lots of code duplication ..
    public function sendRepsWelcome( $activation_usr_id ) {

        $sql = 'select buyer_agent_id, seller_agent_id from users_company inner join users on ( users.idcompany = users_company.id ) where users.id = ?';

        $row =  $this->database->fetchAssoc($sql, $activation_usr_id );
        $agent_id = ( $row['buyer_agent_id'] != 0) ? $row['buyer_agent_id'] : $row['seller_agent_id'] ;

        $query =  $this->database->select()
                  ->from('email_templates')
                  ->join( 'email_template' , 'email_templates.template = email_template.id' )
                  ->joinLeft( 'email_custom', 'email_custom.emailID = email_templates.id and email_custom.userid =' . intval($activation_usr_id)  ) //sdez shto delat?
                  ->where('email_templates.name = ?', 'welcome');

        //var_export( $query->__toString() );

        $row = $this->database->fetchAssoc($query);

        $this->messageMarkers = $this->getMarkers();

        if ( !$this->chooseLanguage($row) )
            return 	false;
        else
            $this->pushEmails();

    }

    private function pushEmails() {
        for ( $i = 0 ; $i < count($this->to_array); $i++ ) {
            $msg = $this->replaceMarkers( $this->to_array[$i] );
            mail( $this->to_array[$i]['email'], $this->messageTitle,  $msg, "Content-Type: text/html" );

            //$this->database->insert( emails, array( 'emailText' => addslashes($msg) . '\n\n -----------\n\n' .$this->to_array[$i]['email'], 'insert_date' =>  new Zend_Db_Expr('NOW()') )  );
        }
    }

    private function chooseLanguage($row) {

        if (! is_array($row) )
            return false;

        // here we decide whether or not to send russian or not.
        if ( true  ) {

            if ( isset($row['eng_body']) && $row['eng_body'] != '' )
                $this->messageText = str_replace('{body}', $row['eng_email_text'] . '{signature}', $row['design']);
            else
                $this->messageText = str_replace('{body}', $row['eng_email_text'] . '{signature}', $row['design']);

            if ( isset($row['eng_subject'])   && $row['eng_subject'] != '')
                $this->messageTitle = $row['eng_subject'];
            else
                $this->messageTitle = $row['eng_email_title'];




        } else {

            if ( isset($row['rus_body']) && $row['rus_body'] !='' )
                $this->messageText = str_replace('{body}', $row['rus_body'] . '{signature}', $row['design']);
            else
                $this->messageText = str_replace('{body}', $row['rus_email_text'] . '{signature}', $row['design']);

            if ( isset($row['rus_subject']) && $row['rus_subject'] !='' )
                $this->messageTitle = $row['rus_subject'];
            else
                $this->messageTitle = $row['rus_email_title'];


        }

        //catch php errors here and return false if anything fatal happens.
        return true;

    }

}


