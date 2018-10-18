<?php 
TableDescription::addTable('timeline_event_types',
							array('id',
								   'name',
								   'title',
								   'description',
								   'active',
								   'importance'
								)
	);


class T3TimeLine_EventType extends DbSerializable{

	public $id;
	public $name;
	public $title;
	public $description;
	public $active;
	public $importance;




	/* Posting Events */
	const POSTING_DUBLICATE_METHOD_CHANGED = 'posting_duplicateMethod_changed';
	const POSTING_DUBLICATE_DAYS_CHANGED = 'posting_duplicateDays_changed';
	const POSTING_DUBLICATE_POST_DAYS_CHANGED = 'posting_duplicatePostDays_changed';
	const POSTING_DUBLICATE_GLOBAL_DAYS_CHANGED = 'posting_duplicateGlobalDays_changed';
	const POSTING_GLOBAL_DUBLICATE_CHANGED = 'posting_globalDuplicate_changed';
	const POSTING_MIN_DUBLICATE_NUM_CHANGED = 'posting_minDuplicateNums_changed';
	const POSTING_MIN_DUBLICATE_DATA_CHANGED = 'posting_minDuplicateData_changed';
	const POSTING_SEND_VS_SOLD_CHANGED = 'posting_ratioSendVsSold_changed';	
	const POSTING_AUTO_ON_OF_CHANGED = 'posting_auto_no_off_changed';
	const POSTING_AUTO_PAUSED_MINUTES_CHANGED = 'posting_auto_paused_minutes_changed';
	const POSTING_AUTO_PAUSED_PROCENT_CHANGED = 'posting_auto_paused_percent_changed'; 	
	const POSTING_EMAIL_CHANGED = 'posting_emails_changed';
	const POSTING_FILTERS_CHANGED = 'posting_filters_changed';
	const POSTING_FILTERS_COPY = 'posting_filters_copy';
	const POSTING_PRICES_CHANGED  = 'posting_prices_changed';
	const POSTING_STATUS_CHANGED  = 'posting_status_changed';
	const POSTING_CONFIG_CHANGED  = 'posting_config_changed';
	const POSTING_CREATED         = 'posting_created';
	const POSTING_TIMEZONE_CHANGED = 'posting_timezone_changed'; 
	const POSTING_TITLE_CHANGED = 'posting_title_changed';
	const POSTING_PRICE_GAME_CHANGED = 'posting_price_game_changed';
	/* -------- */

	/* Webmaster Events */
	const WEBMASTER_CHANNEL_PRICE_GAME_CHANGED = 'webmaster_channel_price_game_changed';
	const WEBMASTER_FORM_CHANNEL_PRICE_GAME_CHANGED = 'webmaster_form_channel_price_game_changed';
	const WEBMASTER_REFAFFID_CHANGED = 'webmaster_refaffid_changed';
	const WEBMASTER_PROFILE_STATUS_CHANGED = 'webmaster_profile_status_changed';
	const WEBMASTER_SISID_CREATED = 'webmaster_sisid_created';
	const WEBMASTER_SISID_CHANGED = 'webmaster_sisid_changed';
	const WEBMASTER_SISID_DELETED = 'webmaster_sisid_deleted';
	const WEBMASTER_REMOTE_SISID_ACTION = 'webmaster_remote_sisid_action';
	const WEBMASTER_PAYMENTS_SETTINGS_CHANGED = 'webmaster_payments_settings_changed';
	const WEBMASTER_WEBMASTERS_PAYMENTS_SETTINGS_CHANGED = 'webmaster_webmasters_payments_settings_changed';
	const SERVER_POST_CREATED = 'server_post_created';
	const FORM_PRICE_GAME_STATUS = 'form_price_game_status';
	const SERVER_POST_CHANNEL_STATUS_CHANGED = 'server_post_channel_status_changed';
	/* -------- */

	/* Buyer Events */
	const BUYER_PROFILE_CHANGED = 'buyer_profile_changed';
	const BUYERS_INVOICES_CHANGED = 'buyer_invoices_changed';
	/* -------- */

	const PINGTREE_ACTIONS = 'pingtree_actions';
	const PINGTREE_SET_POSITIONS = 'pingtree_set_positions';
	const POST_PRICES_CHANGED = 'post_prices_changed';
	const PRICING_SUPER_GLOBAL_SETTINGS_CHANGED = 'pricing_super_global_settings_changed';

	public function __construct(){
		parent::__construct();
	    $this->tables[] = 'timeline_event_types';
	}

	public static function add($name, 
							   $title, 
							   $description, 
							   $active, 
							   $importance){

		$event = new T3TimeLine_EventType();

		$event->name = $name;
		$event->title = $title;
		$event->description = $description;
		$event->active = $active;
		$event->importance = $importance;

		$saved =  $event->insertIntoDatabase();
		return $saved;
	}


	public static function getIdByType($type){
		$event_type = new T3TimeLine_EventType();
		if($event_type->fromDatabase(array('name' => $type))){
			return $event_type->id;
		}else{
			return NULL;
		}

	}

	public static function renderTimeline($events){
		$template = new Zend_View();
		$template->setScriptPath(dirname(__FILE__).DS.'templates');
		$template->events = $events;
		return $template->render('timeline.phtml');
	}

	public static function render($event){
		$type = new T3TimeLine_EventType();
		$type->fromDatabase($event->event_type);
		$template = new Zend_View();
		$template->setScriptPath(dirname(__FILE__).DS.'templates');
		$template->event = $event;
		try{
			return $template->render($type->name.'.phtml');
		}catch(Zend_View_Exception $e){
			return $template->render('not_implemented.phtml');
		}
	}
}
