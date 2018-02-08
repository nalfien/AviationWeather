<?php
  class month {
    var $month;
    var $days;
    var $avgHigh;
    var $avgLow;
    var $perfectTime;
    var $dayVFRTime;
    var $VFRTime;
    var $totalTime;
    var $processed;
    
    function __construct($month) {
      $this->month = $month;
      $this->days = array();
      $this->avgHigh = 0;
      $this->avgLow = 0;
      $this->perfectTime = 0;
      $this->dayVFRTime = 0;
      $this->VFRTime = 0;
      $this->totalTime = 0;
      $this->totalDayTime = 0;
      $this->processed = false;
    }
    
    function getDay($day) {
      if(!array_key_exists($day, $this->days)) {
        $this->days[$day] = new day;
      }
      
      return $this->days[$day];
    }
    
    function processMonth() {
      $dayCount = count($this->days);
      
      foreach($this->days as $day) {
        $this->avgHigh += $day->high;
        $this->avgLow += $day->low;
      }
      
      $this->avgHigh /= $dayCount;
      $this->avgLow /= $dayCount;
      
      $this->perfectTime /= $this->totalDayTime;
      $this->dayVFRTime /= $this->totalDayTime;
      $this->VFRTime /= $this->totalTime;
      
      unset($this->days);
      
      $this->processed = true;
    }
  }
?>