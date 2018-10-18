<?php
class SiteRules {

    public static function getPublishedRulesForWebmaster($wmID){

	$rules = T3Db::site()->query("select * from site_rules where site_rules.published= 1 and site_rules.id NOT IN (select viewed_rules.rule_id from viewed_rules where viewed_rules.wm_id ={$wmID})")->fetchAll();
	return $rules;
    }
    public static function getLatestRuleForWebmaster($wmID){
	$rule = T3Db::site()->query("select * from site_rules where site_rules.published= 1 and site_rules.id NOT IN (select viewed_rules.rule_id from viewed_rules where viewed_rules.wm_id ={$wmID}) order by site_rules.last_published_time asc limit 1")->fetch();
        return $rule;

    }
    public static function getAllRules(){
	$rules = T3Db::site()->query("select * from site_rules ")->fetchAll();
	return $rules;
    }
    public static function addRule($ruleParams){
	$r = T3Db::site()->insert('site_rules', 
		array('label'=>$ruleParams['label'] , 'text'=>$ruleParams['text'],'last_published_time'=>$ruleParams['datetime'],'id_user_published'=>$ruleParams['id_user'],'published'=>$ruleParams['published']));
	return T3Db::site()->lastInsertId('site_rules');
    }
    public static function getRule($id){
	$rule = T3Db::site()->query("select * from site_rules where id={$id}")->fetch();
	return $rule;
    }
    public static  function setRuleViewed($ruleID,$userID){
	T3Db::site()->query("insert into viewed_rules(rule_id,wm_id) values({$ruleID},{$userID})");
    }
    public static function updateRule($id,$ruleParams){
	        //die(var_dump($ruleParams));
		$q = "update  site_rules  set label='{$ruleParams['label']}' ,
		text='{$ruleParams['text']}',
		last_published_time='{$ruleParams['datetime']}',
		id_user_published='{$ruleParams['id_user']}',
		published={$ruleParams['published']} where id={$id}";
		$r = T3Db::site()->query($q);
		$r = T3Db::site()->update('site_rules',
		array('label'=>$ruleParams['label'] , 'text'=>$ruleParams['text'],'last_published_time'=>$ruleParams['datetime'],'id_user_published'=>$ruleParams['id_user'],'published'=>$ruleParams['published']),
			"id={$id}");
		return $r;
    }
    public static function deleteViewedRule($ruleID){
	$q = "delete from viewed_rules where rule_id={$ruleID}";
	$r = T3Db::site()->query($q);
	return $r;
    }
    public static function deleteRule($ruleID){
	$q = "delete from site_rules where id={$ruleID}";
	$r = T3Db::site()->query($q);
	return $r;
    }
}
?>
