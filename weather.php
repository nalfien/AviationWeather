<?php
  require 'data.php';
  require 'year.php';
  require 'month.php';
  require 'day.php';
  
  if(isset($_GET["station"]) && (strlen($_GET["station"]) == 3 || strlen($_GET["station"]) == 4) && ctype_alnum($_GET["station"])) {
    $station = strtoupper($_GET["station"]);
  } else {
    $station = "ILG";
  }
  
  // Put your database information here.
  $mysqli = new mysqli();
  
  if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());
		exit();
	}
  
  $query = "SELECT * FROM cire.weather WHERE station = '" . $station . "' AND checked > '" . (new DateTime())->sub(new DateInterval('P1Y'))->format('Y-m-d') . "';";
  
  $result = $mysqli->query($query);
  
  if($result->num_rows != 0) {
    $dataObj = new data;
    $dataObj->getFromDB($result->fetch_assoc());
  } else {
    $now = new DateTime();
    $then = (new DateTime())->sub(new DateInterval("P10Y"));
    
	/*
	 * Go to the URL below, with the properly formatted query string. Essentially, take "station" from the javascript submittal, and grab all the data for the last decade.
	 */
    $url = "https://mesonet.agron.iastate.edu/cgi-bin/request/asos.py?station=" . $station . "&data=all&year1=" . $then->format("Y") . "&month1=" . $then->format("m") . "&day1=" . $then->format("d") . "&year2=" . $now->format("Y") . "&month2=" . $now->format("m") . "&day2=" . $now->format("d") . "&tz=Etc%2FUTC&format=tdf&latlon=no&direct=no&report_type=1&report_type=2";
    
    $data = fopen($url, "r");
    
    $weatherArr = array();
    $header = array();
    $dateArr = array();
    
    $i = 0;

    date_default_timezone_set("America/New_York");
    
    $prevDate = "";
    
    $dataObj = new data;
    
	/*
	 * Process all the data:
	 * -Some lines don't have proper data, so skip them.
	 * -Find the header data, and save that to an array, so we can have an object-indexed array instead of just numbers.
	 * -If it has real data, put that into the main array for better processing later.
	 */
    if($data) {
      $hasData = false;
      while(($metar = fgets($data)) !== false) {
        if(!startsWith($metar, "#DEBUG") && strpos($metar, "\t") !== false) {
          $metar = explode("\t", $metar);
          
          if($metar[0] == "station") {
            foreach($metar as $item) {
              array_push($header, trim($item));
            }
          } else {
            $hasData = true;
            $weatherArr = array();
            $j = 0;
            
            foreach($metar as $item) {
              $weatherArr[$header[$j]] = $item;
              $j++;
            }
            
            $dataObj->procLine($weatherArr);
          }
        }
      }
    }
    
	/*
	 * If we got data earlier: 
	 * -Send it to the main processing function
	 * -Generate a query to save the processed data to the database
	 */
    if($hasData) {
      $dataObj->processData();
      
      $now = new DateTime();
      
      $query = "INSERT INTO cire.weather VALUES ('" . $station . "','" . $now->format("Y-m-d") . "'," . $dataObj->highHigh . "," . $dataObj->highLow . "," . $dataObj->lowHigh . "," . $dataObj->lowLow . "," . $dataObj->avgPerfectTime . "," . $dataObj->avgDayVFRTime . "," . $dataObj->avgVFRTime . ", '" . $dataObj->bestMonth["name"] . "', '" . $dataObj->worstMonth["name"] . "','" . $dataObj->maxReason . "') ON DUPLICATE KEY UPDATE checked = '" . $now->format("Y-m-d") . "', highhigh = " . $dataObj->highHigh . ", highlow = " . $dataObj->highLow . ", lowhigh = " . $dataObj->lowHigh . ", lowlow = " . $dataObj->lowLow . ", perfect = " . $dataObj->avgPerfectTime . ", dayvfr = " . $dataObj->avgDayVFRTime . ", vfr = " . $dataObj->avgVFRTime . ", bestmonth = '" . $dataObj->bestMonth["name"] . "', worstmonth = '" . $dataObj->worstMonth["name"] . "', reason = '" . $dataObj->maxReason . "';";
      
      if(!$mysqli->query($query)) {
        echo $mysqli->error . "<br/>";
        echo $query . "<br/>";
      }
    } else {
      echo "No data found.";
      return;
    }
  }
  
  $mysqli->close();
  
  /*
   * Output the data as a plain HTML table.
   */
  echo "<table border=1><tr><td>Station</td><td colspan=2>" . $station . "</td><tr><td rowspan=2>Average High</td><td>Highest</td><td>" . $dataObj->highHigh . "</td></tr><tr><td>Lowest</td><td>" . $dataObj->lowHigh . "</td></tr><tr><td rowspan=2>Average Low</td><td>Highest</td><td>" . $dataObj->highLow . "</td></tr><td>Lowest</td><td>" . $dataObj->lowLow . "</td></tr><tr><td rowspan=5>Quality Percentage</td><td>Perfect</td><td>" . $dataObj->avgPerfectTime . "%</td></tr><tr><td>Day VFR</td><td>" . $dataObj->avgDayVFRTime . "%</td></tr><tr><td>VFR</td><td>" . $dataObj->avgVFRTime . "%</td></tr><tr><td>Best Month</td><td>" . $dataObj->bestMonth["name"] . "</td></tr><td>Worst Month</td><td>" . $dataObj->worstMonth["name"] . "</td></tr><tr><td>Reason</td><td colspan=2>" . preg_replace("/([a-z0-9])([A-Z])/", "$1 $2", $dataObj->maxReason) . "</td></tr>";
  
  function startsWith($string, $query) {
    return (substr($string, 0, strlen($query)) === $query);
  }
?>