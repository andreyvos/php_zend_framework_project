<?php

TableDescription::addTable('webmasters_banners', array(
  'id',                       //  int(11)
  'code_name',                //  char(20)
  'creation_datetime',        //  datetime
  'width',                    //  int(5)
  'height',                   //  int(5)
  'url',                      //  text
  'file_name',                //  varchar(255)
  'file_path',                //  text
  'file_mime_type',           //  text
  'type',                     //  enum('static_image','animated_image','flash','text')
  'product',                  //  varchar(200)
  'sign_size',                //  enum('small','medium','large')
  'sign_shape',               //  enum('long','square','tall','vertical','horizontal')
  'text_banner_content',      //  text
  'hide_in_admin_table',      //  tinyint(1)
));


class T3Banner extends DbSerializable {

  public $id;
  public $code_name;
  public $creation_datetime;
  public $width;
  public $height;
  public $url;
  public $file_name;
  public $file_path;
  public $file_mime_type;
  public $type;
  public $product;
  public $sign_size;
  public $sign_shape;
  public $text_banner_content;
  public $hide_in_admin_table;


  public $retrieveUploadObject;



  public function __construct() {

    parent::__construct();

    $this->tables = array('webmasters_banners');
    $this->readNewIdAfterInserting = false;

  }

  public function getProduct($lazy = true){
    return $this->product;
  }


  public function getProductTitle($lazy = true){
    return T3Products::getTitle($this->product);
  }

  public function calcSigns(){

    if(is_null($this->width) || is_null($this->height) || $this->type === T3Banners::TYPE_TEXT){
      $this->sign_size = null;
      $this->sign_shape = null;
      return;
    }


    $core = T3Banners::getInstance();


    $max = max($this->width, $this->height);
    $n1 = count($core->sizeGradsKeys)-1;
    for($i1 = 0; $i1<$n1; $i1++)
      if($max<=(int)$core->sizeGradsKeys[$i1+1])
        break;

    $this->sign_size = $core->sizeGrads[$core->sizeGradsKeys[$i1]];

    $tan = $this->width/$this->height;
    $n1 = count($core->tansGradsKeys)-1;

    for($i1 = 0; $i1<$n1; $i1++)
      if($tan<=(double)$core->tansGradsKeys[$i1+1])
        break;

    $this->sign_shape = $core->tansGrads[$core->tansGradsKeys[$i1]];


  }

  public function getAbsoluteBannerPath(){
    return $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $this->file_path;
  }

  public function getRandomCodeName(){
    if(empty($this->code_name))
      $this->code_name = randomString(20);
    return $this->code_name;
  }

  public function createBanner(){
    $this->creation_datetime = mySqlDateTimeFormat();   
    $this->insertIntoDatabase();
  }

  public function setFileName($fileName){
    $this->file_name = $fileName;
    $this->file_path = T3Banners::BANERS_FOLDER_NAME . DIRECTORY_SEPARATOR . $this->file_name;
    $this->url = '/'.T3Banners::BANERS_FOLDER_NAME.'/' . $this->file_name;
  }

  public function realizeSize(){

    switch($this->type){
      case T3Banners::TYPE_STATIC_IMAGE :
      case T3Banners::TYPE_ANIMATED_IMAGE :
      case T3Banners::TYPE_FLASH :
        $a = getimagesize($this->getAbsoluteBannerPath(), $n);
        $this->width = $a[0];
        $this->height = $a[1];
      break;
      case T3Banners::TYPE_TEXT :
        $this->width = null;
        $this->height = null;
      break;
    }
    
  }

  public function saveTextBannerFile(){
    $view = new Zend_View();
    $view
      ->addScriptPath(dirname(__FILE__) . DS . 'T3Banners' . DS)
      ->addHelperPath(LIBS . DS . 'Helpers' . DS, 'MyZend_View_Helper_')
      ->setEncoding("UTF-8");

    $view->text = addcslashes(str_replace("\n"," ",$this->text_banner_content), '"');

    $data = $view->render('textBannerCode.phtml');
    $h = fopen($this->getAbsoluteBannerPath(), 'w');
    if($h===false)
      return false;
    fwrite($h, $data);
    fclose($h);


    $this->realizeSize();
    $this->calcSigns();

    return true;
  }

  public function setTextBanner($text){

    $this->text_banner_content = $text;
    $this->setFileName($this->getRandomCodeName() . '.js');
    $this->file_mime_type = null;
    $this->type = T3Banners::TYPE_TEXT;
    $this->saveTextBannerFile();

  }

  public function retrieveUpload(){

    $fileKey = "banner_file";

    $this->retrieveUploadObject = new FileUploadRetriever();

    $this->retrieveUploadObject->maximumSize = 0;//1000000;
    $this->retrieveUploadObject->allowOnlyFormats(T3Banners::getInstance()->allowedFileMime);
    $this->retrieveUploadObject->setRandomFileName = false;
    $this->retrieveUploadObject->fileDestination = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . T3Banners::BANERS_FOLDER_NAME;
    $this->retrieveUploadObject->fileKey = $fileKey;
    $this->retrieveUploadObject->fileName = $this->getRandomCodeName();
    $this->retrieveUploadObject->forceFileExtensionAdding = true;

    if($this->retrieveUploadObject->retrieveFile() === false)
      return false;

    /*switch(FileFormats::$data[$_FILES[$fileKey]['type']]){
      case FileFormats::FLASH :
        $type = T3Banners::TYPE_FLASH;
      case FileFormats::GIF:
        $type = T3Banners::TYPE_ANIMATED_IMAGE;
      default:
        $type = T3Banners::TYPE_STATIC_IMAGE;
    }
    $this->type = $type;
     */

    $this->setFileName($this->retrieveUploadObject->fileName);
    $this->file_mime_type = $this->retrieveUploadObject->fileMime;    
    $this->realizeSize();
    $this->calcSigns();

  }


 }





