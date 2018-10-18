<?php
/*

Класс для работы с изображением

Для фотографирования сайтов необходим web proxy с интерфейсом {domain}/proxy/?p={url}

*/

class T3Images_Main {
    public $rand; # Случайное число, включено в название временного файла
    public $full_filename = null; # Путь к файлу полноразмерной фотографии сайта
    public $dst_filename = null; # Путь к рабочему файлу

    function __constuct(){
        
    }
    
    # Фотография нового изображения
    function cutycapt($url,$width = null){
        /*
        http://cutycapt.sourceforge.net/

        Usage

        Open a command prompt and ask for help:

         % CutyCapt --help
         -----------------------------------------------------------------------------
         Usage: CutyCapt --url=http://www.example.org/ --out=localfile.png
         -----------------------------------------------------------------------------
          --help                                Print this help page and exit
          --url=<url>                           The URL to capture (http:...|file:...|...)
          --out=<path>                          The target file (.png|pdf|ps|svg|jpeg|...)
          --out-format=<f>                      Like extension in --out, overrides heuristic
          --min-width=<int>                     Minimal width for the image (default: 800)
          --max-wait=<ms>                       Don't wait more than (default: 90000, inf: 0)
          --delay=<ms>                          After successful load, wait (default: 0)
          --user-styles=<url>                   Location of user style sheet, if any
          --header=<name>:<value>               request header; repeatable; some can't be set
          --method=<get|post|put>               Specifies the request method (default: get)
          --body-string=<string>                Unencoded request body (default: none)
          --body-base64=<base64>                Base64-encoded request body (default: none)
          --app-name=<name>                     appName used in User-Agent; default is none
          --app-version=<version>               appVers used in User-Agent; default is none
          --user-agent=<string>                 Override the User-Agent header Qt would set
          --javascript=<on|off>                 JavaScript execution (default: on)
          --java=<on|off>                       Java execution (default: unknown)
          --plugins=<on|off>                    Plugin execution (default: unknown)
          --private-browsing=<on|off>           Private browsing (default: unknown)
          --auto-load-images=<on|off>           Automatic image loading (default: on)
          --js-can-open-windows=<on|off>        Script can open windows? (default: unknown)
          --js-can-access-clipboard=<on|off>    Script clipboard privs (default: unknown)
         -----------------------------------------------------------------------------
          <f> is svg,ps,pdf,itext,html,rtree,png,jpeg,mng,tiff,gif,bmp,ppm,xbm,xpm
         -----------------------------------------------------------------------------
         http://cutycapt.sf.net - (c) 2003-2008 Bjoern Hoehrmann - bjoern@hoehrmann.de

        Build Instructions

        If your system is set up to compile Qt applications, building CutyCapt should be a simple matter of checking out the source code and running qmake and your version of make. 
        As an example, if you are running Ubuntu Hardy Heron and have configured the system to use packages from hardy-backports, the following should do:

          % sudo apt-get install subversion libqt4-webkit libqt4-dev g++
          % svn co https://cutycapt.svn.sourceforge.net/svnroot/cutycapt
          % cd cutycapt/CutyCapt
          % qmake
          % make
          % ./CutyCapt --url=http://www.example.org --out=example.png
         
        */
        
        /* ----------------------------------------------------------------- */
        $add_pars = ""; # дополнтельные параметры
        
        if($width!=null && is_integer($width) && $width>0 && $width<3200){
            $add_pars.= " --min-width={$width}";            
        }
        /* ----------------------------------------------------------------- */

        $this->rand = time() . "_" .  ceil(microtime()*1000) . rand(1000,9999);
        $this->full_filename =  dirname(__FILE__) . "/temp/{$this->rand}.png";
        
        if(strtolower(substr(PHP_OS,0,3)) == 'win'){
            $bin_cc = 'CutyCapt.exe';
            $add_display = '';
        }
        else {
            $bin_cc = 'CutyCapt'; 
            $add_display = 'DISPLAY=:0 ';   
        }
        
        $str = $add_display . dirname(__FILE__) . "/cutycapt/{$bin_cc} --url=http://{$_SERVER['HTTP_HOST']}/T3System/scripts/proxy/index.php?p=" . urlencode($url) . " --out={$this->full_filename}{$add_pars}";
        exec($str);
        //$img_bin = file_get_contents($img_file_name);
        //$img_base64 = base64_encode($img_bin);
        //unlink($img_file_name);
        
        //print "<img src='img/{$rand}.png' />";
        //print "<img alt=\"Embedded Image\" src=\"data:image/png;base64,{$img_base64}\" />";    
    }
    
    function __destruct(){
        # Удаление временных файлов
        if(isset($this->full_filename) && is_file($this->full_filename)){
            //unlink($this->full_filename); 
        }  
        
        if(isset($this->dst_filename) && is_file($this->dst_filename)){
            //unlink($this->dst_filename); 
        }       
    }
    
    # изменение исходного изображения до заданных размеров
    function img_fix_size($option){
        if(!isset($option['width']))    $option['width']    = '240';
        if(!isset($option['height']))   $option['height']   = '180';
        if(!isset($option['valign']))   $option['valign']   = 'center'; // значения: left center right
        if(!isset($option['halign']))   $option['halign']   = 'middle'; // значения: top middle bottom
        if(!isset($option['type']))     $option['type']     = 'png';    // значения: png jpg  
        if(!isset($option['source']))   $option['source']   = 'png';    // значения: png jpg  
        if(!isset($option['quality']))  $option['quality']  = '90';    // only jpg (0-100)
        
        
        # получение информации о исходном изображении, и создание его экземпляра - $src_image
        list($src_width, $src_height) = getimagesize($this->full_filename);
        $source_img_type = image_type_to_mime_type($this->full_filename);
        if($option['source'] == "png"){
            $src_image = imagecreatefrompng($this->full_filename);
        }
        else {
            $src_image = imagecreatefromjpeg($this->full_filename);      
        }
        
        # создание новоого изображения, с заданными размерами
        $dst_image = imagecreatetruecolor($option['width'],$option['height']);
        
        # расчет коэффицентов уменьшения
        $k_w = $src_width/$option['width'];
        $k_h = $src_height/$option['height'];
        
        if($k_w == $k_h){
            # пропорции исходного и нового изображения не отличаются
            
            # исходное изображение
            $src_x = 0;
            $src_y = 0;
            $src_w = $src_width;
            $src_h = $src_height;
            
            # новое изображение
            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $option['width'];
            $dst_h = $option['height'];
        }
        else if($k_w<$k_h){
            # Коэффицент ширины меньше коэфицента высоты. Исходное изображение обрезается по высоте
            
            # исходное изображение
            $src_x = 0;
            if($option['halign']=="top")            $src_y = 0;
            else if($option['halign']=="middle")    $src_y = floor(($src_height-($option['height']*$k_h))/2);
            else if($option['halign']=="bottom")    $src_y = floor($src_height-($option['height']*$k_h));
            $src_w = $src_width;
            $src_h = $option['height']*$k_w;
            
            # новое изображение
            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $option['width'];
            $dst_h = $option['height']; 
        }
        else if($k_w>$k_h){
            # Коэффицент ширины больше коэфицента высоты. Исходное изображение обрезается по высоте
            
            # исходное изображение
            if($option['valign'] == "left")         $src_x = 0; 
            else if($option['valign'] == "center")  $src_x = floor(($src_width-($option['width']*$k_w))/2);  
            else if($option['valign'] == "right")   $src_x = floor($src_width-($option['width']*$k_w)); 
            $src_y = 0;
            $src_w = $option['width']*$k_h;
            $src_h = $src_height;
            
            # новое изображение
            $dst_x = 0;
            $dst_y = 0;
            $dst_w = $option['width'];
            $dst_h = $option['height'];
        }
        
        imagecopyresampled($dst_image,$src_image,$dst_x,$dst_y,$src_x,$src_y,$dst_w,$dst_h,$src_w,$src_h);
        
        
        if($option['type'] == "png"){
            imagepng($dst_image,$this->dst_filename);
        }
        else if($option['type'] == "jpg"){
            imagejpeg($dst_image,$this->dst_filename,$option['quality']);    
        }  
        
        die;         
        @chmod($this->dst_filename, 0777); 
               
    }
    
    
}
?>