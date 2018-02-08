<?php
  class data {
    var $years;           // Array to hold all the years processed
	
	// Temperature variables for the whole period of time
    var $highHigh;        // Highest daily high
    var $lowHigh;         // Lowest daily high
    var $highLow;         // Highest daily low
    var $lowLow;          // Lowest daily low
	
	// Averages of various types of time for each year in the period
    var $avgPerfectTime;  // "Perfect" time
    var $avgDayVFRTime;   // Day VFR Time
    var $avgVFRTime;      // All VFR (day and night) time
    
	var $prevDate;        // The previous date, to check and see if we have a new day
    
	// Arrays to hold months in various ways
	var $bestMonths;      // Best months, by year
    var $worstMonths;     // Worst months, by year
    var $bestMonth;       // Best single month, overall
    var $worstMonth;      // Worst single month, overall
    
    function __construct() {
      $this->years = array();
      $this->highHigh = -1;
      $this->lowHigh = 1000;
      $this->highLow = -1;
      $this->lowLow = 1000;
      $this->prevDate = "";
      $this->avgPerfectTime = 0;
      $this->avgDayVFRTime = 0;
      $this->avgVFRTime = 0;
      $this->bestMonths = array();
      $this->worstMonths = array();
      $this->bestMonth = array();
      $this->worstMonth = array();
      $this->reasons = array("VeryWindy"=>"0","Visibility"=>"0","LowCloud"=>"0","Windy"=>"0","NotClear"=>"0");
      $this->maxReason = "";
    }
    
	// Load data from the database
    function getFromDB($dbRow) {
      $this->highHigh = $dbRow["highhigh"];
      $this->lowHigh = $dbRow["lowhigh"];
      $this->highLow = $dbRow["highlow"];
      $this->lowLow = $dbRow["lowlow"];
      $this->avgPerfectTime = $dbRow["perfect"];
      $this->avgDayVFRTime = $dbRow["dayvfr"];
      $this->avgVFRTime = $dbRow["vfr"];
      $this->bestMonth["name"] = $dbRow["bestmonth"];
      $this->worstMonth["name"] = $dbRow["worstmonth"];
      $this->maxReason = $dbRow["reason"];
    }
    
	// Take a year, and process it, if it hasn't been yet
    function getYear($year) {
      if(!array_key_exists($year, $this->years)) {
        foreach($this->years as $yearObj) {
          if(!$yearObj->processed) {
            $yearObj->processYear();
          }
        }
        
        $this->years[$year] = new year($year);
      }
      
      return $this->years[$year];
    }
    
	/* Process a line from the main source
	 * -Determine local time for the weather source
	 * -If previous date hasn't been set yet, do so
	 * -Otherwise, determine how long between lines
	 * -Determine the various values:
	 * --Is the line during the day
	 * --Is it very windy
	 * --Is it windy
	 * --Is visibility good
	 * --Are there no clouds
	 * --If it's not clear, determine if low clouds exist
	 * -Pass the calculated data to the lower time spans
	 */
    function procLine($metar) {
      $localtime = date("Y-m-d h:i:sa", strtotime(gmdate("Y-m-d h:i:sa", strtotime($metar["valid"]))));
      $localParse = date_parse($localtime);
      
      if($metar["tmpf"] != "M") {
        $this->getYear($localParse["year"])->getMonth($localParse["month"])->getDay($localParse["day"])->addTemp($metar["tmpf"]);
      }
      
      if($this->prevDate == "") {
        $this->prevDate = $localtime;
      } else {
        $hourDiff = $this->s_datediff("h", new DateTime($this->prevDate), new DateTime($localtime));
        $this->prevDate = $localtime;
        
        $isDay = (($localParse["hour"] > 8) && ($localParse["hour"] < 18));
        
        $isVeryWindy = (($metar["sknt"] > 10) || ($metar["gust"] > 5));
        
        $isWindy = (($metar["sknt"] > 5) || ($metar["gust"] > 0));
        
        $isVisible = ($metar["vsby"] > 5);
        
        $isClear = ($metar["skyc1"] == "CLR");
        
        $isLowCloud = false;
        
        if(!$isClear) {
          for($i = 1; $i <= 4; $i++) {
            if(($metar["skyc" . $i] == "BKN" || $metar["skyc" . $i] == "OVC") && ($metar["skyl" . $i] < 5000)) {
              $isLowCloud = true;
              break;
            }
          }
        }
        
        $this->getYear($localParse["year"])->addTime($isVeryWindy, $isVisible, $isLowCloud, $isDay, $isWindy, $isClear, $localParse["month"], $hourDiff);
      }
    }
    
	/*
	 * Process all data, checking each data value against the saved ones for the whole period,
	 * also determines which reason is most common for something being bad
	 * -Finally, convert the various "average" times into percentages
	 * -Step through arrays for best and worst months, and determine which is the top
	 * -Clear out the "years" array, to save resources
	 */
    function processData() {
      $numYears = count($this->years);
      
      foreach($this->years as $year) {
        if(!$year->processed) {
          $year->processYear();
        }
        
        if($year->highHigh > $this->highHigh) {
          $this->highHigh = $year->highHigh;
        }
        
        if($year->lowHigh < $this->lowHigh) {
          $this->lowHigh = $year->lowHigh;
        }
        
        if($year->highLow > $this->highLow) {
          $this->highLow = $year->highLow;
        }
        
        if($year->lowLow < $this->lowLow) {
          $this->lowLow = $year->lowLow;
        }
        
        $this->avgPerfectTime += $year->perfectTime;
        $this->avgDayVFRTime += $year->dayVFRTime;
        $this->avgVFRTime += $year->VFRTime;
        
        if(!array_key_exists($year->bestMonth["name"], $this->bestMonths)) {
          $this->bestMonths[$year->bestMonth["name"]] = 0;
        } else {
          $this->bestMonths[$year->bestMonth["name"]]++;
        }
        
        if(!array_key_exists($year->worstMonth["name"], $this->worstMonths)) {
          $this->worstMonths[$year->worstMonth["name"]] = 0;
        } else {
          $this->worstMonths[$year->worstMonth["name"]]++;
        }
        
        $this->reasons[$year->maxReason]++;
      }
      
      $maxReasonTime = -1;
      
      foreach($this->reasons as $reason => $maxReasonNum) {
        if($maxReasonNum > $maxReasonTime) {
          $maxReasonTime = $maxReasonNum;
          $this->maxReason = $reason;
        }
      }
      
      $this->avgPerfectTime /= ($numYears * 2920);
      $this->avgDayVFRTime /= ($numYears * 2920);
      $this->avgVFRTime /= ($numYears * 8760);
      
      $this->avgPerfectTime *= 100;
      $this->avgDayVFRTime *= 100;
      $this->avgVFRTime *= 100;
      
      $this->highHigh = round($this->highHigh);
      $this->lowHigh = round($this->lowHigh);
      $this->highLow = round($this->highLow);
      $this->lowLow = round($this->lowLow);
      
      $this->avgPerfectTime = round($this->avgPerfectTime);
      $this->avgDayVFRTime = round($this->avgDayVFRTime);
      $this->avgVFRTime = round($this->avgVFRTime);
      
      foreach($this->bestMonths as $month => $percent) {
        if(sizeof($this->bestMonth) == 0 || $this->bestMonth["percent"] < $percent) {
          $this->bestMonth["name"] = $month;
          $this->bestMonth["percent"] = $percent;
        }
      }
      
      $this->bestMonth["name"] = $this->getMonthName($this->bestMonth["name"]);
      
      foreach($this->worstMonths as $month => $percent) {
        if(sizeof($this->worstMonth) == 0 || $this->worstMonth["percent"] < $percent) {
          $this->worstMonth["name"] = $month;
          $this->worstMonth["percent"] = $percent;
        }
      }
      
      $this->worstMonth["name"] = $this->getMonthName($this->worstMonth["name"]);
      
      unset($this->years);
    }
    
    function output() {
      return round($this->highHigh) . " / " . round($this->lowHigh) . " | " . round($this->highLow) . " / " . round($this->lowLow) . "<br/>" . round($this->avgPerfectTime, 1) . "% | " . round($this->avgDayVFRTime, 1) . "% | " . round($this->avgVFRTime, 1) . "%<br/>";
    }
    
    function getMonthName($month) {
      switch($month) {
        case 1:
          return "January";
          break;
        case 2:
          return "February";
          break;
        case 3:
          return "March";
          break;
        case 4:
          return "April";
          break;
        case 5:
          return "May";
          break;
        case 6:
          return "June";
          break;
        case 7:
          return "July";
          break;
        case 8:
          return "August";
          break;
        case 9:
          return "September";
          break;
        case 10:
          return "October";
          break;
        case 11:
          return "November";
          break;
        case 12:
          return "December";
          break;
      }
    }
    
	// A datediff function, pulled from:
	// https://stackoverflow.com/questions/10778338/php-convert-date-interval-diff-to-decimal
    function s_datediff( $str_interval, $dt_menor, $dt_maior, $relative=false){

       if( is_string( $dt_menor)) $dt_menor = date_create( $dt_menor);
       if( is_string( $dt_maior)) $dt_maior = date_create( $dt_maior);

       $diff = date_diff( $dt_menor, $dt_maior, ! $relative);

       switch( $str_interval){
           case "y": 
               $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
           case "m":
               $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
               break;
           case "d":
               $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
               break;
           case "h": 
               $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
               break;
           case "i": 
               $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
               break;
           case "s": 
               $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
               break;
          }
       if( $diff->invert)
               return -1 * $total;
       else    return $total;
   }
  }
?>