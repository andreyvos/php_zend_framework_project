<?php

class T3LeadsProducts {

    const paydayLoan = 'payday';

    public static $products = array(
      self::paydayLoan
    );
    
    static public function getProducts(){
        return self::$products;    
    }

    public static $productsClasses = array(
      self::paydayLoan => 'T3LeadBody_PaydayLoan',
    );

    public static $productsTitles = array(
      self::paydayLoan => 'Payday Loan',
    );

    public static function getProductsTitles() {
        return self::$productsTitles;
    }

    public static function productExists($product) {
        return in_array($product, self::$products);
    }
    
    public function getProductTitle($name){
        return "Payday";    
    }

}

