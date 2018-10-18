<?php

class T3Forms {

  static protected $cache = array();

  public static function get($formName){   
    $class = 'T3Form_' . $formName;
    if(!isset(self::$cache[$class])){
      if(!@class_exists($class)){
        trigger_error("T3Forms : form class $class doesn't exist", E_USER_ERROR);
        return;
      }
      self::$cache[$class] = new $class();
    }
    return self::$cache[$class];
  }

}