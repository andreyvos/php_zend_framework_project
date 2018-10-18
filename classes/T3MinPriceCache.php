<?

class T3MinPriceCache
{
    
    public static function addMinPrice($webmaster, $date, $minPrice, $product, $channel = 0, $sold = 0, $wm = 0, $total = 0){
        // select
        $res = T3Db::api()->fetchOne("select * from min_prices where webmaster=? and date=? and min_price=? and product=? and channel=?",
            array($webmaster, $date, $minPrice, $product, $channel)
        );
        
        if ($res){
            // Update
            T3Db::api()->update("min_prices", array(
                'count' => new Zend_Db_Expr("`count`+1"), 
                'sold' => new Zend_Db_Expr("`sold`+{$sold}"), 
                'wm' => new Zend_Db_Expr("`wm`+{$wm}"), 
                'ttl' => new Zend_Db_Expr("`ttl`+{$total}"), 
            ), 
            "webmaster=" .  T3Db::api()->quote($webmaster)  . " and " .
            "`date`=" .     T3Db::api()->quote($date)       . " and " .
            "min_price=" .  T3Db::api()->quote($minPrice)   . " and " .
            "product=" .    T3Db::api()->quote($product)    . " and " .
            "channel=" .    T3Db::api()->quote($channel));
        }
        else{
            // Insert
            T3Db::api()->insert("min_prices",
                array(
                    "webmaster" => $webmaster,
                    "date"      => $date,
                    "min_price" => $minPrice,
                    "product"   => $product,
                    "channel"   => $channel,
                    "count"     => "1",
                    "sold"      => $sold,
                    "wm"        => $wm,
                    "ttl"       => $total,
                )
            );
        }
    }

    public function getMinPrices($webmaster, $product, $date1, $date2, $channel = null){
        /** @var Zend_Db_Select */
        $select = T3Db::api()->select();
        
        $colums = array(
            'min_price',
            'count' => new Zend_Db_Expr("sum(`count`)"),
            'sold'  => new Zend_Db_Expr("sum(`sold`)"),
            'wm'    => new Zend_Db_Expr("sum(`wm`)"),
            'ttl'   => new Zend_Db_Expr("sum(`ttl`)"),
        );
        
        if(!T3Users::getCUser()->isRoleAdmin() && !T3Users::getCUser()->isRoleBuyerAgent()){
            unset($colums['ttl']);   
        }
        
        $select
        ->from("min_prices", $colums)
        ->where("((date between '{$date1}' and '{$date2}') OR (date between '{$date2}' and '{$date1}'))")
        ->where("webmaster=?", $webmaster)
        ->where("product=?", $product)
        ->group("min_price");
        
        if($channel){
            if(T3Channels::isAccess($channel)){
                $select->where("channel=?", $channel);
            }       
        }
        
        return T3Db::api()->fetchAll($select);   
        
    }

}