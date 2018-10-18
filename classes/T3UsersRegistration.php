<?php


class T3UsersRegistration
{
	public function sendNotifications()
	{	
		$message = T3Db::api()->fetchOne("SELECT text FROM webmasters_signup_followup WHERE id=1");
		
		$users = T3UserWebmasterAgents::getUsers();
		
		foreach ($users as $user)
		{
			$login = $user['login'];
			//check to see if email was already send more than 2 time to the user
			$userInLogs = T3Db::api()->fetchAll("SELECT * FROM registration_followup_log WHERE user='$login'");
			
			if (isset($userInLogs) && !empty($userInLogs))
			{
				foreach ($userInLogs as $userInLog)
				{
					if ((int)$userInLog['count']<3)
					{
						$text = (string)$message;	
						$userObject = T3Users::getUserById((int)$user['id']);
						$username = $user['nickname'];
						$text = str_replace ("{user:nickname}",$username,$text);
						$text = str_replace ('{link}',(string)T3Users::getActivationLink($userObject),$text);
						
						// send an email
						$mail = new Zend_Mail();
						$mail->setBodyHtml($text);
						$mail->setFrom('newsletter@t3leads.com', 'T3Leads Team');
						$mail->addTo($user['email'], $user['nickname']);
						$mail->setSubject('Account Activation');
						$mail->send();
						
						$email = $user['email'];
						$login = $user['login'];
						$date = date ("Y-m-d H:i:s");
						// log to database
						T3Db::api()->query("INSERT INTO registration_followup_log (user,email,date,count) VALUES ('$login','$email','$date','1') ON DUPLICATE KEY UPDATE date='$date', count=count+1");
						
					}
				}
				 
			}
			else 
			{
				$text = (string)$message;	
				$userObject = T3Users::getUserById((int)$user['id']);
				$username = $user['nickname'];
				$text = str_replace ("{user:nickname}",$username,$text);
				$text = str_replace ('{link}',(string)T3Users::getActivationLink($userObject),$text);
				
				// send an email
				$mail = new Zend_Mail();
				$mail->setBodyHtml($text);
				$mail->setFrom('newsletter@t3leads.com', 'T3Leads Team');
				$mail->addTo($user['email'], $user['nickname']);
				$mail->setSubject('Account Activation');
				$mail->send();
				
				$email = $user['email'];
				$login = $user['login'];
				$date = date ("Y-m-d H:i:s");
				// log to database
				T3Db::api()->query("INSERT INTO registration_followup_log (user,email,date,count) VALUES ('$login','$email','$date','1') ON DUPLICATE KEY UPDATE date='$date', count=count+1");
			}
			
			

		}
	}
}