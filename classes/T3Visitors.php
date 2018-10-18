<?

class T3Visitors {

    protected static $_instance = null;
    public $system;
    public $database;

    protected function initialize() {
        $this->system = T3System::getInstance();
        $this->database = $this->system->getConnect();
    }

    /**
    * @return T3Visitors
    */
    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
            self::$_instance->initialize();
        }
        return self::$_instance;
    }

    public static function updateEarnings($lead_id, $wm, $agn, $ref, $ttl)
    {
        T3Db::api()->update("leads_visitors",
            array(
                'wm' => $wm,
                'agn' => $agn,
                'ref' => $ref,
                'ttl' => $ttl,
                'is_sold' => (($ttl>0)?1:0),
            ), "lead_id = '".$lead_id."'");

    }

    public function GetListOfVisitorsByWebmasterId($webmaster_id)
    {
        return T3Db::api()->fetchAll("
        SELECT *
        FROM leads_visitors
        WHERE webmaster_id=?
        ORDER by id desc
        ", array($webmaster_id));

    }


}