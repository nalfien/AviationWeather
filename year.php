<?php
  class year {
    var $year;
    var $months;
    var $highHigh;
    var $lowHigh;
    var $highLow;
    var $lowLow;
    var $processed;
    var $perfectTime;
    var $dayVFRTime;
    var $VFRTime;
    var $bestMonth;
    var $worstMonth;
    
    function __construct($year) {
      $this->year = $year;
      $this->months = array();
      $this->highHigh = -1;
      $this->lowHigh = 1000;
      $this->highLow = -1;
      $this->lowLow = 1000;
      $this->perfectTime = 0;
      $this->dayVFRTime = 0;
      $this->VFRTime = 0;
      $this->bestMonth = array();
      $this->worstMonth = array();
      $this->reasons = array("VeryWindy"=>"0","Visibility"=>"0","LowCloud"=>"0","Windy"=>"0","NotClear"=>"0");
      $this->maxReason = "";
      $this->processed = false;
    }
    
    function getMonth($month) {
      if(!array_key_exists($month, $this->months)) {
        foreach($this->months as $monthObj) {
          if(!$monthObj->processed) {
            $monthObj->processMonth();
          }
        }
        
        $this->months[$month] = new month($month);
      }
      
      return $this->months[$month];
    }
    
    function addTime($isVeryWindy, $isVisible, $isLowCloud, $isDay, $isWindy, $isClear, $currMonth, $hourDiff) {
      $currMonth = $this->getMonth($currMonth);
      $currMonth->totalTime += $hourDiff;
      
      if($isDay) {
        $currMonth->totalDayTime += $hourDiff;
      }
      
      if(!$isVeryWindy && $isVisible && !$isLowCloud) {
        $this->VFRTime += $hourDiff;
        $currMonth->VFRTime += $hourDiff;
        
        if($isDay) {
          $this->dayVFRTime += $hourDiff;
          $currMonth->dayVFRTime += $hourDiff;
          
          if(!$isWindy && $isClear) {
            $this->perfectTime += $hourDiff;
            $currMonth->perfectTime += $hourDiff;
          }
        }
      }
      
      if($isVeryWindy) {
        $this->reasons["VeryWindy"] += $hourDiff;
      }
      if(!$isVisible) {
        $this->reasons["Visibility"] += $hourDiff;
      }
      if($isLowCloud) {
        $this->reasons["LowCloud"] += $hourDiff;
      }
      if($isWindy) {
        $this->reasons["Windy"] += $hourDiff;
      }
      if(!$isClear) {
        $this->reasons["NotClear"] += $hourDiff;
      }
    }
    
    function processYear() {
      foreach($this->months as $month) {
        if(!$month->processed) {
          $month->processMonth();
        }
        
        if($month->avgHigh > $this->highHigh) {
          $this->highHigh = $month->avgHigh;
        }
        
        if($month->avgHigh < $this->lowHigh) {
          $this->lowHigh = $month->avgHigh;
        }
        
        if($month->avgLow > $this->highLow) {
          $this->highLow = $month->avgLow;
        }
        
        if($month->avgLow < $this->lowLow) {
          $this->lowLow = $month->avgLow;
        }
        
        if(sizeof($this->bestMonth) == 0 || $this->bestMonth["percent"] < $month->dayVFRTime) {
          $this->bestMonth["name"] = $month->month;
          $this->bestMonth["percent"] = $month->dayVFRTime;
        }
        
        if(sizeof($this->worstMonth) == 0 || $this->worstMonth["percent"] > $month->dayVFRTime) {
          $this->worstMonth["name"] = $month->month;
          $this->worstMonth["percent"] = $month->dayVFRTime;
        }
      }
      
      $maxTime = -1;
      
      foreach($this->reasons as $reason => $reasonTime) {
        if($reasonTime > $maxTime) {
          $this->maxReason = $reason;
          $maxTime = $reasonTime;
        }
      }
      
      unset($this->months);
      
      $this->processed = true;
    }
    
    function output() {
      return $this->highHigh . " / " . $this->lowHigh . " | " . $this->highLow . " / " . $this->lowLow . "<br/>";
    }
  }
?>