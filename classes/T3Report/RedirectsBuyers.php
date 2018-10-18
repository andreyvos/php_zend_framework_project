<?php

class T3Report_RedirectsBuyers {
    /**
    * Загрузит раздел только для этого вебмастера
    * @var mixed
    */
    public $autoBuyer = null;
    
    public $autoPosting;
    
    /** @var T3MyValidator_DateRange */ 
    public $dateRange;
    
    /** @var AZend_Form */ 
    public $form;
    
    public $cacheType = 'daily';
    
    public $showTablePercent = 90;
    
    public $buyer = '';
    
    public $posting = '';
    
    public $data;
    public $dataTable;
    
    public $getValues = array();
    
    public $globalPercent; 
    
    public $maxX = 0;
    
    public function addFormValue($name, $value){
        $this->getValues[$name] = $value;  
    }
    
    public function run(){
        // Дата рендж 
        $this->dateRange = new T3MyValidator_DateRange();
        $this->dateRange->from_delta_days = -60;  
        $this->dateRange->checkDates(ifset($_GET['date1']), ifset($_GET['date2']));
        
        // Buyer
        if($this->autoBuyer){
            $this->buyer = $this->autoBuyer;    
        }
        else {
            if(isset($_GET['buyer']) && T3BuyerCompanys::isBuyer($_GET['buyer'])){
                $this->buyer = $_GET['buyer'];    
            }    
        }
        
        // Форма
        $this->form = new AZend_Form();
        $this->form->setDecoratorsUserForm();

        
        $this->form->addElementAndDecor('select', 'cacheType', 'Cache Type', array(
            'multiOptions'  => array(
                '10mins'    => '10 Minutes',
                '30mins'    => '30 Minutes',
                '1hour'     => '1 Hour',
                '3hours'    => '3 Hours',
                '6hours'    => '6 Hours',
                '12hours'   => '12 Hours',
                'daily'     => 'Daily',
            ),
            'required' => false, 
            'value' => 'daily',   
        )); 
        
        $this->form->addElementAndDecor('select', 'showTablePercent', 'Table Show is Percent', array(
            'multiOptions' => array(
                '50'  => '50%',
                '60'  => '60%',
                '70'  => '70%',
                '80'  => '80%',
                '85'  => '85%',
                '90'  => '90%',
                '95'  => '95%',
                '100' => '100%',
            ), 
            'value' => $this->showTablePercent,   
        )); 
        
        $this->form->addElementAndDecor('select', 'posting', 'Posting', array(
            'multiOptions' => T3BuyerCompanys::getPostings(ifset($_GET['buyer']), "- All -"),
            'required' => false, 
            'value' => '',   
        ))->setRegisterInArrayValidator(false);
        

        
        // Лечение проблемы при ненахождение канала при смене вебмасетра + безопастность
        if($this->autoPosting){
            $_GET['posting'] = $this->autoPosting;    
        }
        else{
            if(isset($_GET['posting']) && $_GET['posting']){
                /** @var T3BuyerChannel */
                $ch = T3BuyerChannels::getChannel($_GET['posting']);
                
                if($ch && $ch->id){
                    if(T3Users::getCUser()->isRoleBuyer()){
                        if($ch->buyer_id != T3Users::getCUser()->company_id){
                            $_GET['posting'] = '';    
                        }    
                    }   
                }
                else {
                    $_GET['posting'] = '';    
                }
            } 
        } 
        
        if(isset($_GET['SubmitButton'])){
            if($this->form->isValid($_GET)){
                $this->cacheType        = $this->form->getValue('cacheType');
                $this->posting          = $this->form->getValue('posting'); 
                $this->showTablePercent = $this->form->getValue('showTablePercent');  
            }
        }
        
        // Данные
        /** @var Zend_Db_Select */ 
        $select = T3Db::apiReplicant()->select();
        
        // product,company_id,channel_id,from,till,failed,success,total,percents
        
        $select
        ->from("redirect_buyer_{$this->cacheType}", array(
            'from',
            'till',
            'good_redirects'    => new Zend_Db_Expr("sum(success)"), 
            'bad_redirects'     => new Zend_Db_Expr("sum(total) - sum(success)"), 
            'total_redirects'      => new Zend_Db_Expr("sum(total)"),
            'percent'    => new Zend_Db_Expr("round((sum(success)/sum(total))*100, 1)"),    
        ))
        ->where("`till` BETWEEN '{$this->dateRange->dateFrom} 00:00:00' and '{$this->dateRange->dateTill} 23:59:59'")
        ->order("till desc")
        ->group('till');
        
        if($this->buyer)    $select->where("company_id=?",  $this->buyer);
        if($this->posting)      $select->where("channel_id=?",  $this->posting);  
        
        $this->data = T3Db::apiReplicant()->fetchAll($select);
        
        
        // Заполнение неполных разделов из других кешей
        if($this->cacheType == 'daily' && $this->dateRange->dateTill == date("Y-m-d")){
            $select = T3Db::apiReplicant()->select();
            $select
            ->from("redirect_buyer_10mins", array(
                'from'              => new Zend_Db_Expr("min(`from`)"), 
                'till'              => new Zend_Db_Expr("max(`till`)"), 
                'good_redirects'    => new Zend_Db_Expr("sum(success)"), 
                'bad_redirects'     => new Zend_Db_Expr("sum(total) - sum(success)"), 
                'total_redirects'   => new Zend_Db_Expr("sum(total)"),
                'percent'           => new Zend_Db_Expr("round((sum(success)/sum(total))*100, 1)"),    
            ))
            ->where("`till` BETWEEN '" . date("Y-m-d") . " 00:00:00' and '" . date("Y-m-d") . " 23:59:59'")
            ->order("till desc");
            
            if($this->buyer)    $select->where("company_id=?",  $this->buyer);
            if($this->posting)      $select->where("channel_id=?",  $this->posting);  
            
            $add = T3Db::apiReplicant()->fetchRow($select);
            
            
            
            if($add['total_redirects'] > 0){
                $add['from'] =  date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y"))); 
                //$add['till'] =  date("Y-m-d H:i:s", mktime(date("H"), 0, -1, date("m"), date("d"), date("Y")));    
                
                array_unshift($this->data, $add);   
            }      
        }
        
        // Добавление нового фиктивного элемента, нужен для графика
        if(count($this->data) > 0){
            $add = $this->data[0];
            
            if(count($this->data) > 1){
                $from  = strtotime($this->data[1]['from']);
                $till  = strtotime($this->data[1]['till']);
                $step = $till - $from;
                
                $add['from'] = date("Y-m-d H:i:s", $till+2+$step);
                $add['till'] = date("Y-m-d H:i:s", $till+2+2*$step);
            }
            
            array_unshift($this->data, $add);     
        }
        
        $currentYear = date('Y');
        
        
        $this->dataTable = array(); 
        if(count($this->data)){
            
            $first = true;
            $totalGood = 0;
            $totalAll = 0;
            
            foreach($this->data as $k => $el){
                $mktimeFrom     =   strtotime($el['from']);
                $mktimeTill     =   strtotime($el['till']);   
                
                if($this->cacheType == 'daily'){
                    $fromStr    = date('d M, Y H:i', $mktimeFrom);
                    $tillStr    = date('d M, Y H:i', $mktimeTill);
                }
                else{  
                    $fromStr    = date('d M, Y H:i', $mktimeFrom);
                    $tillStr    = date('d M, Y H:i', $mktimeTill); 
                }   
                
                //$dateStr = date('d M, Y H:i', $mktime); 
                
                $this->data[$k]['fromStr']              =   $fromStr; 
                $this->data[$k]['tillStr']              =   $tillStr; 
                $this->data[$k]['percent']              =   round($el['percent'] ,1);   
                $this->data[$k]['flortTime']            =   ((strtotime($el['from'] . " UTC"))*1000);
                
                $maxXtemp = ((strtotime($el['till'] . " UTC")+1)*1000); 
                if($maxXtemp > $this->maxX)$this->maxX = $maxXtemp;
                
                if(!$first){
                    if($this->data[$k]['percent'] <= $this->showTablePercent){
                        $this->dataTable[$k] = $this->data[$k];   
                        $this->dataTable[$k]['send_to_link'] = T3SendMail_Main::createLink('BuyersRedirectReport', array(
                            'from'      => $el['from'],
                            'till'      => $el['till'],
                            'buyer'     => $this->buyer,
                            'posting'   => $this->posting,
                        ));
                        
                        $this->dataTable[$k]['show_to_link'] = T3SendMail_Main::createLinkInShowPage('BuyersRedirectReport', array(
                            'from'      => $el['from'],
                            'till'      => $el['till'],
                            'buyer'     => $this->buyer,
                            'posting'   => $this->posting,
                        ));
                    }
                    
                    $totalGood+= $el['good_redirects']; 
                    $totalAll+= $el['total_redirects'];  
                } 
                $first = false;    
                
            } 
            
            if($totalAll){
                $this->globalPercent = round(($totalGood / $totalAll)*100, 1);
            }   
        }
        
        $this->globalRedirectsLink = T3SendMail_Main::createLink('BuyersRedirectReport', array(
            'from'      => "{$this->dateRange->dateFrom} 00:00:00",
            'till'      => "{$this->dateRange->dateTill} 23:59:59",
            'buyer'     => $this->buyer,
            'posting'   => $this->posting,
        ));
        
             
    }    
    
    public function render(){
        MyZend_Site::addJS(array(
            'report/redirect.js',
            'jquery.ui.core.js',
            'jquery.ui.datepicker.js',
            'json2.js',
            'jquery.tablesorter.js',
        ));
        
        MyZend_Site::addCSS(array(
            'datepicker/ui.all.css',
            'tablesorter/style.css',
            'helpMessage.css',
            'report/summary.report.css',
        ));
        
        $view = new Zend_View();
        $view->setScriptPath(dirname(__FILE__) . DS . "RedirectsBuyers");
        $view->addHelperPath(LIBS . DS . "Helpers", "MyZend_View_Helper_"); 
        
        $view->class = $this;
        
        return $view->render("main.phtml");  
    }
} 