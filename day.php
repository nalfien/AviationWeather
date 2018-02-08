<?php
  class day {
    var $high;
    var $low;
    
    function __construct() {
      $this->high = -1;
      $this->low = 1000;
    }
    
    function addTemp($temp) {
      if($temp > $this->high) {
        $this->high = $temp;
      }
      
      if($temp < $this->low) {
        $this->low = $temp;
      }
    }
  }
?>