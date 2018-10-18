<?php

class T3Cache_LeadsTabSetup {
    static public $is = false;
    
    static public function setup(){
        
        if(self::$is == false){
            self::$is = true;
            
            MyZend_Site::addCSS(array(
                'ui-themes/smoothness/jquery.ui.all.css',
                'table/lead.tab.css',
                'lead/body.css',
                'tablesorter/style.css',
                'table/status.css',
                'lead/post-to-pingtree.css',
            ));
            
            MyZend_Site::addJS(array(
                'table/lead.tab.js',
                
                'lead/reject.js', 
                'lead/verification.js', 
                'lead/body.js',
                'lead/post_to_buyer.js',
                'lead/post_to_pingtree.js',
                
                'jquery.tablesorter.js',
                "jquery.color.js",
                "jquery.numeric.pack.js",
                
                'jquery.ui/jquery-ui-1.8.2.custom.js',
                'jquery.ui/minified/jquery.ui.widget.min.js',
                'jquery.ui/minified/jquery.ui.mouse.min.js',
                'jquery.ui/minified/jquery.ui.draggable.min.js',
                'jquery.ui/minified/jquery.ui.position.min.js',
                'jquery.ui/minified/jquery.ui.resizable.min.js',
                'jquery.ui/minified/jquery.ui.dialog.min.js',
                'jquery.ui/minified/jquery.ui.tabs.min.js',  
                
                'jquery.ui/minified/jquery.effects.core.min.js',
                'jquery.ui/minified/jquery.effects.drop.min.js',
                'jquery.ui/minified/jquery.effects.explode.min.js',
            ));
        }
    }   
}