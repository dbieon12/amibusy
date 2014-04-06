<?php
	require_once "top.php";
?>
<?php
	/////////////////////
	///  EVENT CLASS  ///
	/////////////////////

	class Event {
		var $myStartTime;
		var $myEndTime;
	}

	//////////////////
	///  CALENDAR  ///
	//////////////////

	// Declare calendar array with calendar URLs
	$calendarArray = array(
		"name_of_calendar" => "REPLACE WITH URL TO YOUR GOOGLE CALENDAR FEED",
		"name_of_calendar_2" => "REPLACE WITH URL TO YOUR GOOGLE CALENDAR FEED",
	);

	// Global variable declaration
	$eventArray = array();
	$counter = 0;

	// Fetch timeOfDay in Unix Timestamp format
	$timeOfDay = gettimeofday(true);
	$today = date("M j, Y", $timeOfDay);


	/////////////////
	//  FUNCTIONS  //
	/////////////////

	function getCalendarDataAsEvents() {
		// Declare global variables as local
		global $calendarArray, $eventArray, $timeOfDay, $counter;

		// Loop through each calendar
		foreach ($calendarArray as $calendar) {

			// Load the calendar XML file
			$calXML = simplexml_load_file($calendar);

			// Create an array of entries
			$events = $calXML->entry;

			// Check each event in this calendar
			foreach($events as $summary) {
				$str_orig = $summary->summary;
				$str = str_replace("When: ", "", $str_orig);
				$str = substr($str, 4);
				$wordCount = str_word_count($str, 0);
				$eventDate = getEventDate($str, 3);

				// Check if event is current (happening today)
				if ($eventDate <= $timeOfDay) {

					if ($wordCount == 7) {
						$startTime = getStartTime($str, 4);
						$startTime = strtotime($startTime);

						$endTime = getEndTime($str, $eventDate);
						$endTime = str_replace("UTC", "", $endTime);
						$endTime = str_replace("EDT", "", $endTime);
						$endTime = str_replace("EST", "", $endTime);
						$endTime = strtotime($endTime);

						// Add the event to the eventArray
						$anEvent = new Event();
						$anEvent->myStartTime = $startTime;
						$anEvent->myEndTime = $endTime;
						array_push($eventArray, $anEvent);
					}
					elseif ($wordCount == 9) {
						$startTime = getStartTime($str, 4);
						$startTime = strtotime($startTime);

						$endTime = getEndTime2($str);
						$endTime = str_replace("UTC", "", $endTime);
						$endTime = str_replace("EDT", "", $endTime);
						$endTime = str_replace("EST", "", $endTime);
						$endTime = strtotime($endTime);

						$anEvent = new Event();
						$anEvent->myStartTime = $startTime;
						$anEvent->myEndTime = $endTime;
						array_push($eventArray, $anEvent);
					}
				}
			}
		}
	}

	// Sort the Event Array by myEndTime value
	function sortEventArray($inEventArray) {
		$eventArray = $inEventArray;

		// Sort based on results of compareEventTimes function
		usort($eventArray, "compareEventTimes");
	}

	// Find the latest valid endTime, update endTime
	function findLatestActiveEventEndTime($inEventArray, $inTimeOfDay) {
		$eventArray = $inEventArray;
		$timeOfDay = $inTimeOfDay;
		$latestEndTime = 0;

		// Check for events currently in progress
		foreach ( $eventArray as $event ) {
			if ( ($event->myStartTime <= $timeOfDay) && ($event->myEndTime > $timeOfDay) ) {
				if ( $event->myEndTime > $latestEndTime ) {
					// If Event is current and event end time is later than the previous
					$latestEndTime = $event->myEndTime;

					// Check for later events
					$latestEndTime = checkForLaterEvents($eventArray, $timeOfDay, $latestEndTime);
				}

				// Remove in progress events from array
				unset($eventArray[$event]);
			}
		}
		return $latestEndTime;
	}

	// Check for events that start before the longest current event ends with a later end time
	function checkForLaterEvents($inEventArray, $inTimeOfDay, $inLatestEndTime) {
		$eventArray = $inEventArray;
		$timeOfDay = $inTimeOfDay;
		$latestEndTime = $inLatestEndTime;

		foreach( $eventArray as $event ) {
			if ( ($event->myStartTime <= $latestEndTime) && ($event->myEndTime > $latestEndTime) ) {
				$latestEndTime = $event->myEndTime;
				$latestEndTime = checkForLaterEvents($eventArray, $timeOfDay, $latestEndTime);
			}
		}
		return $latestEndTime;
	}


	///////////////////////////
	///  UTILITY FUNCTIONS  ///
	///////////////////////////

	// Return eventDate & year
	function getEventDate($string, $length) {
	   $words = explode(' ', $string);
	   if (count($words) > $length)
		   return implode(' ', array_slice($words, 0, $length));
	   else
		   return $string;
	}

	// Return the startTime of the event
	function getStartTime($string, $length) {
	   $words = explode(' ', $string);
	   if (count($words) > $length)
		   return implode(' ', array_slice($words, 0, $length));
	   else
		   return $string;
	}

	// Return the endTime of the event (two versions)
	function getEndTime($string, $eventDate) {
		$words = explode(' ', $string);
		$words = $words[5];
		$words = $eventDate . " " . $words;
		$words = str_replace("&nbsp;", "", $words);
		$words = str_replace("<br>", "", $words);
		return $words;
	}

	function getEndTime2($string) {
		$words = explode(' ', $string);
		$words = $words[6] . " " . $words[7] . " " . $words[8] . " " . $words[9];
		$words = str_replace("&nbsp;", "", $words);
		$words = str_replace("<br>", "", $words);
		return $words;
	}

	// Compare the event end times
	function compareEventTimes($evtA, $evtB) {
		if ( ($evtA->myEndTime) == ($evtB->myEndTime) ) {
			return 0;
		}
		return ( ($evtA->myEndTime) < ($evtB->myEndTime) ) ? -1 : 1;
	}

	// Generate output whether 'busy' or 'free'
	function busyOrFreeOutput($latestEventEndTime, $timeOfDay) {
		if ($latestEventEndTime >= 1) {
			echoTime($timeOfDay);
			echo '<div class="hero-unit yes">';
			echo "<h1 class='yes'>Yes, he is busy until " . date("g:i a", $latestEventEndTime) . ".</h1>";
		}
		else {
			echoTime($timeOfDay);
			echo '<div class="hero-unit no">';
			echo "<h1 class='no'>No, he is not busy.</h1>";
		}
	}

	// Output the current date/time
	function echoTime($timeOfDay) {
		echo "It is " . date("g:i A", $timeOfDay) . " on " . date("n/j/Y", $timeOfDay) . ".<br/>";
	}


	//////////////////////
	///  METHOD CALLS  ///
	//////////////////////

	getCalendarDataAsEvents();
	sortEventArray($eventArray);
	$latestEventEndTime = findLatestActiveEventEndTime($eventArray, $timeOfDay);
?>

<body>
	<div class="container">
	<?php
		// Call the method to generate the HTML output
		busyOrFreeOutput($latestEventEndTime, $timeOfDay);
	?>
	</div>
      <div class="row">
        <div class="span12">
          <h2>About this site</h2>
          <p>
          <ul>
            <li>You are seeing live information.</li>
            <li>Am I Busy? pulls data from multiple Google Calendars to determine whether there is a scheduled event at the current time.</li>
            <li>It then outputs whether I am busy or not.</li></li>
            <li>Future versions may allow input to query a specific time.</li>
          </ul>
          </p>
        </div>
      </div>
      <hr>
      <footer>
        <p>Am I Busy?</p>
      </footer>
    </div> <!-- /container -->

<?php
	require_once "footer.php";
?>