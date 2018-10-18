<?php

class Cache_PostingSoldCardRender extends AP_Cache_Abstract {
    static protected $instance;
    /**
    * @return self
    */
    static public function instance(){
        if(is_null(self::$instance)) self::$instance = new self();
        return self::$instance;
    }
    
    protected function select($ids){ 
        return;
    }
    
    public function load($ids){
        Cache_PostingSoldCardInfo::instance()->load($ids);
    }
    
    public function get($id){
        $all = Cache_PostingSoldCardInfo::instance()->get($id);

        if(is_array($all)){

            MyZend_Site::addCSS('sold_card.css');

            return "<div class='sold_card' style='float:none;'>
                    <div>" .
                    (strlen($all['logo']) ?
                        "<div class='sold_card_logo_div'>
                            <img src='" . htmlspecialchars($all['logo']) . "'>
                            </div>" : ""
                    ).
                    "<div class='sold_card_text_div'>" .
                    (strlen($all['title']) ?
                        "<div class='sold_card_title'>{$all['title']}</div>" : ""
                    )
                    .
                    (strlen($all['description']) ?
                        "<div class='sold_card_description'>{$all['description']}</div>" : ""
                    ) .
                    "</div>
                </div>
            </div>";
        }

        return "";
    }
}