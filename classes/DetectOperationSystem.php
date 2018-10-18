<?php

class DetectOperationSystem {

    public static function Detect(){
        //echo $_SERVER['HTTP_USER_AGENT']."<br>\r\n";
        
        $result = "";
        
        /*
        // Match user agent string with operating systems
        'Windows 3.11' => 'Win16',
        'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
        'Windows 98' => '(Windows 98)|(Win98)',
        'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
        'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
        'Windows Server 2003' => '(Windows NT 5.2)',
        'Windows Vista' => '(Windows NT 6.0)',
        'Windows 7' => '(Windows NT 6.1)|(Windows NT 7.0)',
        'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
        'Windows ME' => 'Windows ME',
        */

        $OSList = array(
            'Windows' => 'Windows',
            'Open BSD' => 'OpenBSD',
            'Sun OS' => 'SunOS',
            'Linux' => '((Linux)|(X11))',
            'Mac OS' => '((Mac_PowerPC)|(Macintosh))',
            'QNX' => 'QNX',
            'BeOS' => 'BeOS',
            'OS/2' => 'OS\/2',
            'iPhone' => 'iPhone OS',
            'iPad' => 'iPad',
            'Symbian' => 'Symbian',
            'BlackBerry' => 'BlackBerry',
            'Search Bot' => '((nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves\/Teoma)|(ia_archiver))',
        );

        // Loop through the array of user agents and matching operating systems
        foreach($OSList as $CurrOS => $Match){
            // Find a match
            if (preg_match('/' . $Match . "/i", ifset($_SERVER['HTTP_USER_AGENT']))){
                $result = $CurrOS;
                // We found the correct match
                break;
            }
        }
        
        // You are using Windows Vista
        return $result;
    }
}

