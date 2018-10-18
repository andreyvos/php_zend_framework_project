<?php
class T3UkPhoneTest {
    
    private $database;

    private static $_instance = null;

    public function __construct(){
        $this->database = T3Db::api();
    }
    
    public static function getInstance(){
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public static function blankphone($buyer_id,$lead_id){
        $result = false;
        try{
            $num = self::getInstance()->database->fetchOne("select count(id) from ukphoneblanktest where buyer_id=$buyer_id");
            if ($num<100){
                $data = array(
                    'lead_id' => $lead_id,
                    'buyer_id'=> $buyer_id 
                );
                self::getInstance()->database->insert('ukphoneblanktest',$data);
                $result = true;                
            }
        }
        catch(Exception $e){}
        
        return $result;   
    }
    
}
?>
