<?php
/**
  * Displays form for submitting list of barcodes
  *
  * @author Jared Howland <sirsi@jaredhowland.com>
  * @version 2014-04-15
  * @since 2014-02-22
  *
  */
require_once 'config.php';

// Get all barcodes entered into form and put them into an array
$item_ids = explode("\n", $_REQUEST['barcodes']); // Access is limited to BYU's IP range so I am carelessly not cleaning the input before using it

// Get data for each barcode using SIRSI API calls
$data = get_data($item_ids);

// Format the data to only include necessary information for our application
$clean_data = format_data($data);

// Give output to template for display to user
$html = array('title' => 'Home', 'html' => $clean_data);
template::display('report.tmpl', $html);


/***************************
 *        FUNCTIONS        *
 ***************************/

/**
 * Constructs API call(s) and returns data for all barcodes
 *
 * @param array Array with all barcodes entered into form
 * @return array Array with just relevant data from API calls included
 **/
function get_data($item_ids) {
  // Loop through all barcodes
  // Sirsi technically allows aggregating multiple calls but does not return all necessary information for each returned result for this to be useful for us
  foreach($item_ids AS $item_id) {
    $item_id = trim($item_id);
    // Create search parameters for Sirsi API
    $parameters = 'marcEntryFilter=ALL&includeItemInfo=true&includeCallNumberSummary=true&json=true&itemID=' . $item_id;
    // Construct URL for Sirsi API call
    $url = config::API_BASE_URL . config::API_SEARCH_URL . $parameters . '&clientID=' . config::CLIENT_ID;
    // Get the results of the API call
    $data = get_json($url);
    if($data == '') {
      echo '<p>There was a problem connecting to the server. Please try again later.</p>';
      die();
    }
    // Sirsi spits out bad JSON if there is an error
    // Correct the invalid JSON if there is an error
    if(config::BAD_FAULT_JSON) {
      $data = str_replace('faultResponse', '"faultResponse"', $data);
    }
    // Convert the object to a PHP array
    $data = json_decode($data, true);
    // Store all API call results in one array
    $all_data[] = array('item_id' => $item_id, 'data' => $data);
  }
  return $all_data;
}


/**
 * Extracts relevant information from results of all API calls
 *
 * @param array Array with data from all API calls
 * @return array Array with just relevant data from API calls included
 **/
function format_data($data) {
  foreach($data AS $record) {
    $item_id = $record['item_id'];
    $title_info = $record['data']['TitleInfo'][0];
    // If no result found
    if(empty($title_info)) {
      $all_records[] = array('error' => 'No item found for ' . $item_id . '.');
    // If result is found
    } else {
      $title_id = $title_info['titleID'];
      // Home location and call number
      foreach($title_info['CallInfo'] AS $CallInfo) {
        $record_call_number = $CallInfo['callNumber'];
        foreach($CallInfo['ItemInfo'] AS $ItemInfo) {
          $record_item_id = $ItemInfo['itemID'];
          if($record_item_id == $item_id) {
            $call_number = $record_call_number;
            $home_location = $ItemInfo['homeLocationID'];
          }
          // Indicate whether the item is a SERIAL because base call number
          // does not include subfield z (can remove once SIRSI updates APIs
          // to match documentation)
          if($ItemInfo['itemTypeID'] == 'SERIAL') {
            $call_number = $record_call_number . '<br/><span class="warning">(SERIAL)</span>';
          } // Non-BOOK items have a different location for call number data
          elseif($ItemInfo['itemTypeID'] != 'BOOK') {
            $call_number = $record_call_number;
          }
        }
      }
      // MARC record data
      $marc = $record['data']['TitleInfo'][0]['BibliographicInfo']['MarcEntryInfo'];
      foreach($marc AS $field) {
        $label           = $field['label'];
        $entryID         = $field['entryID'];
        $indicators      = $field['indicators'];
        $text            = $field['text'];
        $unformattedText = $field['unformattedText'];
        if($entryID == '001') { $control_number = $text; }

        if($entryID == '020') { $isbn = $text; }
        if($entryID == '100') { $author = $text; }
        if($entryID == '245') { $title = $text; }
        // Vernacular title
        if($entryID == '880' && strpos($unformattedText, '|6245-01|') !== false) { $linked_title = $text; }
        if($entryID == '260') {
          $publisher = $text;
          $pub_array = explode('|c', $unformattedText);
          $pub_date  = trim($pub_array[1], '-. ');
        }

      }

      $all_records[] = array('item_id' => $item_id, 'title_id' => $title_id, 'home_location' => $home_location, 'call_number' => $call_number, 'control_number' => $control_number, 'isbn' => $isbn, 'author' => $author, 'title' => $title, 'linked_title' => $linked_title, 'pub_date' => $pub_date, 'publisher' => $publisher);

      // Reset all variables for next item being searched
      $item_id        = null;
      $title_id       = null;
      $home_location  = null;
      $call_number    = null;
      $control_number = null;
      $isbn           = null;
      $author         = null;
      $title          = null;
      $linked_title   = null;
      $pub_date       = null;
      $publisher      = null;
    }
  }
  return $all_records;
}


/**
 * Uses CURL to grab the JSON from SIRSI
 * From http://25labs.com/alternative-for-file_get_contents-using-curl/
 *
 * @param string URL of API call
 * @return string Data returned by API call
 **/
function get_json($url) {
  $curl = curl_init();
  // $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.117 Safari/537.36';
  $userAgent = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)';

  curl_setopt($curl, CURLOPT_URL, $url); //The URL to fetch. This can also be set when initializing a session with curl_init().
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE); //TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 150); //The number of seconds to wait while trying to connect.
  curl_setopt($curl, CURLOPT_USERAGENT, $userAgent); //The contents of the "User-Agent: " header to be used in a HTTP request.
  curl_setopt($curl, CURLOPT_FAILONERROR, TRUE); //To fail silently if the HTTP code returned is greater than or equal to 400.
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE); //To follow any "Location: " header that the server sends as part of the HTTP header.
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); //Do not verify peer’s certificate
  curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE); //Force new connection rather than using cache
  curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE); //To automatically set the Referer: field in requests where it follows a Location: redirect.
  curl_setopt($curl, CURLOPT_TIMEOUT, 10); //The maximum number of seconds to allow cURL functions to execute.

  // If not production, give verbose feedback on curl problems
  if(config::DEVELOPMENT) {
    curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
    $verbose = fopen('php://temp', 'rw+');
    curl_setopt($curl, CURLOPT_STDERR, $verbose);
  }

  // Execute query
  $contents = curl_exec($curl);

  // Report errors if they occur
  curl_error_report($curl, $verbose);

  curl_close($curl);
  return $contents;
}

/**
 * Sends CURL error message to user when needed (and only when in development)
 * From http://25labs.com/alternative-for-file_get_contents-using-curl/
 *
 * @param mixed
 *        string  If there is a result when making CURL call, will return data
 *        boolean If there is no result when making CURL call, will return FALSE
 * @return null If error exists, prints error directly to user and kills everything
 *              Otherwise, it does not return anything
 **/
function curl_error_report($curl, $verbose = null) {
  if(curl_errno($curl)) {
    if(config::DEVELOPMENT) {
      rewind($verbose);
      $verboseLog = stream_get_contents($verbose);
      echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
      $curlVersion = curl_version();
      extract(curl_getinfo($curl));
      $metrics = <<<EOD
    URL: $url
   Code: $http_code ($redirect_count redirect(s) in $redirect_time secs)
Content: $content_type Size: $download_content_length (Own: $size_download) Filetime: $filetime
   Time: $total_time Start @ $starttransfer_time (DNS: $namelookup_time Connect: $connect_time Request: $pretransfer_time)
  Speed: Down: $speed_download (avg.) Up: $speed_upload (avg.)
   Curl: v{$curlVersion['version']}
EOD;
      echo '<pre>' . $metrics . '</pre>';
    }
    echo 'cURL Error: ' . curl_errno($curl) . ': ' . curl_error($curl) . '<br/>';
    curl_close($curl);
    die();
  }
  return;
}
