<html>
	<head>
		<title>Station Weather Analysis</title>
		<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
		<script>
			$(document).ready(function()
			{
        $("#getweather").click(function() {
          var station = $("#stationid").val().toUpperCase();
          
          $("#outputdiv").html("<img id=\"loader\" src=\"..\\..\\loading_spinner.gif\"/>");
          
          if(station.length == 4 && station.startsWith("K")) {
            station = station.substring(1);
          }
          
          $.ajax({
            type: "GET",
            url: "weather.php?station=" + station,
            dataType: "html",
            success: function(html) {
              $("#outputdiv").html(html);
            }
          });
        });
      });
    </script>
  </head>
  <body>
    <div id="controldiv">
      IATA or ICAO Station ID: <input type="text" id="stationid"/><br/>
      <input type="button" id="getweather" value="Get Weather"/><br/><br/>
      This system collects the last ten years worth of data from https://mesonet.agron.iastate.edu/request/download.phtml, collates it, and averages it. The averages for the highest/lowest highs and lows are by year. The Quality Percentages are as follows:<br/>
      <ul>
        <li>VFR: Wind less than 10 knots, with gusts less than 5 knots, visibility greater than 5 miles, and a ceiling of greater than 5,000 feet.</li>
        <li>Day VFR: As above, but during the day.</li>
        <li>Perfect: Wind less than 5 knots, with no gusts, and visiblity greater than 5 miles, and clear skies during the day.</li>
        <li>Best and Worst Month: Based on the total percentage of Day VFR time per month, what is the most common best month.</li>
        <li>Reason: Most common reason for the weather to not be perfect, e.g. low cloud, visibility, and so on.</li>
      </ul>
      The percentage is based on the possible times, so VFR is the percentage time for the whole year, while day VFR and perfect are only for times during the 8 AM to 6 PM local time window (very approximately daylight hours). Day VFR and perfect are also additive, so, by definition, a perfect day is also a day VFR day.<br/>
      Note also that this system caches requests as they come in, but if the data is not cached it can take up to ten minutes to complete the analysis. Please be patient.<br/><br/>
    </div>
    <div id="outputdiv"></div>
  </body>
</html>