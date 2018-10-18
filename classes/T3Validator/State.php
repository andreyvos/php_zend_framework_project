<?php

class T3Validator_State extends T3Validator_InArray {

  public function getMessage($code){
    return self::INVALID_TEXT;
  }

  public function  initialize() {
    parent::defInit(array(
      'AL', 'AK', 'AZ', 'AR', 'CA',
      'CO', 'CT', 'DE', 'DC', 'FL',
      'GA', 'HI', 'ID', 'IL', 'IN',
      'IA', 'KS', 'KY', 'LA', 'ME',
      'MD', 'MA', 'MI', 'MN', 'MS',
      'MO', 'MT', 'NE', 'NV', 'NH',
      'NJ', 'NM', 'NY', 'NC', 'ND',
      'OH', 'OK', 'OR', 'PA', 'RI',
      'SC', 'SD', 'TN', 'TX', 'UT',
      'VT', 'VA', 'WA', 'WV', 'WI',
      'WY',
    ));
  }

  public function defInit(){}
  
    /*
    array('AL','Alabama'),          array('AK','Alaska'),       array('AZ','Arizona'),      array('AR','Arkansas'),             array('CA','California'),
    array('CO','Colorado'),         array('CT','Connecticut'),  array('DE','Delaware'),     array('DC','District of Columbia'), array('FL','Florida'),
    array('GA','Georgia'),          array('HI','Hawaii'),       array('ID','Idaho'),        array('IL','Illinois'),             array('IN','Indiana'),
    array('IA','Iowa'),             array('KS','Kansas'),       array('KY','Kentucky'),     array('LA','Louisiana'),            array('ME','Maine'),
    array('MD','Maryland'),         array('MA','Massachusetts'),array('MI','Michigan'),     array('MN','Minnesota'),            array('MS','Mississippi'),
    array('MO','Missouri'),         array('MT','Montana'),      array('NE','Nebraska'),     array('NV','Nevada'),               array('NH','New Hampshire'),
    array('NJ','New Jersey'),       array('NM','New Mexico'),   array('NY','New York'),     array('NC','North Carolina'),       array('ND','North Dakota'),
    array('OH','Ohio'),             array('OK','Oklahoma'),     array('OR','Oregon'),       array('PA','Pennsylvania'),         array('RI','Rhode Island'),
    array('SC','South Carolina'),   array('SD','South Dakota'), array('TN','Tennessee'),    array('TX','Texas'),                array('UT','Utah'),
    array('VT','Vermont'),          array('VA','Virginia'),     array('WA','Washington'),   array('WV','West Virginia'),        array('WI','Wisconsin'),
    array('WY','Wyoming'),
    */

}