<?php

class T3Seo_Calendar {
    static public function installCalendarResources(){
        MyZend_Site::addCSS(array(
            'datepicker/ui.all.css',
        ));
        
        MyZend_Site::addJS(array(
            'jquery.ui.core.js',
            'jquery.ui.datepicker.js',
        ));    
    }
    
    /**
    * Нарисовать небольшую версию календаря, для боковой панели
    */
    static public function renderMiniVersion(){           
        echo "<div id='seoCalendarMiniVersion' style='margin:5px;'></div>";
        echo "<script>jQuery(function(){jQuery('#seoCalendarMiniVersion').datepicker();});</script>";
    
    }    
}