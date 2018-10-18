<?php

class T3Products {
    public $products;
    public $productsMini;
    public $productsMiniID;
    public $callproductsMini;
    
    protected static $_instance = null;
    
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } 
    
    /**
    * Полученние полного массива продуктов
    * 
    * @param mixed $type
    * @param mixed $lazy
    */
    static public function getProducts($type = 'null', $lazy = true){
        if(!isset(self::getInstance()->products) || !$lazy){
            $prod = T3System::getConnect()->fetchAll("select * from leads_type where activ='1' order by `prioritet` desc");
             
             foreach($prod as $pr){
                self::getInstance()->products[$pr['name']] = $pr; 
             }  
        }
        return self::getInstance()->products;
    }

    static public function isProduct($name)
    {
        return (bool)T3System::getConnect()->fetchOne('select count(*) from leads_type where `name`=?', $name);
    }

    /**
    * @param string $name
    * @return array
    */
    static public function getProduct($name, $value = null){
        $products = self::getProducts();
        $product = ifset($products[$name]);
        
        if(is_null($value))     return $product;    
        else                    return ifset($product[$value]);     
        
    }
    
    /**
    * Получение ассотиативного масива продуктов name => title
    * 
    * @param mixed $type
    * @param mixed $lazy
    */
    static public function getProducts_MiniArray(){
        if(!isset(self::getInstance()->productsMini)){ 
            $all = T3Db::apiReplicant()->fetchAll("select `name`, `title` from leads_type where activ='1' order by `title` asc");
            self::getInstance()->productsMini = array();
            foreach($all as $pr){
                self::getInstance()->productsMini[$pr['name']] = $pr['title']; 
            }
        }
        return self::getInstance()->productsMini;
    }
    /**
     * Получение ассотиативного масива продуктов id => title
     *
     * @param mixed $type
     * @param mixed $lazy
     */
    static public function getProducts_MiniArrayID(){
        if(!isset(self::getInstance()->productsMiniID)){
            $all = T3Db::apiReplicant()->fetchAll("select `id`, `title` from leads_type where activ='1' order by `prioritet` desc");
            self::getInstance()->productsMiniID = array();
            foreach($all as $pr){
                self::getInstance()->productsMiniID[$pr['id']] = $pr['title'];
            }
        }
        return self::getInstance()->productsMiniID;
    }

    static public function getCallProducts_MiniArray(){
        if(!isset(self::getInstance()->callproductsMini)){
            $all = T3Db::apiReplicant()->fetchAll("select `name`, `title` from leads_type where activ='1' and title rlike 'Call' order by `prioritet` desc");
            self::getInstance()->callproductsMini = array();
            foreach($all as $pr){
                self::getInstance()->callproductsMini[$pr['name']] = $pr['title'];
            }
        }
        return self::getInstance()->callproductsMini;
    }
    
    static public function getTitle($name){
        return T3System::getConnect()->fetchOne('select title from leads_type where `name`=?', $name); 
    }
    
    static public $products_ids = array(
        'payday' => 7
    );
    
    static public function getID($name){
        if(!isset(self::$products_ids[$name])){
            self::$products_ids[$name] = T3System::getConnect()->fetchOne('select id from leads_type where `name`=?', $name); 
        }
        return self::$products_ids[$name];
    }
    
    static public $products_names = array(
        7 => 'payday'
    );
    
    static public function getName($id){
        if(!isset(self::$products_names[$id])){
            self::$products_names[$id] = T3System::getConnect()->fetchOne('select name from leads_type where `id`=?', $id); 
        }
        return self::$products_names[$id];
    }
    
    /**
    * Создание объекта, который в дальнейшем можно отрендерить на странице
    * 
    * @param mixed $name
    * @return Zend_Form_Element_Select
    */
    static public function renderSelectObject($name = 'product', $firstOption = null){
        // Для баер агента, проверяем какие продукты для него доступны, и показываем только их.
        if(T3Users::getCUser()->isRoleBuyerAgent()){
            $prodTemp = self::getProducts_MiniArray(); 
            $prodAgent = T3UserBuyerAgents::getProducts();
            $prod = array();
            foreach($prodTemp as $key => $val){
                if(in_array($key, $prodAgent)){
                    $prod[$key] = $val;    
                }
            }   
        }
        else {
            $prod = self::getProducts_MiniArray();           
        }
        
        $select = new Zend_Form_Element_Select($name);
        if($firstOption)$select->addMultiOption('', $firstOption);
        $select->addMultiOptions($prod);
        $select->setDecorators(array('ViewHelper'));
        
        return $select;
    }
    
    static public function createProduct($systemName, $title, $bodyClass){
        T3System::getConnect()->insert('leads_type', array(
            'name'          =>  $systemName,
            'title'         =>  $title,
            'class_body'    =>  $bodyClass,
        ));
        
        return T3System::getConnect()->lastInsertId();
    }
    
    static public function createDefaultServerPOSTDoc($product){
        T3WebmasterChannelDocumentations::addMenuItem($product);
        
        $text = AZend_StaticContent::getText("documentations____default__", false);
        
        $text = str_replace(   
            array(
                '{product:name}',
                '{product:title}',
            ), 
            array(
                $product,
                self::getTitle($product),  
            ), 
            $text
        );
        
        AZend_StaticContent::setText("documentations__{$product}", $text);    
    }
    
    static protected $productsRelations = array(
        'auto_loan'             => 'auto_loan',
        'credit_repair'         => 'credit_repair',
        'debt_s'                => 'debt_settlement',
        'mortgage'              => 'mortgage',
        'payday'                => 'payday',
        'auto_ref'              => 'auto_refinance',
        'auto_w'                => 'auto_warranties',
        'credit_report'         => 'credit_report',
        'bankruptcy'            => 'bankruptcy',
        'home_security'         => 'home_security',
        'satellite_tv'          => 'satellite_tv',
        'stud_cons'             => 'student_loans',
        'commercial_loan'       => 'commercial_loan',
        'home_improve'          => 'home_improvement',
        'annuity_insurance'     => 'annuity_insurance',
        'auto_insurance'        => 'auto_insurance',
        'home_insurance'        => 'home_insurance',
        'renter_insurance'      => 'renters_insurance',
        'ltc_insurance'         => 'ltc_insurance',
        'life_insurance'        => 'life_insurance',
        'health_insurance'      => 'health_insurance',
        'disability_insurance'  => 'disability_insurance',
        'cancer_insurance'      => 'cancer_insurance',
        'burial_insurance'      => 'burial_insurance',
        'loan_modification'     => 'loan_modification',
        'ukpayday'              => 'ukpayday',
    );
    
    /**
    * Cопостовление продукта в старой и новой системе.
    * 
    * @param string $oldTitle
    * @return string
    */
    static public function oldToNew($oldTitle){
        if(isset(self::$productsRelations[$oldTitle])) return self::$productsRelations[$oldTitle];
        return '';
    }
    
    /**
    * Cопостовление продукта в новой и старой системе.
    * 
    * @param string $newTitle
    * @return string
    */
    static public function newToOld($newTitle){
        $products = array();
        foreach(self::$productsRelations as $old => $new){
            $products[$new] = $old;   
        }
        
        if(isset($products[$newTitle])) return $products[$newTitle];
        return '';
    } 
}
