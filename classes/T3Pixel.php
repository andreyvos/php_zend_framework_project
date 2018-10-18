<?php

class T3Pixel {

    protected $_db;

    public function __construct() {
        $this->_db = T3Db::api();
    }

    /**
     * Creates hash or returns if exists
     * @param int $lead_id
     * @param int $channel_id
     * @return string $hash
     */
    public function checkHash($lead_id, $channel_id) {
        $hash = md5($lead_id . $channel_id);


        $select = $this->_db->select()
                ->from('pixel_hash')
                ->where('hash=?', $hash);

        $result = $this->_db->fetchAll($select);

        if (!count($result)) {
            $insert = $this->_db->insert('pixel_hash', array('lead_id' => $lead_id, 'channel_id' => $channel_id, 'hash' => $hash));
        }

        return $hash;
    }

    public function getByHash($hash) {

        $select = $this->_db->select()
                ->from('pixel_hash')
                ->where('hash=?', $hash);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function createPixelAction($dateTime, $ip, $hash_id) {
        $insert = $this->_db->insert('pixel_action', array('datetime' => $dateTime, 'ip' => $ip, 'hash_id' => $hash_id));
    }

    
    public function getByleadandChannel ($lead_id, $channel_id){
        $hash=  md5($lead_id . $channel_id);
        
        $select=  $this->_db->select()
                ->from('pixel_hash')
                ->where('hash=?', $hash);
        $result=  $this->_db->fetchRow($select);
        $hash_id=$result['id'];
        
        
        $selectAction = $this->_db->select()
                ->from('pixel_action')
                ->where('hash_id=?', $hash_id);
        $result = $this->_db->fetchRow($selectAction);
        return $result;
        
    }




//    public function getActionByLeadId($lead_id) {
//
//        $select = $this->_db->select()
//                ->from('pixel_hash')
//                ->where('lead_id=?', $lead_id);
//        $result = $this->_db->fetchRow($select);
//        $hash_id = $result['id'];
//
//        $selectAction = $this->_db->select()
//                ->from('pixel_action')
//                ->where('hash_id=?', $hash_id);
//        $result = $this->_db->fetchRow($selectAction);
//        return $result;
//    }
//
//    public function getActionByChannelId($channel_id) {
//        $select = $this->_db->select()
//                ->from('pixel_hash')
//                ->where('channel_id=?', $channel_id);
//        $result = $this->_db->fetchRow($select);
//        $hash_id = $result['id'];
//        
//        $selectAction = $this->_db->select()
//                ->from('pixel_action')
//                ->where('hash_id=?', $hash_id);
//        $result = $this->_db->fetchRow($selectAction);
//        return $result;
//    }

}