<?php

class T3Cache_PostingID {
    static public function get($id, $link = true){

        if(!$link){
            return $id;
        }
        else {                               
            MyZend_Site::addCSS("table/menu.css");
            MyZend_Site::addJS("table/menu.js");
            
            if(T3Users::getCUser()->isRoleAdmin()){
                return "<span 
                    style='color:#007EBB'
                    class='aztable_menu_a'
                    id='aztable_menu_a_{$id}'
                    onmouseover=\"createPostingAdminMenu('{$id}');\" 
                ><span>" . $id . "</span><div class='aztable_menu' id='aztable_menu_list_{$id}' ></div></span>";  
            }
            else if(T3Users::getCUser()->isRoleBuyerAgent()){
                return "<span 
                    style='color:#007EBB'
                    class='aztable_menu_a'
                    id='aztable_menu_a_{$id}'
                    onmouseover=\"createPostingBuyerAgentMenu('{$id}');\" 
                ><span>" . $id . "</span><div class='aztable_menu' id='aztable_menu_list_{$id}' ></div></span>";    
            }
            else {
                return "<a style='color:#007EBB' href='/en/account/posting/main/id/" . $id . "'>" . $id . "</a>";   
            }
            
        }
    } 
}
