<?php

class T3Cache_PostReportStatus {
    
    static protected $a = array(
        // проданный
        'Sold'              => array('Sold',            '#D3F4B3'),
        
        // Нормальные 
        'Filtered'          => array('Filtered',        '#FFF'), 
        'Duplicated'        => array('Duplicated',      '#FFF'), 
        'Rejected'          => array('Rejected',        '#ddf'), 
        'GlobalReject'      => array('GlobalReject',    '#FFF'), 
        
        // Не нормальные
        'SendError'         => array('SendError',       '#FF9955'), 
        'PriceConflict'     => array('PriceConflict',   '#FEEE92'), 
        'Error'             => array('Error',           '#FEEE92'), 
        'ConfigError'       => array('ConfigError',     '#FF8844'), 
        'AnalysisError'     => array('AnalysisError',   '#FF6633'),
        'Timeout'           => array('Timeout',         '#FFCC99'),  
        'Unknown'           => array('Unknown',         '#FFFF66'),
        'Return'           => array('Return',           '#D1BFFF'),
        'NotPosted'		   => array('Not Posted',       '#FFF'),
        'active'		   => array('Active',           '#D1F0D5'),
    	'paused'		   => array('Paused',           '#F7C6C6'),
    	'just_created'		=> array('Just created',    '#E6F0F5'),
    );
    
    static public function renderStatus($status){
        MyZend_Site::addCSS('table/status.css'); 
        return "<div class='tableStatus' style='background:" . ifset(self::$a[$status][1], '#66F') . ";color:#333;text-align:center;'>" . ifset(self::$a[$status][0], $status) . "</div>";
    }
 /*   
    static public function renderStatusFromLog($log_data)
    {
        MyZend_Site::addCSS('table/status.css'); 
        
        if(is_array($log_data) && count($log_data)) {
            
            if($log_data['']) {}
            
        }
        return "<div class='leadStatus' style='background:#FFFF66;color:#333;text-align:center;'>Unknown</div>";
    }
 */   
    
}
