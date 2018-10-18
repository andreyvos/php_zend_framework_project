<?php

class TabsStaticExt extends AP_TabsStatic{
    protected $dropUrlQuery = false;
    protected function getURL($action){
        $url = parse_url($_SERVER['REQUEST_URI']);
        parse_str(ifset($url['query']), $get);
        $get[$this->getOption] = $action;
        if($this->dropUrlQuery){
            unset($get);
            $get[$this->getOption] = $action;
        }
        return $url['path'] . "?" . http_build_query($get);
    }

    /** Отк/Вкл присоединение query парамметров в методе getURL
     * @param bool $bool
     * @return $this
     */
    public function setDropUrlQuery($bool = true){
        $this->dropUrlQuery = $bool;
        return $this;
    }
}