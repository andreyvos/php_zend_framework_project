<?php 
  class T3BloomFilter {
    private $hashbits;
    private $hashKeys = array(0,0,0);
    private $fileName;
    private $vector_size = 0;

    public function __construct() {
      $this->fileName = dirname(__FILE__) . "/../cache/cashNetUSA.current.bloom";
    }


    public function hashString($s) {
      $workHashCode = 0;
      for ($i = 0; $i < strlen($s); $i++)
        $workHashCode = ((($workHashCode << ($i % 4)) + ord($s[$i])) % (pow(2,28)));
      return $workHashCode;
    }

    public function encodeString($s) {
      return $this->hashString(md5($s));
    }

    public function createHashes($str) {
      $h1 = $this->encodeString($str);
      $h2 = $this->hashString($str);
      

      $this->hashKeys[0] = ($h1 % ($this->vector_size));
      $this->hashKeys[1] = (($h1 + $h2) % ($this->vector_size));
      $this->hashKeys[2] = (($h1 + 2*$h2) % ($this->vector_size));
     return $this->hashKeys;
    }

    public function includes($str) {
         $fp = fopen($this->fileName, "r");
         $this->vector_size = filesize($this->fileName)*8;
         $this->createHashes($str);
         foreach($this->hashKeys as $hash){
           fseek($fp, floor($hash / 8)-(floor($hash/8)%4)); //seek to the word that would represent the byte we want
           $word_array = str_split(fread($fp,4)); // get the word we want
           $word_num = ($hash % 32) >> 3;
           $bit_num = ($hash % 8);
           $byte = ord($word_array[$word_num]) & (1 << $bit_num);
           //parse the get the appropriate bit out of the byte
           //return false if bit didnt match
           if (!$byte) {
                fclose($fp);
        return false;
             }
         }
         fclose($fp);
         return true;
       }
}
?>
