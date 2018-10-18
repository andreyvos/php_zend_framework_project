<?php

TableDescription::addTable('form_template', array(
    'id',
    'createDate',
    'product',
    'name',
    
    'title',
    'description',
    
    'steps',
    
    'minWidth',
    'maxWidth',
    
    'minHeight',
    'maxHeight',
    
    'design_settings_type',
    
    'active',
));

class T3FormTemplate extends DbSerializable {
    public $id;
    public $createDate;
    public $product;
    public $name;
    
    public $title;
    public $description;
    
    public $steps;
    
    public $minWidth;
    public $maxWidth;
    
    public $minHeight; 
    public $maxHeight; 
    
    /**
    * Тип интерфейса для настйроки CSS Файла для дизайна.
    * Файлы лежат в {class_dir}/Settings_CSS/{design_settings_type}.php
    * Если design_settings_type = '', значит для этого темплейта нет возможности
    * настраивать дизанй через динамический CSS файл.
    * 
    * @var string
    */
    public $design_settings_type;
    public $active;

    public function  __construct() {
        parent::__construct();
        $this->tables[] = "form_template";
    }
    
}
?>
