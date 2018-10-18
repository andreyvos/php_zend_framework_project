<?php

class T3Banners {

  const BANERS_FOLDER_NAME = 'promo';

  const DOESNT_MATTER_ENTIRY = "doesntMatter";
  public $doesntMatterEntity = array(
    'name' => self::DOESNT_MATTER_ENTIRY,
    'title' => "Doesn't matter",
  );


  public $allowedFileMime = array(/**/);

  public $products;

  public $retrieveBannerUpload;

  const TYPE_STATIC_IMAGE = 'static_image';
  const TYPE_ANIMATED_IMAGE = 'animated_image';
  const TYPE_FLASH = 'flash';
  const TYPE_TEXT = 'text';

  public $typesData = array(
    self::TYPE_STATIC_IMAGE => array(
      'name' => self::TYPE_STATIC_IMAGE,
      'title' => 'Static image',
      'fullTitle' => 'Static Banner',
    ),
    self::TYPE_ANIMATED_IMAGE => array(
      'name' => self::TYPE_ANIMATED_IMAGE,
      'title' => 'Animated gif',
      'fullTitle' => 'Animated Banner',
    ),
    self::TYPE_FLASH => array(
      'name' => self::TYPE_FLASH,
      'title' => 'Flash banner',
      'fullTitle' => 'Flash Banner',
    ),
    self::TYPE_TEXT => array(
      'name' => self::TYPE_TEXT,
      'title' => 'Text banner',
      'fullTitle' => 'Text Banner',
    ),
  );

  public $types = array(/**/);
  public $typesNames = array(/**/);



////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


  const SIZE_SMALL = 'small';
  const SIZE_MEDIUM = 'medium';
  const SIZE_LARGE = 'large';

  public $sizesData = array(
    self::SIZE_SMALL => array(
      'name' => self::SIZE_SMALL,
      'value' => '0',
      'title' => 'Small',
      'fullTitle' => 'Small Banner',
    ),
    self::SIZE_MEDIUM => array(
      'name' => self::SIZE_MEDIUM,
      'value' => '160',
      'title' => 'Medium',
      'fullTitle' => 'Medium Banner',
    ),
    self::SIZE_LARGE => array(
      'name' => self::SIZE_LARGE,
      'value' => '350',
      'title' => 'Large',
      'fullTitle' => 'Large Banner',
    ),
  );

  public $sizeGrads = array(/**/);
  public $sizeGradsKeys = array(/**/);
  public $bannerSizesNames = array(/**/);


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////



  const SHAPE_VERTICAL = 'vertical';
  const SHAPE_TALL = 'tall';
  const SHAPE_LONG = 'long';
  const SHAPE_SQUARE = 'square';
  const SHAPE_HORIZONTAL = 'horizontal';

  /*[0]=>
  float(0)
  [1]=>
  float(0.19891236737966)
  [2]=>
  float(0.72654252800536)
  [3]=>
  float(1.3763819204712)
  [4]=>
  float(5.0273394921258)
*/
  
  public $shapesData = array(
    self::SHAPE_VERTICAL => array(
      'name' => self::SHAPE_VERTICAL,
      'value' => 0.0,
      'title' => 'Vertical',
      'fullTitle' => 'Vertical Banner',
    ),
    self::SHAPE_TALL => array(
      'name' => self::SHAPE_TALL,
      'value' => 0.19891236737966,
      'title' => 'Tall',
      'fullTitle' => 'Tall Banner',
    ),
    self::SHAPE_SQUARE => array(
      'name' => self::SHAPE_SQUARE,
      'value' => 0.72654252800536,
      'title' => 'Square',
      'fullTitle' => 'Square Banner',
    ),
    self::SHAPE_LONG => array(
      'name' => self::SHAPE_LONG,
      'value' => 1.3763819204712,
      'title' => 'Long',
      'fullTitle' => 'Long Banner',
    ),
    self::SHAPE_HORIZONTAL => array(
      'name' => self::SHAPE_HORIZONTAL,
      'value' => 5.0273394921258,
      'title' => 'Horizontal',
      'fullTitle' => 'Horizontal Banner',
    ),
  );

  public $tansGrads = array(/**/);
  public $tansGradsKeys = array(/**/);
  public $bannerShapesNames = array(/**/);

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

  protected static $_instance = null;
  protected $database;

  protected $allTableData;


  protected function __construct(){

    $this->database = T3Db::api();

    $this->types = array_keys($this->typesData);

    $this->typesNames = array();
    foreach($this->typesData as $v){
      $this->typesNames[$v['name']] = $v['title'];
    }

    foreach($this->sizesData as $v){
      $this->sizeGrads[$v['value']] = $v['name'];
    }

    foreach($this->shapesData as $v){
      $this->tansGrads[$v['value']] = $v['name'];
    }

    $this->sizeGradsKeys = array_keys($this->sizeGrads);
    $this->tansGradsKeys = array_keys($this->tansGrads);

    foreach($this->sizesData as $v){
      $this->bannerSizesNames[$v['name']] = $v['title'];
    }
    
    foreach($this->shapesData as $v){
      $this->bannerShapesNames[$v['name']] = $v['title'];
    }

    $this->allowedFileMime = array(
      FileFormats::$data[FileFormats::FLASH]['mime'],
      FileFormats::$data[FileFormats::GIF]['mime'],
      FileFormats::$data[FileFormats::JPEG]['mime'],
      FileFormats::$data[FileFormats::PNG]['mime'],
    );


  }

  /** @return T3Banners  */
  public static function getInstance() {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }


  public function getPagesForUserAndProduct($product, $webmasterId){

    $data = $this->database->fetchAll('
      SELECT url, accepts_referrers, is_t3cms
      FROM channels_js_forms
      WHERE product = ? AND (company_id = ? OR accepts_referrers)
      GROUP BY url, accepts_referrers, is_t3cms
      ORDER BY accepts_referrers DESC
    ', array($product, $webmasterId));

    $result = array();

    foreach($data as $v){
      $b = true;
      $partialUrl = new T3Banners_PartialUrl($v);
      foreach($result as $v2)
        if($partialUrl->equals($v2)){
          $b = false;
          break;
        }
      if(!$b)
        continue;
      $result[] = $partialUrl;
    }

    return $result;

  }


  public function getAllTableData($showHidden){
    if(is_null($this->allTableData))
      $this->allTableData = $this->database->fetchAll("
        SELECT

          b.product, b.*,
          lt.title as product_title

        FROM webmasters_banners AS b
        LEFT JOIN leads_type AS lt
        ON b.product=lt.name
        WHERE NOT ifnull(hide_in_admin_table, 0) OR ?
        ORDER BY b.creation_datetime DESC
      ", array((int)$showHidden));
    return $this->allTableData;
  }

  public function getProductsBannersCount(){
    return groupBy($this->database->fetchAll('
      SELECT lt.title as `title`, wb.product as `name` , count(*) as `c`
      FROM webmasters_banners as wb
      LEFT JOIN leads_type as lt
      ON wb.product = lt.name
      WHERE 
      wb.hide_in_admin_table <> "1" and lt.activ="1"
      GROUP BY wb.product
      ORDER BY lt.prioritet DESC
    '), null, 'name');
  }

  public function deleteBanner($id){
    $ar = $this->database->fetchAll('
      SELECT file_path
      FROM webmasters_banners
      WHERE id=?
    ', $id);
    if(!count($ar))
      return false;
    $fileName = $this->getAbsoluteBannerPath($ar[0]['file_name']);
    if(is_file($fileName))
      unlink($fileName);
    $this->database->query('
      DELETE FROM webmasters_banners
      WHERE id=?
    ', $id);
    $this->database->query('
      DELETE FROM webmasters_banners_history
      WHERE banner_id=?
    ', $id);
    return true;
  }

  public function getAllBannersForWebmaster($id = null){

    if(is_null($id))
      $id = T3Users::getInstance()->getCurrentUser()->company_id;

    $ar = $this->database->fetchAll('
      SELECT b.*
      FROM webmasters_banners_history as bh
      LEFT JOIN webmasters_banners as b
      ON bh.banner_id=b.id
      WHERE bh.webmaster_id=?
      ORDER BY bh.choice_datetime DESC
    ', $id);

    $result = array();

    foreach($ar as $v){
      $object = new T3Banner();
      $object->fromArray($v);
      $result[] = $object;
    }

    return $result;
    
  }

  public function writeBannerToHistory($bannerId){
    $this->database->query('
      REPLACE INTO webmasters_banners_history
      SET webmaster_id = ?, banner_id = ?, choice_datetime = ?
    ', array(T3Users::getInstance()->getCurrentUser()->company_id, $bannerId, mySqlDateTimeFormat()));
  }

  public function searchBanners($params){


    $select = $this->database->select(array('*'))->from('webmasters_banners');

    if(!empty($params['product']))
      $select->where('product = ?', $params['product']);

    if($params['size'] != self::DOESNT_MATTER_ENTIRY){
      $select->where('sign_size = ?', $params['size']);
    }
      
    if($params['shape'] != self::DOESNT_MATTER_ENTIRY){
      switch($params['shape']){
        case self::SHAPE_VERTICAL:
          $ar = array(
            $this->shapesData[self::SHAPE_VERTICAL]['name'],
            $this->shapesData[self::SHAPE_TALL]['name'],
          );
          break;
        case self::SHAPE_TALL:
          $ar = array(
            $this->shapesData[self::SHAPE_VERTICAL]['name'],
            $this->shapesData[self::SHAPE_TALL]['name'],
            $this->shapesData[self::SHAPE_SQUARE]['name'],
          );
          break;
        case self::SHAPE_SQUARE:
          $ar = array(
            $this->shapesData[self::SHAPE_TALL]['name'],
            $this->shapesData[self::SHAPE_SQUARE]['name'],
            $this->shapesData[self::SHAPE_LONG]['name'],
          );
          break;
        case self::SHAPE_LONG:
          $ar = array(
            $this->shapesData[self::SHAPE_SQUARE]['name'],
            $this->shapesData[self::SHAPE_LONG]['name'],
            $this->shapesData[self::SHAPE_HORIZONTAL]['name'],
          );
          break;
        case self::SHAPE_HORIZONTAL:
          $ar = array(
            $this->shapesData[self::SHAPE_LONG]['name'],
            $this->shapesData[self::SHAPE_HORIZONTAL]['name'],
          );
          break;
        default:
          $ar = array(
            $this->shapesData[self::SHAPE_VERTICAL]['name'],
            $this->shapesData[self::SHAPE_TALL]['name'],
            $this->shapesData[self::SHAPE_SQUARE]['name'],
            $this->shapesData[self::SHAPE_LONG]['name'],
            $this->shapesData[self::SHAPE_HORIZONTAL]['name'],
          );
      }
      $s = dbQuote($this->database, $ar);
      $select->where("sign_shape IN ($s)");
    }

    if(!empty($params['types'])){
      $types = dbQuote($this->database, $params['types']);
      $select->where("type IN ($types)");
    }
    
    $select->where("hide_in_admin_table='0'");

    $data = $this->database->fetchAll($select->order('height')->order('width desc')->order('id desc'));

    $result = array();
    foreach($data as $v){
      $a = new T3Banner();
      $a->fromArray($v);
      $result[$a->id] = $a;
    }
    return $result;

  }


  public function getAbsoluteBannerPath($dest){
    return $_SERVER['DOCUMENT_ROOT'] . $dest;
  }

/*
  public function cleanUnlinkedFiles(){

    $resp = $this->db->fetchAll('
      SELECT file_path
      FROM webmasters_banners
    ');
    $ar = array();
    foreach($resp as $v)
      $ar[] = $v['file_path'];

    $dh = opendir($this->bannersFolderAbsolute);
    if($dh){
      while (($file = readdir($dh)) !== false) {
        $fullPath = $this->bannersFolderAbsolute . $file;
        if(filetype($fullPath)!='file')
          continue;
        if(!in_array($fullPath, $ar))
          unlink($fullPath);
      }
      closedir($dh);
    }

  }*/

}


  T3Banners::getInstance();
