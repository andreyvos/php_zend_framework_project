<?php

define('SECRET', 'T3SYSTEM');

class T3Captcha {

    protected static $_instance = null;

    protected $cookie_name = 'CAPTCHA';

    protected $use_session = false;

    protected $use_cookie = true;

    protected $font = '';

    protected $_width = 150;

    protected $_height = 45;

    protected $_fsize = 25;

    protected $_expiration = 600;

    public function __construct() {
        //LIBS
        $this->font = T3SYSTEM_SITELIBS.DIRECTORY_SEPARATOR.'SenZend'.DIRECTORY_SEPARATOR.'Image'.DIRECTORY_SEPARATOR.'Fonts'.DIRECTORY_SEPARATOR.'arial.ttf';
    }

    public static function getInstance() {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


    public function getHtml($use_reload = false) {
        $html = '<img src="'.ROOT.'/index/captcha/"  id="img_captcha" /> ';


        $random_num = rand(5000, 99999);

        $inner_html = '<img src=\'/index/captcha/?rnd='.$random_num.'\'  id=\'img_captcha\' />';

        if ($use_reload)
            $html .= '<a href="#" onclick="reloadCaptcha(); return false;" >Reload</a>';

        $html .= ' <script type="text/javascript"> ';
        $html .= ' function reloadCaptcha() { ';
        $html .= ' var random_num = Math.random(); ';
        $html .= ' document.getElementById(\'img_captcha\').setAttribute( \'src\', \''.ROOT.'/index/captcha/?rnd=\'+ random_num + \'\'); ';
        $html .= ' ';
        $html .= ' } ';
        $html .= ' </script> ';


        return $html;
    }


    public function generate() {
        $font = $this->getFont();

        if (empty($font)) {
            require_once 'SenZend/Exception.php';
            throw new SenZend_Exception("Image CAPTCHA requires font");
        }

        if (!extension_loaded("gd")) {
            require_once 'SenZend/Exception.php';
            throw new SenZend_Exception("Image CAPTCHA requires GD extension");
        }

        if (!function_exists("imagepng")) {
            require_once 'SenZend/Exception.php';
            throw new SenZend_Exception("Image CAPTCHA requires PNG support");
        }

        if (!function_exists("imageftbbox")) {
            require_once 'SenZend/Exception.php';
            throw new SenZend_Exception("Image CAPTCHA requires FT fonts support");
        }



        $w     = $this->getWidth();
        $h     = $this->getHeight();
        $fsize = $this->getFontSize();

        $ABC = 'QWERTYUIPLKJHGFDSAMZNXBCV132654897';

        $CAP_REZ = '';
        for ($c_i = 0; $c_i<=5; $c_i++) {
            $cap_1_r = rand(0,23);
            $cap[0]  = $ABC[$cap_1_r];
            $cap_2_r = rand(0,23);
            $cap[1]  = $ABC[$cap_2_r];
            $cap_3_r = rand(0,23);
            $cap[2]  = $ABC[$cap_3_r];
            $cap_4_r = rand(0,23);
            $cap[3]  = $ABC[$cap_4_r];
            $cap_5_r = rand(1,9);
            $cap[4]  = $cap_5_r;

            $c_tmp = rand(0,4);
            $CAP_REZ .= $cap[$c_tmp];
        }

        $word = strtoupper($CAP_REZ);

        $img        = imagecreatetruecolor($w, $h);
        $text_color = imagecolorallocate($img, 0, 0, 0);
        $bg_color   = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $w-1, $h-1, $bg_color);
        $textbox = imageftbbox($fsize, 0, $font, $word);
        $x = ($w - ($textbox[2] - $textbox[0])) / 2;
        $y = ($h - ($textbox[7] - $textbox[1])) / 2;
        imagefttext($img, $fsize, 0, $x, $y, $text_color, $font, $word);

        // generate noise
        for ($i=0; $i<100; $i++) {
            imagefilledellipse($img, mt_rand(0,$w), mt_rand(0,$h), 2, 2, $text_color);
        }
        for ($i=0; $i<5; $i++) {
            imageline($img, mt_rand(0,$w), mt_rand(0,$h), mt_rand(0,$w), mt_rand(0,$h), $text_color);
        }

        // transformed image
        $img2     = imagecreatetruecolor($w, $h);
        $bg_color = imagecolorallocate($img2, 255, 255, 255);
        imagefilledrectangle($img2, 0, 0, $w-1, $h-1, $bg_color);
        // apply wave transforms
        $freq1 = $this->_randomFreq();
        $freq2 = $this->_randomFreq();
        $freq3 = $this->_randomFreq();
        $freq4 = $this->_randomFreq();

        $ph1 = $this->_randomPhase();
        $ph2 = $this->_randomPhase();
        $ph3 = $this->_randomPhase();
        $ph4 = $this->_randomPhase();

        $szx = $this->_randomSize();
        $szy = $this->_randomSize();

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $sx = $x + (sin($x*$freq1 + $ph1) + sin($y*$freq3 + $ph3)) * $szx;
                $sy = $y + (sin($x*$freq2 + $ph2) + sin($y*$freq4 + $ph4)) * $szy;

                if ($sx < 0 || $sy < 0 || $sx >= $w - 1 || $sy >= $h - 1) {
                    continue;
                } else {
                    $color    = (imagecolorat($img, $sx, $sy) >> 16)         & 0xFF;
                    $color_x  = (imagecolorat($img, $sx + 1, $sy) >> 16)     & 0xFF;
                    $color_y  = (imagecolorat($img, $sx, $sy + 1) >> 16)     & 0xFF;
                    $color_xy = (imagecolorat($img, $sx + 1, $sy + 1) >> 16) & 0xFF;
                }
                if ($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255) {
                    // ignore background
                    continue;
                }
                elseif ($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0) {
                    // transfer inside of the image as-is
                    $newcolor = 0;
                }
                else {
                    // do antialiasing for border items
                    $frac_x  = $sx-floor($sx);
                    $frac_y  = $sy-floor($sy);
                    $frac_x1 = 1-$frac_x;
                    $frac_y1 = 1-$frac_y;

                    $newcolor = $color    * $frac_x1 * $frac_y1
                                + $color_x  * $frac_x  * $frac_y1
                                + $color_y  * $frac_x1 * $frac_y
                                + $color_xy * $frac_x  * $frac_y;
                }
                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newcolor, $newcolor, $newcolor));
            }
        }

        // generate noise
        for ($i=0; $i<100; $i++) {
            imagefilledellipse($img2, mt_rand(0,$w), mt_rand(0,$h), 2, 2, $text_color);
        }
        for ($i=0; $i<5; $i++) {
            imageline($img2, mt_rand(0,$w), mt_rand(0,$h), mt_rand(0,$w), mt_rand(0,$h), $text_color);
        }


        $cookie_value = md5(SECRET . $word);
        //setrawcookie('CAPTCHA', $cookie_value); // , time()+$this->_expiration+25000);
        $_SESSION['CAPTCHA'] = $cookie_value;

        header('Content-type: image/png');

        imagepng($img2);
        imagedestroy($img);
        imagedestroy($img2);

        //return $out;
    }

    public function isValid($data) {


        if (!isset($_SESSION['CAPTCHA']))
            return false;

        if (!defined('SECRET'))
            return false;

        if ($data == '' || strlen($data) < 3)
            return false;

        $data = strtoupper(htmlspecialchars($data));


        $secret = md5(SECRET . $data);

        if ($_SESSION['CAPTCHA'] == $secret)
            return true;

        return false;
    }

    public function getFont() {
        return $this->font;
    }

    public function getFontSize() {
        return $this->_fsize;
    }

    public function getHeight() {
        return $this->_height;
    }

    public function getWidth() {
        return $this->_width;
    }

    public function setFont($font) {
        $this->font = $font;
    }

    protected function _randomFreq() {
        return mt_rand(700000, 1000000) / 15000000;
    }

    protected function _randomPhase() {
        // random phase from 0 to pi
        return mt_rand(0, 3141592) / 1000000;
    }

    protected function _randomSize() {
        return mt_rand(300, 700) / 100;
    }
}