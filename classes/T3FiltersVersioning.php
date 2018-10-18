<?php

/* вместо buyers_filters_conditions на время тестирования установлена таблица buyers_filters_conditions_test */

class T3FiltersVersioning{

  public static function CreationTypeToString($creationType){

    switch($creationType){
      case 'copy' : return 'Copying';
      case 'modify' : return 'Modifying';
      case 'revert' : return 'Revert';
    }

    return "Initial";

  }

  public static function SaveAndSetCurrentVersion($buyerChannelId, $creationType){



    $database = T3System::getConnect();

    try{

      $database->beginTransaction();

      $buyerChannelData = $database->fetchRow('select * from buyers_channels where id = ?', array($buyerChannelId));

      if(empty($buyerChannelData))
        throw new Exception();

      $buyerId = $buyerChannelData['buyer_id'];
      $currentVersionId = $buyerChannelData['current_filters_version_id'];

      if(empty($buyerId))
        throw new Exception();

      $currentVersionArrayData = $database->fetchAll('
        select * from buyers_filters_versions_data where version_id = ?
      ', $currentVersionId);

      if(!empty($currentVersionArrayData)){

        $currentVersionArrayData = groupBy($currentVersionArrayData, array(), 'type_name');

        $newVersionArrayData = $database->fetchAll('
          select * from buyers_filters_conditions_test where channel_id = ?
        ', $buyerChannelId);

        $newVersionArrayData = groupBy($newVersionArrayData, array(), 'type_name');

        $modificationsData = array();
        foreach($newVersionArrayData as $k => $v){
          if(!isset($currentVersionArrayData[$k])){
            $modificationsData[] = $k;
            continue;
          }

          $v1 = $currentVersionArrayData[$k];

          if(
            $v['affirmative'] != $v1['affirmative'] ||
            $v['works'] != $v1['works'] ||
            $v['misc'] != $v1['misc']
          ){
            $modificationsData[] = $k;
          }

        }

      }else
        $modificationsData = array();

      $database->query('
        insert into buyers_filters_versions set
          creation_datetime = ?,
          buyer_id = ?,
          buyer_channel_id = ?,
          modifications_data = ?,
          previous_version_id = ?,
          author_id = ?,
          author_ip_address = ?,
          creation_type = ?
      ', array(
        mySqlDateTimeFormat(),
        $buyerId,
        $buyerChannelId,
        serialize($modificationsData),
        $currentVersionId,
        T3Users::getCUser()->id,
        $_SERVER['REMOTE_ADDR'],
        $creationType,
      ));

      $versionId = $database->lastInsertId();

      $database->query('
        update buyers_channels set current_filters_version_id = ? where id = ?
      ', array($versionId, $buyerChannelId));

      $database->query('
        insert into buyers_filters_versions_data
        select 0, channel_id, type_name, affirmative, works, misc, ?
        from buyers_filters_conditions_test
        where channel_id = ?
      ', array($versionId, $buyerChannelId));

      $database->commit();

      $old_count = $database->fetchRow('select count(*) as c  from buyers_filters_versions_data where version_id = ? AND channel_id = ? AND works=1', array($currentVersionId, $buyerChannelId));
      $new_count = $database->fetchRow('select count(*)  as c from buyers_filters_versions_data where version_id = ? AND channel_id = ? AND works=1', array($versionId, $buyerChannelId));
      if($new_count > $old_count){
      $event_rating = T3TimeLine_Event::RATING_BAD;
      } elseif ($new_count == $old_count) {
      $event_rating = T3TimeLine_Event::RATING_NEUTRAL;
      }else{
      $event_rating = T3TimeLine_Event::RATING_GOOD;
      }


      $FilterVersionArrayData = T3FiltersVersioning::GetVersionArrayData($currentVersionId);


      $FilterCurrentVersionDiference = T3FiltersVersioning::CurrentVersionDiference(
      $buyerChannelId, $FilterVersionArrayData);

      //varDump2( $FilterCurrentVersionDiference );

      $filtersVersionsData = Array($currentVersionId, $versionId);

      $details = array('posting_id' => $buyerChannelId ,'old_version' => $currentVersionId, 'new_version' => $versionId);

        $diffData = "";
      if(count($FilterCurrentVersionDiference)) {
        foreach($FilterCurrentVersionDiference as $key => $filter) {
          $diffData .= '"'.$filter.'",';
        }

        $diffData = substr($diffData,0,-1);
        $result = T3Db::api()->fetchAll("SELECT * FROM `buyers_filters_versions_data` WHERE version_id in (?,?) AND type_name in (".$diffData.")",
          Array($currentVersionId, $versionId)
        );

        $details['currentVersionDiference'] = $result;


      }

        $template = new Zend_View();
        $template->setScriptPath(dirname(__FILE__).DS.'templates');

        $template->assign(Array(
            'posting_id'  => $buyerChannelId,
            'old_version' => $currentVersionId
          )
        );

        $details['filters_old_version_data'] = $template->render('showOldVersionFilters.phtml');

        $template->assign(Array(
            'posting_id'  => $buyerChannelId,
            'new_version' => $versionId
          )
        );

        $details['filters_new_version_data'] = $template->render('showNewVersionFilters.phtml');



      $event_type_id = T3TimeLine_EventType::getIdByType(T3TimeLine_EventType::POSTING_FILTERS_CHANGED);

      if($event_type_id){
        T3TimeLine_Event::add($buyerId,
                            T3TimeLine_Event::TYPE_BUYER,
                            $event_type_id,
                            $details,
                            $event_rating
        );
      }




      return $versionId;

    }catch(Exception $e){
      $database->rollBack();
    }

  }

  public static function ItemsDiffers($baseItem, $newItem){
    return
      $baseItem['affirmative'] != $newItem['affirmative'] ||
      $baseItem['works'] != $newItem['works'] ||
      $baseItem['misc'] != $newItem['misc'];
  }

  public static function CurrentVersionDiference($buyerChannelId, $data){

    $database = T3System::getConnect();

    if(!empty($data)){

      $data = groupBy($data, array(), 'type_name');

      $currentVersionArrayData = $database->fetchAll('
        select * from buyers_filters_conditions_test where channel_id = ?
      ', $buyerChannelId);

      $currentVersionArrayData = groupBy($currentVersionArrayData, array(), 'type_name');

      $modificationsData = array();
      foreach($currentVersionArrayData as $k => $v){
        if(!isset($data[$k])){
          $modificationsData[] = $k;
          continue;
        }

        $v1 = $data[$k];

        if(
          $v['affirmative'] != $v1['affirmative'] ||
          $v['works'] != $v1['works'] ||
          $v['misc'] != $v1['misc']
        ){
          $modificationsData[] = $k;
        }

      }

    }else
      $modificationsData = array();

    return $modificationsData;

  }

  public static function ThereIsVersion($buyerChannelId, $versionId){

    $database = T3System::getConnect();

    return $database->fetchRow('select count(*)>0 from buyers_filters_versions where id = ?', array($versionId)) != 0;

  }

  public static function InjectItemsToCurrentVersion($buyerChannelId, $injectionVersionId, $items){
    die( __METHOD__ );
    $database = T3System::getConnect();

    $newVersionId = T3FiltersVersioning::SaveAndSetCurrentVersion($buyerChannelId, 'revert');

    try{

      $database->beginTransaction();

      $newVersionData = $database->fetchAll('select * from buyers_filters_versions_data where version_id = ?', array($newVersionId));
      $newVersionData = groupBy($newVersionData, array(), 'type_name');
      $injectionVersionData = $database->fetchAll('select * from buyers_filters_versions_data where version_id = ?', array($injectionVersionId));
      $injectionVersionData = groupBy($injectionVersionData, array(), 'type_name');

      $diffItems = array();
      foreach($newVersionData as $k => $v){

        if(!in_array($k, $items))
          continue;

        if(!isset($injectionVersionData[$k]))
          continue;

        $v1 = $injectionVersionData[$k];

        if(!self::ItemsDiffers($v, $v1))
          continue;

        $diffItems[] = $k;

      }


      $itemsString = $database->quote($diffItems);

      $database->query("
        delete from buyers_filters_versions_data
        where type_name in ($itemsString) and version_id = ?
      ", array($newVersionId));


      $database->query("
        insert into buyers_filters_versions_data
        select 0,channel_id,type_name,affirmative,works,misc,? from buyers_filters_versions_data
        where type_name in ($itemsString) and version_id = ?
      ", array($newVersionId, $injectionVersionId));

      $database->query("
        update buyers_filters_versions set modifications_data = ? where id = ?
      ", array(serialize($diffItems), $newVersionId));

      $database->commit();


    }catch(Exception $e){
      $database->rollBack();
    }

    return $newVersionId;


  }

  public static function LoadVersion($buyerChannelId, $versionId){

    $database = T3System::getConnect();

    $versionData = $database->fetchRow('select * from buyers_filters_versions where id = ?', array($versionId));

    if($buyerChannelId != $versionData['buyer_channel_id'])
      return false;

    try{

      $database->beginTransaction();

      $database->query('
        delete from buyers_filters_conditions_test where channel_id = ?
      ', array($buyerChannelId));

      $database->query('
        insert into buyers_filters_conditions_test
        select
          0, channel_id, type_name, affirmative, works, misc
        from buyers_filters_versions_data where version_id = ?
      ', array($versionId));

      $database->query('update buyers_channels set current_filters_version_id = ? where id = ?',
        array($versionId, $buyerChannelId)
      );

      $database->commit();

    }catch(Exception $e){
      $database->rollBack();
    }


  }

  public static function InitializeSystem(){

    $database = T3System::getConnect();

    $thereIsData = $database->fetchOne('select count(*)>0 from buyers_filters_versions')!=0;

    if($thereIsData)
      return false;

    $buyersChannelsIds = $database->fetchCol('select distinct channel_id from buyers_filters_conditions_test');

    foreach($buyersChannelsIds as $v)
      T3FiltersVersioning::SaveAndSetCurrentVersion($v, null);

  }


  public static function GetVersionData($versionId){

    $database = T3System::getConnect();

    $versionData = $database->fetchRow('select * from buyers_filters_versions where id = ?', array($versionId));

    return $versionData;

  }

  public static function GetVersionArrayData($versionId){

    $database = T3System::getConnect();

    $versionArrayData = $database->fetchAll('select * from buyers_filters_versions_data where version_id = ?', array($versionId));

    $versionArrayData = groupBy($versionArrayData, array(), 'type_name');

    return $versionArrayData;

  }

  public static function ClearAllVersions(){

    $database = T3System::getConnect();

    $database->query('delete from buyers_filters_versions');
    $database->query('delete from buyers_filters_versions_data');
    $database->query('update buyers_channels set current_filters_version_id = null');

  }

  public static function GetAllVersionsData($buyerChannelId){

    $database = T3System::getConnect();

    $data = $database->fetchAll('
      select buyers_filters_versions.*, users.nickname as author_nickname
      from buyers_filters_versions
      left join users on buyers_filters_versions.author_id = users.id
      where buyers_filters_versions.buyer_channel_id = ?
    ', array($buyerChannelId));

    return $data;

  }

  public static function GetBuyerChannelData($buyerChannelId){
    return T3System::getConnect()->fetchRow('select * from buyers_channels where id = ?', array($buyerChannelId));
  }

  public static function CopyFilterToTest($buyerChannelId){

    $database = T3System::getConnect();

    try{

      $database->beginTransaction();

      $database->query('delete from buyers_filters_conditions_test where channel_id = ?', array($buyerChannelId));
      $database->query('
        insert into buyers_filters_conditions_test
        select * from buyers_filters_conditions
        where channel_id = ?
      ', array($buyerChannelId));

      $database->commit();

    }catch(Exception $e){
      $database->rollBack();
    }

  }

  public static function InitializeTestTable(){

    $database = T3System::getConnect();

    $database->query("delete from buyers_filters_conditions_test");
    $database->query("insert into buyers_filters_conditions_test select * from buyers_filters_conditions");

  }


}
