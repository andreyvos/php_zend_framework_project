<?php
class T3Clear {
    static protected $id;
    static protected $start;

    const TYPE_BUYERS_STATISTICS_LITE = 1;

    static protected function createTable(){
        try{
            T3Db::logs()->query("
                    CREATE TABLE `clear_log` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `type` tinyint(4) DEFAULT NULL,
                      `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `runtime` float unsigned DEFAULT NULL,
                      `memory` int(10) unsigned DEFAULT NULL,
                      `count` int(11) DEFAULT NULL,
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1
                ");
        }
        catch(Exception $e){}
    }

    static public function isLastFinished($type){
        try{
            return (bool)T3Db::logs()->fetchOne("SELECT runtime FROM `clear_log` WHERE `type`=? ORDER BY id DESC LIMIT 1", $type);
        }
        catch(Exception $e){
            self::createTable();

            return false;
        }
    }

    static public function start($type){
        self::$start = microtime(1);
        self::$id = null;

        try{
            T3Db::logs()->insert("clear_log", array(
                'type' => $type,
            ));
            self::$id = T3Db::logs()->lastInsertId();
        }
        catch(Exception $e){
            self::createTable();
        }

        return self::$id;
    }

    static public function finish($count = 0){
        if(self::$id){
            try {
                T3Db::logs()->update("clear_log", array(
                    'runtime' => microtime(1) - self::$start,
                    'memory'  => memory_get_peak_usage(),
                    'count'   => $count,
                ), "id=" . self::$id);
            }
            catch(Exception $e){}
        }
    }
}