<?php

class T3Ui_Paging {
    
    /**
    * Количесво элементов
    * @var int
    */
    public $countElements;
    
    /**
    * Количесво страниц
    * @var int
    */
    public $pagesCount;
    
    /**
    * Максимальное количесво элементов на странице
    * @var int
    */
    public $pageLen = 20;
    
    /**
    * Текущая страница
    * @var int
    */
    public $curentPage;
    
    /**
    * С какого элемента надо делать выборку из общего списка
    * @var int
    */
    public $limitOffset;
    
    /**
    * Сколько элементов будет на этой странице
    * @var int
    */
    public $limitCount;
    
    public function run($countElements, $page = 1){
        $this->countElements = (int)$countElements;
        $this->curentPage = (int)$page;
        
        $this->pagesCount = ceil($this->countElements / $this->pageLen);
        
        if($this->curentPage > $this->pagesCount)$this->curentPage = $this->pagesCount;
        if($this->curentPage < 1)$this->curentPage = 1; 

        $this->limitOffset = $this->pageLen*($this->curentPage-1);
        if($this->curentPage == $this->pagesCount)  $this->limitCount = $this->countElements - ($this->pageLen*($this->pagesCount-1));
        else                                        $this->limitCount = $this->pageLen;    
    }
    
    
    public function RunAndRender($countElements, $getValueName = '_page', $len_block = 11){
        $this->run($countElements, (int)ifset($_POST[$getValueName], ifset($_GET[$getValueName], 1))); 
        return self::render($this->pagesCount, $this->curentPage, $this->countElements, $getValueName, $len_block);        
    }
    
    public function runObject($countElements, $getValueName = '_page'){
        $this->run($countElements, (int)ifset($_POST[$getValueName], ifset($_GET[$getValueName], 1)));       
    }
    
    public function renderObject($getValueName = '_page', $len_block = 11){
        return self::render($this->pagesCount, $this->curentPage, $this->countElements, $getValueName, $len_block);        
    }
    
    /**
    * put your comment there...
    * 
    * @param mixed $pagesCount
    * @param mixed $curentPage
    * @param mixed $allElements
    * @param mixed $getValueName
    * @param mixed $len_block количество отображаемых ссылок перехода на новую страницу
    * @return string
    */
    static public function render($pagesCount, $curentPage, $allElements, $getValueName = '_page', $len_block = 11){
        MyZend_Site::addCSS("paging.css");
        
        ob_start(); 
        if($pagesCount > 1) {
            ?><div class="pagingStat">Pages: <?
            
            $page_now = $curentPage-1;
            $page_all = $pagesCount;
            $all_val = $allElements;
            $page_all = $pagesCount;
            
            $start_page = $page_now - floor($len_block/2);
            if($start_page<0)$start_page = "0";
            $finish_page = $start_page+$len_block;
            if($finish_page>$page_all-1)$finish_page = $page_all-1;
            
            $curentUri = $_SERVER['REQUEST_URI'];
            if(strpos($curentUri, "_page={$curentPage}") === false){
                if(strpos($curentUri, "?") === false){
                    $pagingLink = $curentUri . "?_page={PageNum}";    
                }
                else {
                    $pagingLink = $curentUri . "&_page={PageNum}";   
                }
            }
            else {
                $pagingLink = str_replace("_page={$curentPage}", "_page={PageNum}", $curentUri);        
            }
            
            # ссылка на первую страницу
            if($start_page>0) {
                ?><a href="<?=str_replace("{PageNum}", 1, $pagingLink)?>" class="paging">1</a> ... <?
            }
            
            # видимый список ссылок на сатрницы
            for($i=$start_page;$i<=$finish_page;$i++) {
                if($i == $page_now) {
                    ?><b class="paging"><?=($i+1)?></b> <?
                }
                else {
                    ?><a href="<?=str_replace("{PageNum}", (string)($i+1), $pagingLink)?>" class="paging"><?=($i+1)?></a> <?
                }
            }
            # последняя страница
            if($finish_page<$page_all-1) {
                ?>... <a href="<?=str_replace("{PageNum}", $page_all, $pagingLink)?>" class="paging"><?=$page_all?></a><?
            }
            
            if($page_all>$len_block){
                ?> (<?="Pages: {$page_all}; Items: <span class='t3ui_paging_items'>{$all_val}</span>"?>)<?
            } 
            else if($page_all > 1){
                ?> (<?="Items: <span class='t3ui_paging_items'>{$all_val}</span>"?>)<? 
            } 
            ?></div><?
        }
        else {
            ?><div class="pagingStat">One Page (<?="Items: <span class='t3ui_paging_items'>{$allElements}</span>"?>)</div><?     
        }
        $paging = ob_get_contents();
        ob_end_clean(); 
        
        return  $paging;  
    }    
}