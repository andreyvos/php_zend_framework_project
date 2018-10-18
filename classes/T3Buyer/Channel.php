<?php
/**
* Класс Канала для Баеров
* 
* 
* @author Anton S. Panfilov
* @version 1.0
* @copyright anton.panfilov@gmail.com  
*/

  
AZend_DB_ObjectSettings::addTable('buyers_channels', array(
    'id',
    'buyer_id',
    
    'status',
    'product',
    
    'title',
    
    'timezone',
    'minConstPrice',

));

class T3Buyer_Channel extends AZend_DB_Object {
    protected $dbObj_tableName = 'buyers_channels';
    

    protected $id;  
    public $buyer_id;
     
    public $status;
    public $product;
    
    public $title;   
    
    public $timezone;
    public $minConstPrice;  
                
}

