<?php

require_once "AP/DocuSign/utils.php";
require_once "AP/DocuSign/session.php";

$_SESSION['UserID'] = 'hrant.m@t3leads.com';
$_SESSION["AccountID"] =  '7e52f474-9f12-4cd8-8747-eb7428a1d68f'; // Need to clerify on this
$_SESSION["IntegratorsKey"] = "TLEA-be862953-7991-4c9b-809f-48fdad9af728";
$_SESSION["Password"] = "hrantjan";


class AP_DocuSign {

    
    public $_oneSigner = true; // Do we want One Signer (=true) or Two (=false)
    public $_showTwoSignerMessage = false; // Display (or not display) a message before Signer One has signed (only for Two Signer mode)
    public $_showTransitionMessage = false; // Display (or not display) a message after Signer One has signed (only for Two Signer mode)
    
    //========================================================================
    // Functions
    //========================================================================
    
    public function test()
    {
        $billing = array (
            'account_number'=> T3Users::getCUser()->id,
            'ptype'         =>'echeck',//'cc',
            'firstName'     =>'John',
            'lastName'      => 'Smith',
            'address'       => 'blah-blah str',
           // 'city'          => 'sometown',
            'country'       => 'US',
            'state'         =>'CA',
            'zip'           =>'91206',
            'phoneNumber'   =>'8188188811',
            'faxNumber'     =>'',
            'email'            =>'email@email.com',
            ////credit card type/////////
            'cardNumber'     =>'5555xxxTRSxxxxx1111',
            'cardCode'       => '123',
            'expirationDate' => 'mm/yyyy',
            'cardType'       => 'Visa',
            ///////echeck type////////////////
            'nameOnAccount' => '',
            'accountNumber' => '',
            'accountType'   => '',
            'bankName'      => '',
            'routingNumber' => '',
//            ptype - тип пэймента(профайла...етц)...либо кредитка (сс) либо банковский аккаунт(echeck)
//            если кредитка то ////credit card type/////////
            'cardNumber'     =>'6666xxxx!!xxxx2222',
            'cardCode'       => '123',
            'expirationDate' => 'mm/yyyy',
//            будет заполнено
//            если ечеки то ///////echeck type////////////////
            'nameOnAccount' => '',
            'accountNumber' => '',
            'accountType'   => '',
            'bankName'      => '',
            'routingNumber' => ''
        );
        
        self::createAndSend($billing, 125487);
    }

    public static function SendUserToDocuSign_fw8eci($data, $redirectAction){
      return self::SendUserToDocuSign($data, "/DocuSign/fw8eci_new.pdf", "fw8eci Form", $redirectAction);
    }
    
    public static function SendUserToDocuSign_fw9($data, $redirectAction){
      return self::SendUserToDocuSign($data, "/DocuSign/fw9_new.pdf", "fw9 Form", $redirectAction);
    }

    public static function SendUserToDocuSign($namesValues, $formPdfFilePath, $formTitle, $redirectAction){

      $id = 1;

      global $_oneSigner;
      $status = "";
      $_oneSigner = true;
      // Construct basic envelope
      $env = new Envelope();
      $env->Subject = $formTitle;
//        $env->EmailBlurb = "This envelope demonstrates embedded signing";
      $env->AccountId = $_SESSION["AccountID"];

      $recipient = new Recipient();
      $recipient->UserName = T3Users::getCUser()->first_name . " " . T3Users::getCUser()->last_name;
      $recipient->Email = T3Users::getCUser()->email;
      $recipient->ID = $id;
      $recipient->Type = RecipientTypeCode::Signer;
      $recipient->CaptiveInfo = new RecipientCaptiveInfo();
      $recipient->CaptiveInfo->ClientUserId = T3Users::getCUser()->id;
      $env->Recipients = array($recipient);



      $hidCustomField = new CustomField();
      $hidCustomField->Name = 'hid';
      $hidCustomField->Required = true;
      $hidCustomField->Show = false;
      $hidCustomField->Value = md5(time().T3Users::getCUser()->id);
      $env->CustomFields = array($hidCustomField);


      $doc = new Document();
      $doc->PDFBytes = file_get_contents($formPdfFilePath, true);
      $doc->Name = $formTitle;
      $doc->ID = "1";
      $doc->FileExtension = "pdf";
      $env->Documents = array($doc);      

      
      $env->Tabs = array();

      foreach($namesValues as $name => $desc){

        $RecipientID = $id;
        $fullAnchor = new Tab();
        $fullAnchor->Type = $desc['type'];
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{" . $name . "}";
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = $desc['value'];
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
     
        $env->Tabs[] = $fullAnchor;

      }

      $api = getAPI();
      try {
        $csParams = new CreateAndSendEnvelope();
        $csParams->Envelope = $env;
        $status = $api->CreateAndSendEnvelope($csParams)->CreateAndSendEnvelopeResult;
        addEnvelopeID($status->EnvelopeID);
        self::getToken($status, 0, "", $redirectAction);
        return $status->EnvelopeID;
      } catch (SoapFault $e) {
        echo $e->getMessage();
      }

    }
    
    /**
     * Creates an embedded signing experience.
     */
    function createAndSend($billing, $id,$hid = null,$page = "") {
       
        global $_oneSigner;
        $status = "";
        $_oneSigner = true;
        // Construct basic envelope
        $env = new Envelope();
        $env->Subject = "Payment Authorization";
//        $env->EmailBlurb = "This envelope demonstrates embedded signing";
        $env->AccountId = $_SESSION["AccountID"];



        $env->Recipients = self::constructRecipients($billing, $id,$_oneSigner);

        if(empty($hid)){
            $hid = md5(time().T3Users::getCUser()->id);
        }


        $c1 = new CustomField();
            $c1->Name = 'hid';
            $c1->Required = true;
            $c1->Show = false;
            $c1->Value = $hid;
        $env->CustomFields = array($c1);
        
        
        $doc = new Document();
        $doc->PDFBytes = file_get_contents("DocuSign/auth.pdf", true);
        $doc->Name = "Auth From";
        $doc->ID = "1";
        $doc->FileExtension = "pdf";
        $env->Documents = array($doc);
        
        $env->Tabs = self::addTabs(count($env->Recipients),$billing,$id);
        
        $api = getAPI();
        try {
            $csParams = new CreateAndSendEnvelope();
            $csParams->Envelope = $env;
            $status = $api->CreateAndSendEnvelope($csParams)->CreateAndSendEnvelopeResult;
            //var_dump($status->CustomFields->CustomField[0]->Name);
            //echo '<br><br><br><br>';
           // var_dump($status);
            addEnvelopeID($status->EnvelopeID);
            self::getToken($status, 0,$page);
            //echo "<br><br><br>SESSION:<br>";
            //print_r($_SESSION);
            
        } catch (SoapFault $e) {
            //$_SESSION["errorMessage"] = $e;
            //varExport($_SESSION["errorMessage"]);
            echo $e->getMessage();
            //header("Location: error.php");
        }
    }
    
    /**
     * Construct recipients
     * 
     * @param boolean $oneSigner
     *     true - create one recipient
     *     false = create two recipient
     * 
     * @return Recipient[]
     */
    function constructRecipients($billing, $id) {
        global $_oneSigner;
        $recipients[] = new Recipient();
        
        $r1 = new Recipient();
        $r1->UserName = $billing['firstName'].' '.$billing['lastName'];
        $r1->Email = $billing['email'];
        $r1->ID = $id;
        
        $r1->Type = RecipientTypeCode::Signer;
        $r1->CaptiveInfo = new RecipientCaptiveInfo();
        $r1->CaptiveInfo->ClientUserId = $id;
        array_push($recipients, $r1);
        
        if ($_oneSigner != true) {
            $r2 = new Recipient();
            $r2->UserName = "";
            $r2->Email = "";
            $r2->ID = 2;
            $r2->Type = RecipientTypeCode::Signer;
            $r2->CaptiveInfo = new RecipientCaptiveInfo();
            $r2->CaptiveInfo->ClientUserId = 2;
            array_push($recipients, $r2);
        }    
        
        // remove 0th element
        array_shift($recipients);
        
        return $recipients;
    }
    
    function addTabs($count,$billing, $RecipientID) {
        $tabs[] = new Tab();
        
        // Signature
        $signatureAnchor = new Tab();
        $signatureAnchor->Type = TabTypeCode::SignHere;
        $anchorSignature = new AnchorTab();
        $anchorSignature->AnchorTabString = "{signature}";
        $anchorSignature->XOffset = 0;
        $anchorSignature->YOffset = 5;
        $anchorSignature->Unit = UnitTypeCode::Pixels;
        $anchorSignature->IgnoreIfNotPresent = false;
        $signatureAnchor->AnchorTabItem = $anchorSignature;
        $signatureAnchor->DocumentID = "1";
        $signatureAnchor->PageNumber = "1";
        $signatureAnchor->RecipientID = $RecipientID;
        array_push($tabs, $signatureAnchor);
        
        // Account ID
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::Custom;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "account_number";
        $anchor->XOffset = 0;
        $anchor->YOffset = -4;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = (isset($billing['account_number']))? $billing['account_number'] : "";
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
        // Full name        
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::FullName;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{full_name}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -2;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        // Date
        $date = new Tab();
        $date->Type = TabTypeCode::DateSigned;
        $anchorDate = new AnchorTab();
        $anchorDate->AnchorTabString = "{date}";
        $anchorDate->XOffset = 0;
        $anchorDate->YOffset = -2;
        $anchorDate->Unit = UnitTypeCode::Pixels;
        $anchorDate->IgnoreIfNotPresent = false;
        $date->AnchorTabItem = $anchorDate;
        $date->DocumentID = "1";
        $date->PageNumber = "1";
        $date->RecipientID = $RecipientID;
        array_push($tabs, $date);
        
        // Name on the card
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::FullName;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{name_on_the_card}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -2;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
        // Card Type
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::Custom;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{card_type}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -4;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = (isset($billing['cardType']))? $billing['cardType'] : "";
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
        // Card Number
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::Custom;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{card_number}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -4;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = substr_replace($billing['cardNumber'],"xxxxxxxx",4,strlen($billing['cardNumber'])-8);
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
           // Card Exp
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::Custom;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{expiration}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -4;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = $billing['expirationDate'];
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
        // Card Exp
        $fullAnchor = new Tab();
        $fullAnchor->Type = TabTypeCode::Custom;
        $anchor = new AnchorTab();
        $anchor->AnchorTabString = "{cvv}";
        $anchor->XOffset = 0;
        $anchor->YOffset = -4;
        $anchor->Unit = UnitTypeCode::Pixels;
        $anchor->IgnoreIfNotPresent = false;
        $fullAnchor->AnchorTabItem = $anchor;
        $fullAnchor->DocumentID = "1";
        $fullAnchor->Value = $billing['cardCode'];
        $fullAnchor->PageNumber = "1";
        $fullAnchor->RecipientID = $RecipientID;
        array_push($tabs, $fullAnchor);
        
        /*    
        $init2 = new Tab();
        $init2->Type = TabTypeCode::InitialHere;
        $init2->DocumentID = "1";
        $init2->PageNumber = "1";
        $init2->RecipientID = $RecipientID;
        $init2->XPosition = "179";
        $init2->YPosition = "583";
        $init2->ScaleValue = "0.6";
        array_push($tabs, $init2);
            
        if ($count > 1) {
            $sign2 = new Tab();
            $sign2->Type = TabTypeCode::SignHere;
            $sign2->DocumentID = "1";
            $sign2->PageNumber = "1";
            $sign2->RecipientID = "2";
            $sign2->XPosition = "339";
            $sign2->YPosition = "97";
            array_push($tabs, $sign2);
    
            $date2 = new Tab();
            $date2->Type = TabTypeCode::DateSigned;
            $date2->DocumentID = "1";
            $date2->PageNumber = "1";
            $date2->RecipientID = "2";
            $date2->XPosition = "339";
            $date2->YPosition = "97";
            array_push($tabs, $date2);
        }
            
        $favColor = new Tab();
        $favColor->Type = TabTypeCode::Custom;
        $favColor->CustomTabType = CustomTabType::Text;
        $favColor->DocumentID = "1";
        $favColor->PageNumber = "1";
        $favColor->RecipientID = $RecipientID;
        $favColor->XPosition = "301";
        $favColor->YPosition = "416";
        array_push($tabs, $favColor);
        
        $fruitNo = new Tab();
        $fruitNo->Type = TabTypeCode::Custom;
        $fruitNo->CustomTabType = CustomTabType::Radio;
        $fruitNo->CustomTabRadioGroupName= "fruit";
        $fruitNo->TabLabel = "No";
        $fruitNo->Name = "No";
        $fruitNo->DocumentID = "1";
        $fruitNo->PageNumber = "1";
        $fruitNo->RecipientID = $RecipientID;
        $fruitNo->XPosition = "296";
        $fruitNo->YPosition = "508";
        array_push($tabs, $fruitNo);
        
        $fruitYes = new Tab();
        $fruitYes->Type = TabTypeCode::Custom;
        $fruitYes->CustomTabType = CustomTabType::Radio;
        $fruitYes->CustomTabRadioGroupName= "fruit";
        $fruitYes->TabLabel = "Yes";
        $fruitYes->Name = "Yes";
        $fruitYes->Value = "Yes";
        $fruitYes->DocumentID = "1";
        $fruitYes->PageNumber = "1";
        $fruitYes->RecipientID = $RecipientID;
        $fruitYes->XPosition = "202";
        $fruitYes->YPosition = "509";
        array_push($tabs, $fruitYes);
            
        $data1 = new Tab();
        $data1->Type = TabTypeCode::Custom;
        $data1->CustomTabType = CustomTabType::Text;
        $data1->ConditionalParentLabel = "fruit";
        $data1->ConditionalParentValue = "Yes";
        $data1->TabLabel = "Preferred Fruit";
        $data1->Name = "Fruit";
        $data1->DocumentID = "1";
        $data1->PageNumber = "1";
        $data1->RecipientID = $RecipientID;
        $data1->XPosition = "265";
        $data1->YPosition = "547";
        array_push($tabs, $data1);
        */
        
        // eliminate 0th element
        array_shift($tabs);
        
        return $tabs;
    }
    
    function getToken($status, $index,$page = "", $redirectAction = "addfunds") {
        global $_oneSigner;
        $token = null;
        //$_SESSION["embedToken"];
           
        // get recipient token
        $assertion = new RequestRecipientTokenAuthenticationAssertion();
        $assertion->AssertionID = guid();
        $assertion->AuthenticationInstant = todayXsdDate();
        $assertion->AuthenticationMethod = RequestRecipientTokenAuthenticationAssertionAuthenticationMethod::Password;
        $assertion->SecurityDomain = "DocuSign2011Q1Sample";
        
        $recipient = $status->RecipientStatuses->RecipientStatus[$index];
        /*
        $data ='';
        
        foreach($status->CustomFields->CustomField as $k => $v){
            $data .=$v->Value;
        }
        */
        
        $urls = new RequestRecipientTokenClientURLs();

        $urlbase = $redirectAction;

        /*$urlbase = getCallbackURL($page);
        if($page == ''){
            if((strpos($urlbase,'agent')===false) && (strpos($urlbase,$redirectActionad)===false)){
                $urlbase .= $redirectAction . '/';
            }
        }*/
        $urlbase = $urlbase . "?hstr=".$status->CustomFields->CustomField[0]->Value."&hid=".$status->EnvelopeID;
        
        //varExport($recipient);
        
        $urls->OnAccessCodeFailed = $urlbase . "&event=AccessCodeFailed&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnCancel = $urlbase . "&event=Cancel&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnDecline = $urlbase . "&event=Decline&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnException = $urlbase . "&event=Exception&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnFaxPending = $urlbase . "&event=FaxPending&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnIdCheckFailed = $urlbase . "&event=IdCheckFailed&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnSessionTimeout = $urlbase . "&event=SessionTimeout&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnTTLExpired = $urlbase . "&event=TTLExpired&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        $urls->OnViewingComplete = $urlbase . "&event=ViewingComplete&uname=" . $recipient->UserName."&ucid=". $recipient->ClientUserId;
        if ($_oneSigner) {
            
            $urls->OnSigningComplete = $urlbase . "&event=SigningComplete&ucid=". $recipient->ClientUserId;
        }
        else {
            $urls->OnSigningComplete = getCallbackURL("pop2") . "?envelopeID=" . $status->EnvelopeID;
        }
        
        $api = getAPI();
        $rrtParams = new RequestRecipientToken();
        $rrtParams->AuthenticationAssertion = $assertion;
        $rrtParams->ClientURLs = $urls;
        $rrtParams->ClientUserID = $recipient->ClientUserId;
        $rrtParams->Email = $recipient->Email;
        $rrtParams->EnvelopeID = $status->EnvelopeID;
        $rrtParams->Username = $recipient->UserName;
        
        try {
            $token = $api->RequestRecipientToken($rrtParams)->RequestRecipientTokenResult;
        } catch (SoapFault $e) {
            //$_SESSION["errorMessage"] = $e;
            //varExport($e);
            echo $e->getMessage();
//            header("Location: error.php");
        }
        
         $_SESSION["embedToken"] = $token;
         header("Location: ".$token);
    }
    
    function getStatus($envelopeID) {
        $status = null;
        
        $api = getAPI();
        
        $rsParams = new RequestStatus();
        $rsParams->EnvelopeID = $envelopeID;
        
        try {
            $status = $api->RequestStatus($rsParams)->RequestStatusResult;
        } catch (SoapFault $e) {
            //$_SESSION["errorMessage"] = $e;
            //varExport($e);
            echo $e->getMessage();
//            header("Location: error.php");
        }
        
        return $status;
    }
    
    private function getPdfDbQuery($where){
        $where_str = '';
        if(is_array($where)){
            foreach($where as $k => $v){
                if(empty($where_str)){
                    $where_str = " WHERE ".$k . " = " .$v." ";
                }else{
                    $where_str = " AND ".$k . " = " .$v;
                }
            }
        }elseif(is_string($where)){
            $where_str = " WHERE ".$where;
        }
        return Db::users()->fetchAll("SELECT * FROM users_docusign {$where_str}");
    }
    
    static private $pdf_exists;
    
    static public function PdfDocumentExistsInDb($where){
        
        $result = self::getPdfDbQuery($where);
        if(!empty($result)){
            self::$pdf_exists = 1;
            return $result;
        }else{
            self::$pdf_exists = 0;
            return false;
        }
        return false;
    }
    
    static public function PdfDocumentExistsByEid($eid,$uid = 'this'){
        if($uid == 'this'){
            $uid = Users::getIFCurrentUserId();
            return self::PdfDocumentExistsInDb("eid = '{$eid}' AND uid = '{$uid}'");
        }elseif($uid == '*'){
            return self::PdfDocumentExistsInDb("eid = '{$eid}'");
        }elseif(is_numeric($uid)){
            return self::PdfDocumentExistsInDb("eid = '{$eid}' AND uid = '{$uid}'");
        }
         return false;   
        
    }
    /**
    * fix required
    * 
    * @param mixed $hash
    * @param mixed $userbind
    */
    static public function getDocumentByHashId($hash,$userbind = 1){
        $api = getApi();
        $where = " hash_id = '{$hash}'";
        if($userbind){
            $uid = Users::getIFCurrentUserId();
            $where .=" AND uid = '{$uid}'";
        }
        //$result = Db::users()->fetchOne("SELECT * FROM users_docusign {$where}");
        $result = self::PdfDocumentExistsInDb($where);
        
        if($result){
            self::$pdf_exists = 1;
            $params = new RequestPDF();
            $params->EnvelopeID = $result[0]['eid'];
            $api_result = $api->RequestPDF($params)->RequestPDFResult;
            return $api_result;
        }else{
            return false;
            /*
            self::$pdf_exists = 0;
            $params = new RequestPDF();
            $params->EnvelopeID = $hash;
            $api_result = $api->RequestPDF($params)->RequestPDFResult;
            return $api_result;
            */
        }
    }
    
    static public function getDocumentByEnvId($eid,$search_in = 'both',$userbind = 1){
        $api = getApi();
        //echo 'get1';
        if($search_in == 'docusign'){
            //echo 'get2';
            $params = new RequestPDF();
            $params->EnvelopeID = $eid;
            $api_result = $api->RequestPDF($params)->RequestPDFResult;
            return $api_result;
        }elseif($search_in == 'db'){
            //echo 'get3';
            $where = " eid = '{$eid}'";
            if($userbind){
                $uid = Users::getIFCurrentUserId();
                $where .=" AND uid = '{$uid}'";
            }
            
            $result = self::PdfDocumentExistsInDb($where);
            if($result){
                $api_result=  new EnvelopePDF();
                $api_result->EnvelopeID = $result[0]['eid'];
                $api_result->PDFBytes = $result[0]['data'];
                return $api_result;
            }else{
                return false;
            }
        }
        //echo 'get4';
        $where = " eid = '{$eid}'";
        if($userbind){
            //echo 'get5';
            $uid = Users::getIFCurrentUserId();
            $where .=" AND uid = '{$uid}'";
        }
        //echo 'get6';
        //$result = Db::users()->fetchOne("SELECT * FROM users_docusign {$where}");
        $result = self::PdfDocumentExistsInDb($where);
        //var_dump($result);
        if($result){
            //self::$pdf_exists = 1;
            //echo 'get7';
            $api_result=  new EnvelopePDF();
            $api_result->EnvelopeID = $result[0]['eid'];
            $api_result->PDFBytes = $result[0]['data'];
            return $api_result;
        }else{
            //echo 'get8';
            //return false;
            //self::$pdf_exists = 0;
            $params = new RequestPDF();
            $params->EnvelopeID = $eid;
            $api_result = $api->RequestPDF($params)->RequestPDFResult;
            return $api_result;
        }
    }
    
    
    
    static public function savePdfDocument($eid,$status = null,$hash_id = '',$data = null,$userbind = 1){
        $uid = Users::getIFCurrentUserId();
        if($data == null){
            //echo 'save1';
            $data = self::getDocumentByEnvId($eid,'both',$userbind);
            //echo 'save2';
        }
        if($data instanceof EnvelopePDF){
            //echo 'save3';
            $array = array(
                'uid' => $uid,
                'eid' => $eid,
                //'hash_id' => $hash_id,
                'data' => $data->PDFBytes,
                //'status' => $status,
            );
            //echo self::$pdf_exists;
            if(isset(self::$pdf_exists) && self::$pdf_exists == 1){
                //echo 'save4';
                
                if(!empty($status))
                $array['status'] = $status;
                if(!empty($hash_id))
                $array['hash_id'] = $hash_id;
                //echo "update1";
                Db::users()->update('users_docusign',$array,"eid = '{$eid}' AND uid = '{$uid}'");
            }else{
                //echo 'save5';
                $array['status'] = $status;
                $array['hash_id'] = $hash_id;
                //echo "insert1";
                Db::users()->insert('users_docusign',$array);
            }
            
            return true;
        }elseif(is_array($data)){
            //echo 'save6';
            /*
            Db::users()->insert('users_docusign',array(
                'uid' => Users::getIFCurrentUserId(),
                'eid' => $eid,
                'hash_id' => $hash_id,
                'data' => $data['PDFBytes'],
                'status' => $status,
            ));
            return true;
            */
            $array = array(
                'uid' => $uid,
                'eid' => $eid,
                //'hash_id' => $hash_id,
                'data' => $data['PDFBytes'],
                //'status' => $status,
            );
            
            if($result = self::PdfDocumentExistsByEid($eid)){
                
                if(!empty($status))
                $array['status'] = $status;
                if(!empty($hash_id))
                $array['hash_id'] = $hash_id;
            //echo "update2";
                Db::users()->update('users_docusign',$array,"eid = '{$eid}' AND uid = '{$uid}'");
            }else{
                $array['status'] = $status;
                $array['hash_id'] = $hash_id;
                //echo "insert2";
                Db::users()->insert('users_docusign',$array);
            }
            return true;
        }elseif(is_string($data)){
            if($result = self::PdfDocumentExistsByEid($eid)){
                
                $array = array(
                    'uid' => $uid,
                    'eid' => $eid,
                    //'hash_id' => $hash_id,
                    //'data' => $data,
                    //'status' => $status,
                );
                if(!empty($status))
                $array['status'] = $status;
                if(!empty($hash_id))
                $array['hash_id'] = $hash_id;
                if(!empty($data))
                $array['data'] = $data;
                //echo "update3";
                Db::users()->update('users_docusign',$array,"eid = '{$eid}' AND uid = '{$uid}'");
                return true;
                
            }elseif(!empty($status)&& !empty($hash_id) && !empty($data)){
                //echo "insert3";
                Db::users()->insert('users_docusign',array(
                    'uid' => Users::getIFCurrentUserId(),
                    'eid' => $eid,
                    'hash_id' => $hash_id,
                    'data' => $data,
                    'status' => $status,
                ));
                return true;
            }
        }
        return false;
        
        
    }
    static public function savePdfDocumentNew($data,$where = null,$userbind = 1){
        if(!empty($data)){
            if(empty($where)){
                Db::users()->insert('users_docusign',$data);
                return true;
            }else{
                $result = Db::users()->update('users_docusign',$data,$where);
                return $result;
            }
        }
        
        
    }
    
    static public function getUserPdfArraysFromDb($user_id = null){
        if($user_id == null){
            $user_id = Users::getIFCurrentUserId();
        }
        return self::getPdfDbQuery(" uid = '{$user_id}' ");
    }
    
}
