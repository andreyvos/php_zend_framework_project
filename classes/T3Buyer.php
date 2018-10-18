<?php
/**
* Класс Баера (Компания покупающая лиды)
* 
* 
* @author Anton S. Panfilov
* @version 1.0
* @copyright anton.panfilov@gmail.com  
*/
  
AZend_DB_ObjectSettings::addTable('users_company_buyer', array(
    'id',
    'systemName',
    
    'timezone',
    
    'companyName',
    'Country',
    'State',
    'City',
    'ZIP',
    'Address',
    'tech_emails',
    
    
    '_users' => array(
        'type'              =>  'objects', 
        'class'             =>  'T3Buyer_User', 
        'relationField'     =>  'company_id',
    ),
    
    '_channels' => array(
        'type'              =>  'objects', 
        'class'             =>  'T3Buyer_Channel', 
        'relationField'     =>  'buyer_id',
    ),
    
));

class T3Buyer extends AZend_DB_Object {
    protected $dbObj_tableName = 'users_company_buyer';
    
    /**
    * ID баера
    * 
    * @var int
    */
    protected $id;  
    
    /**
    * Короткое имя баера
    * 
    * @var string
    */
    public $systemName; 
    
    /**
    * timeZone баера по умолчанию
    * 
    * @var string
    */
    public $timezone; 
    
    
    /**
    * Полное название компании
    * 
    * @var string
    */
    public $companyName; 
    
    /**
    * @var string
    */
    public $Country;
    
    /**
    * @var string
    */
    public $State;
    
    /**
    * @var string
    */
    public $City;
    
    /**
    * @var string
    */
    public $ZIP;
    
    /**
    * @var string
    */
    public $Address;
    
    /**
    * @var string
    */
    public $tech_emails;
    
    public $_users;
    
    
    public function getBalance(){
        return $this->getCompanyVal('balance');
    }
    
    public function getStatus(){
        return $this->getCompanyVal('status');  
    }
    
    public function getCompanyVal($name){
        /*
        if($this->getID()){
            return $this->database->fetchOne("select {$name} from users_company where id='" . $this->getID() . "'");    
        } 
        else {
            return null;    
        }
        */
        return null;
    }
     
    static public function renderBuyerPhones( $buyerId )
    {
        $buyerId = (int)$buyerId;
        $db = T3Db::api();
        
        $b_users = $db->fetchAll("SELECT * FROM users WHERE company_id = ".$buyerId." ");
        //varDump2($b_users);
        $html = "";
        
        if( count($b_users) ) {
//            $html .= "
//            <a href=\"/en/account/buyers/main?id=".$buyerId."#usersArea\" target=\"_blank\" title=\"Edit\" style=\"display:block;color:#000;text-decoration:none;\">";
//            //<span style=\"color: #777777; font-size: 14px;\">Phones</span><br />";
            $html .= "
            <a href=\"/en/account/buyers/main?id=".$buyerId."&action=buyerusers\" target=\"_blank\" title=\"Edit\" style=\"display:block;color:#000;text-decoration:none;\">";
            //<span style=\"color: #777777; font-size: 14px;\">Phones</span><br />";
            $first = true;
            foreach ($b_users AS $key=>$uv) {
                if(!$first){
                    $html .= "<br>"; 
                }
                $first = false; 
                
                $phones = unserialize($uv['phones']);
                //varDump2($uv);
                if(count($phones) && strlen($uv['phones']) > 7 ) {
                    //$html .= "<span style=\"color: #777777; font-size: 12px;\">\n";
                    $html .= "<b style='font-weight: bold;'>".$uv['first_name']." ".$uv['last_name']."</b><br />\n ";
                    foreach ($phones AS $phone) $html .=  "<span style='color:#999'> " . $phone['type']. "</span>&nbsp;" . $phone['phone'] . "<br />\n";
                    $html .= '<span style="color:#999">Email: </span>&nbsp;'.$uv['email']."<br />\n";
                    //$html .= "<span>\n";
                    $html .= "\n";
                }
            }
            $html .= "</a>\n";
        
        } else {
//            $html .= "Not Phones. <a href=\"/en/account/buyers/main?id=".$buyerId."#usersArea\" target=\"_blank\" title=\"Edit\">";
            $html .= "No Phones. <a href=\"/en/account/buyers/main?id=".$buyerId."&action=buyerusers\" target=\"_blank\" title=\"Edit\">";
            $html .= "Edit</a>\n";
        }
        return $html;
    }

	public function generatePassword ($length = 8)
	  {
	
	    // start with a blank password
	    $password = "";
	
	    // define possible characters - any character in this string can be
	    // picked for use in the password, so if you want to put vowels back in
	    // or add special characters such as exclamation marks, this is where
	    // you should do it
	    $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
	
	    // we refer to the length of $possible a few times, so let's grab it now
	    $maxlength = strlen($possible);
	  
	    // check for length overflow and truncate if necessary
	    if ($length > $maxlength) {
	      $length = $maxlength;
	    }
	
	    // set up a counter for how many characters are in the password so far
	    $i = 0; 
	    
	    // add random characters to $password until $length is reached
	    while ($i < $length) { 
	
	      // pick a random character from the possible ones
	      $char = substr($possible, mt_rand(0, $maxlength-1), 1);
	        
	      // have we already used this character in $password?
	      if (!strstr($password, $char)) { 
	        // no, so it's OK to add it onto the end of whatever we've already got...
	        $password .= $char;
	        // ... and increase the counter by one
	        $i++;
	      }
	
	    }
	
	    // done!
	    return $password;
	
	 }
	 
    public function emailBuyerCredentialsAction($firstName, $lastName, $email, $login, $password)
    {
   	
    	$body ="
			<html>
			<head>
			<title>t3leads newsletter</title>
			<style type=\"text/css\">
			<!--
			body { background: #272215; padding:0; margin:0; color:#000; font-family: Arial, Helvetica, sans-serif; }
			a { color: #000; text-decoration:underline; }
			a:hover { color: #000; text-decoration:none; }
			img {border:0;}
			-->
			</style>
			<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
			</head>
			
			<body bgcolor=\"#272215\" style=\"backgorund:#272215;\">
			<table style=\"width: 100%; background: none repeat scroll 0% 0% #272215;\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" align=\"center\" bgcolor=\"#fef9ee\">
			<tbody>
			<tr>
			<td align=\"center\">
			<div style=\"width: 600px; margin: 0pt auto;\">
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" align=\"center\" bgcolor=\"#ffffff\">
			<tbody>
			
			<tr>
			<td width=\"600\" height=\"38\" valign=\"top\" bgcolor=\"#272215\">
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" align=\"left\">
			<tbody>
			<tr>
			<td style=\"text-align:left;\" width=\"390\" height=\"38\" align=\"left\" bgcolor=\"#272215\"></td>
			<td style=\"text-align:right;\" width=\"210\" height=\"38\" align=\"right\" bgcolor=\"#272215\"><span style=\"text-align: right; font-size: xx-small; color: #cabc94;\"><a style=\"color:#C99011;\" href=\"#\" target=\"_blank\"></a> <a style=\"color: #c99011;\" href=\"{system:websiteURL}\" target=\"_blank\"><span style=\"color: #c99011;\">Visit website</span></a></span></td>
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			<tr>
			
			<td style=\"background-color:#6A5F43; text-align:left;\" width=\"600\" height=\"232\" align=\"left\" valign=\"top\" bgcolor=\"#6a5f43\">
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" align=\"left\">
			<tbody>
			<tr>
			<td style=\"background-color:#6A5F43; text-align:left;\" width=\"26\" height=\"232\" align=\"left\" valign=\"top\" bgcolor=\"#6a5f43\"><strong><span style=\"font-size: medium; font-family: times new roman,times;\"><img style=\"border: 0pt none;\" src=\"http://t3leads.com/img/emails/newNewsletter/topleft.jpg\" alt=\"\" width=\"26\" height=\"102\" /></span></strong></td>
			<td style=\"background-color: #6a5f43; text-align: center;\" width=\"364\" height=\"232\" valign=\"top\" bgcolor=\"#6a5f43\">
			<p><strong><span style=\"font-size: medium; font-family: times new roman,times;\"><img style=\"border: 0pt none;\" src=\"http://t3leads.com/img/emails/newNewsletter/topcenter.jpg\" alt=\"\" width=\"364\" height=\"102\" /></span></strong></p>
			<p><strong><span style=\"font-size: medium; font-family: times new roman,times;\"><br /></span></strong></p>
			<p style=\"text-align: center;\"><strong><span style=\"font-size: medium; font-family: times new roman,times; color: #000000;\"><span><br /></span></span><span style=\"text-align: left; font-size: medium; font-family: times new roman,times; color: #000000;\"> </span></strong></p>
			</td>
			<td style=\"background-color:#6A5F43; text-align:left;\" width=\"210\" height=\"232\" align=\"left\" valign=\"top\" bgcolor=\"#6a5f43\"><img style=\"border: 0pt none;\" src=\"http://t3leads.com/img/emails/newNewsletter/topright.jpg\" alt=\"\" width=\"210\" height=\"232\" /></td>
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			
			<tr>
			<td width=\"600\">
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" align=\"left\">
			<tbody>
			<tr>
			<td width=\"26\">&nbsp;</td>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\" valign=\"top\"><!-- LEFT COLUMN --> 
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"548\" align=\"left\">
			<tbody>
			<tr>
			<td width=\"548\" height=\"20\">&nbsp;</td>
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"></td>
			</tr>
			<tr>
			<td width=\"548\" height=\"15\" align=\"left\">&nbsp;</td>
			
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"><span style=\"text-align: left; font-size: x-small; color: #000000;\"><strong><br /></strong></span></td>
			</tr>
			<tr>
			<td width=\"548\" height=\"15\" align=\"left\">&nbsp;</td>
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"><!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
			<html>
			<head>
			<title>Untitled document</title>
			</head>
			<body>
			<p><span style=\"font-size: small;\">Dear {user:nickname},</span></p>
			
			<p><span style=\"font-size: small;\">Great news!</span></p>
			<p><span style=\"font-size: small;\">As a leader in the affiliate marketing arena, T3LEADS continues to enhance the services we provide our lenders. </span></p>
			<p><span style=\"font-size: small;\">For your convenience, we have created an account for you to view a wide variety of custom information.</span></p>
			<p><span style=\"font-size: small;\">To access your account, please use the following user name and password:</span></p>
			<p style=\"padding-left: 30px;\"><span style=\"font-size: small;\">Username: {user:login}</span></p>
			<p style=\"padding-left: 30px;\"><span style=\"font-size: small;\">Password: {user:password}</span></p>
			<p><span style=\"font-size: small;\">Your account will provide information such as:</span></p>
			<ul>
			<li><span style=\"font-size: small;\">Detailed and summary information for your leads.</span></li>
			
			<li><span style=\"font-size: small;\">Summary and detailed reports on your convertions, including a handy conversion calendar.</span></li>
			<li><span style=\"font-size: small;\">Information on your postings.</span></li>
			<li><span style=\"font-size: small;\">An invoice list.</span></li>
			<li><span style=\"font-size: small;\">Quick access to your T3LEADS support team.</span></li>
			<li><span style=\"font-size: small;\">And much more...</span></li>
			</ul>
			<p><span style=\"font-size: small;\">We are confident you will find this information incredibly useful. Please browse through your reports to see the many valuable tools that are now available to you. And, please feel free to provide any comments and feedback regarding our products and services.<br /></span></p>
			<p><span style=\"font-size: small;\">Thank you for your continued commitment to T3LEADS.</span></p>
			</body>
			</html></td>
			</tr>
			<tr>
			
			<td width=\"548\" height=\"15\" align=\"left\">&nbsp;</td>
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"></td>
			</tr>
			<tr>
			<td width=\"548\" height=\"15\" align=\"left\">&nbsp;</td>
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"><span style=\"text-align: left; font-size: x-small; color: #000000;\"><br /></span></td>
			</tr>
			<tr>
			<td width=\"548\" height=\"15\" align=\"left\">&nbsp;</td>
			</tr>
			<tr>
			<td style=\"text-align:left;\" width=\"548\" align=\"left\"></td>
			</tr>
			
			<tr>
			<td width=\"548\" height=\"25\" align=\"left\">&nbsp;</td>
			</tr>
			</tbody>
			</table>
			<!-- .LEFT COLUMN --></td>
			<td width=\"26\">&nbsp;</td>
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			<tr>
			<td width=\"600\" height=\"25\" bgcolor=\"#272215\"></td>
			</tr>
			<tr>
			<td width=\"600\" height=\"38\" valign=\"top\" bgcolor=\"#272215\">
			
			<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\" align=\"left\">
			<tbody>
			<tr>
			<td style=\"text-align:left;\" width=\"369\" height=\"38\" align=\"left\" valign=\"top\" bgcolor=\"#272215\"><span style=\"text-align: left; font-size: xx-small; color: #cabc94;\">CAN-SPAM Compliant D and D Marketing, Inc fully complies with the CAN-SPAM Act of 2003. You are receiving this email because you gave us your affirmative consent to receive commercial information from D and D Marketing, Inc or one of our wholly-owned subsidiaries. Our records show that you worked with D and D Marketing. </span><br /><br /> </td>
			<td style=\"text-align:left;\" width=\"20\" align=\"left\" valign=\"top\" bgcolor=\"#272215\">&nbsp;</td>
			<td></td>
			<td style=\"text-align:left;\" width=\"1\" height=\"100%\" align=\"left\" valign=\"top\" bgcolor=\"#3e3827\">&nbsp;</td>
			<td></td>
			<td style=\"text-align:left;\" width=\"20\" align=\"left\" valign=\"top\" bgcolor=\"#272215\">&nbsp;</td>
			<td></td>
			<td style=\"text-align:left;\" width=\"190\" align=\"left\" valign=\"top\" bgcolor=\"#272215\"><span style=\"text-align: left; font-size: xx-small; color: #cabc94;\">If you have any questions, please contact us at: 877.7.T3LEADS</span><br /><br /><br /> <span style=\"text-align: left; font-size: xx-small; color: #cabc94;\">D and D Marketing, Inc (T3Leads.com) 15503 Ventura Blvd. #300 | Encino | CA 91436</span></td>
			
			</tr>
			</tbody>
			</table>
			</td>
			</tr>
			<tr>
			<td width=\"600\" height=\"50\" bgcolor=\"#272215\"></td>
			</tr>
			</tbody>
			</table>
			</div>
			</td>
			</tr>
			</tbody>
			</table>
			</body>
			</html>
		";
    	
    	$date = date ("Y-m-d H:i:s");

    	$fullName = $firstName." ".$lastName;
   			
    	$newbody = str_replace("{user:nickname}",$fullName,$body);
    	$newbody = str_replace("{user:login}",$login,$newbody);
    	$newbody = str_replace("{user:password}",$password,$newbody);
    	// send an email
		 
    
		$mail = new Zend_Mail();
		$mail->setBodyHtml($newbody);
		$mail->setFrom('account@t3leads.com', 'T3Leads Team');
		$mail->addTo($email, $fullName);
		$mail->setSubject('Account Information');
		$mail->send();
    		
    }

    public function getInvoiceEmails(){
        return T3Db::apiReplicant()->fetchOne("SELECT `invoices_emails` FROM `users_company_buyer` WHERE id=?", $this->id);
    }
}
