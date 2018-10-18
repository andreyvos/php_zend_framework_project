<?php

class T3Cache_CallInfo {

    static public function render($leadID){
        MyZend_Site::addCSS('table/call.css');
        MyZend_Site::addJS('table/call_quality.js');

        $leadID = IdEncryptor::encode($leadID);
        return "<span class='tableQuality' id='lead_quality_main_{$leadID}'><a class='qualityCallInfo' onClick=\"createCallInfoMenu('{$leadID}')\">" .
        "<span class='qualitySpan' style='background:#D3F4B3'>?</span>" .
        "<div class='qualityInfo' id='lead_quality_div_{$leadID}'></div></a></span>";

        return "";

    }
}