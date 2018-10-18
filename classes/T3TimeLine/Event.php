<?php 
TableDescription::addTable('timeline_events',
							array('id',
								   'user_id',
								   'event_created_user_id',
								   'user_type',
								   'event_type',
								   'event_start',
								   'event_end',
								   'comment',
								   'details', 
								   'rating')
	);

class T3TimeLine_Event extends DbSerializable {
	public $id;
	public $user_id;
	public $event_created_user_id;
	public $user_type;
	public $event_type;
	public $event_start;
	public $event_end;
	public $comment;
	public $details;
	public $details_clean;
	public $rating;


	const RATING_GOOD = 'good'; 
	const RATING_NEUTRAL = 'neutral';
	const RATING_BAD = 'bad';

	const TYPE_BUYER = 'buyer';
	const TYPE_WEBMASTER = 'webmaster';

	public function __construct(){
		parent::__construct();
        $this->tables[] = 'timeline_events';
	}

	public static function add($user_id,
							   $user_type,
							   $event_type,
							   $details, 
							   $event_rating = T3TimeLine_Event::RATING_NEUTRAL,
			                   $event_start  = NULL, 
			                   $event_end    = NULL, 
			                   $comment      = ''){
		$event = new T3TimeLine_Event();
		$event->user_id = $user_id;
		$event->event_created_user_id = T3Users::getCUser()->id;
		$event->user_type = $user_type;
		$event->event_type = $event_type;
		$event->details_clean = $details;
		$event->details = serialize($details);
		$event->rating = $event_rating;

		if(is_null($event_start)){
			$event->event_start = new Zend_Db_Expr('NOW()');
		}else{
			$event->event_start = $event_start;
		}
		if(is_null($event_end)){
			$event->event_end = new Zend_Db_Expr('NOW()');
		}else{
			$event->event_end = $event_end;
		}
		$event->comment = $comment;
		$res = $event->insertIntoDatabase();
		return $res;
	}

	public function render(){
       return T3TimeLine_EventType::render($this);
	}


	public function fromArray($array){
		parent::fromArray($array);
		$this->details_clean = unserialize($this->details);
	}

	/*
			todo: process params
	*/
	public static function getEvents($user_id, 
		$return_array, 
		$event_types = NULL, 
		$event_rating = NULL, 
		$date_start = NULL, 
		$date_end = NULL,
		$user_event_created = NULL
		){
		//$ar = $this->database->fetchAll("SELECT * FROM timeline_events WHERE user_id = ?", array($user_id));
		$api = T3Db::apiReplicant();
		$event_super_global_settings =  $api->fetchOne("SELECT id FROM `timeline_event_types` WHERE name = ?",
			Array(T3TimeLine_EventType::PRICING_SUPER_GLOBAL_SETTINGS_CHANGED)
		);
		$query = 'SELECT *, `timeline_events`.`id` as event_id  FROM `timeline_events` LEFT JOIN `timeline_event_types`
										ON `timeline_events`.`event_type` = `timeline_event_types`.`id`  WHERE (user_id = ? ';
		$params = array($user_id);

		$query .= 'OR (user_id = ? AND event_type = ?))';

		$params[] = 0;
		$params[] = $event_super_global_settings;

		if(!is_null($event_rating)){
			$query.= ' AND rating = ? ';
			$params[] = $event_rating;

		}

		if(!is_null($date_start) && !is_null($date_end)){
			$query.= 'AND event_start>= ? AND event_end <=?';
			$params[] = $date_start;
			$params[] = $date_end. '23:59:59';
		}

		$query.=' order by event_id desc limit 500';

		$query_result = $api->fetchAll($query, $params);
		if($query_result)

		if(count($query_result) === 0){
			return NULL;
		}
		if($return_array){
			return $query_result;
		}


		$events = array();
		foreach ($query_result as $key => $event_array) {
			$event = new T3TimeLine_Event();
			$event->fromArray($event_array);
			$events[] = $event;
		}

		return $events;
	}

	public static function getRatings(){
		return array(self::RATING_NEUTRAL => ucfirst(self::RATING_NEUTRAL), self::RATING_BAD => ucfirst(self::RATING_BAD), self::RATING_GOOD => ucfirst(self::RATING_GOOD));
	}
}

