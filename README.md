# AviationWeather
Processes the weather data from:
https://mesonet.agron.iastate.edu/

Going back 10 years, processes all available data for a given station, finding the averages for the highest/lowest highs and lows, by year, as well as the percentages of various kinds of weather:
-VFR: Wind less than 10 knots, with gusts less than 5 knots, visibility greater than 5 miles, and a ceiling of greater than 5,000 feet.
-Day VFR: As above, but during the day.
-Perfect: Wind less than 5 knots, with no gusts, and visiblity greater than 5 miles, and clear skies during the day.
-Best and Worst Month: Based on the total percentage of Day VFR time per month, what is the most common best month.
-Reason: Most common reason for the weather to not be perfect, e.g. low cloud, visibility, and so on.

The percentage is based on the possible times, so VFR is the percentage time for the whole year, while day VFR and perfect are only for times during the 8 AM to 6 PM local time window (very approximately daylight hours). Day VFR and perfect are also additive, so, by definition, a perfect day is also a day VFR day

At one point, this was hosted on a website I no longer have, and cached the results of the data for quicker access.
