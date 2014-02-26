<?php
/**
  * Configuration class.
  *
  * @author Jared Howland <sirsi@jaredhowland.com>
  * @version 2014-02-22
  * @since 2014-02-22
  *
  */

class config {
  // App settings
  const DEVELOPMENT      = TRUE; // Changes the app behavior (error reporting, template caching, which database to use, etc.)
  const BAD_FAULT_JSON   = TRUE; // Sirsi returns invalid JSON for errors—keep true until they fix the bug
  const URL              = 'http://yourdomain.com/lettering';
  const TIME_ZONE        = 'America/Denver'; // Needed for date calculations in PHP
  const CLIENT_ID        = ''; // clientID used for Sirsi API calls
  const API_BASE_URL     = ''; // Base URL for API calls to your Sirsi instance
  const API_SEARCH_URL   = '/rest/standard/lookupTitleInfo?';


/****************************************************************************/
/*                       DO NOT EDIT BELOW THIS LINE                        */
/****************************************************************************/

  /**
    * Determines type of error reporting
    * Based on state of DEVELOPMENT constant
    *
    * @param null
    * @return string Type of error reporting
    */
  public static function set_error_reporting() {
    if(self::DEVELOPMENT) {
      // error_reporting(E_ALL);
      ini_set('error_reporting', E_ALL^E_NOTICE);
      ini_set('display_errors', 1);
    } else {
      error_reporting(0);
    }
  }
  
} // End class


/****************************************/
/* Miscellaneous configuration settings */
/****************************************/

// Autoload classes
// Must be in the 'classes' directory and prefixed with 'class.'
function __autoload( $class ) {
  require_once( __DIR__ . '/classes/class.' . $class . '.php' );
}

// Set default time zone
date_default_timezone_set( config::TIME_ZONE );

// Set error reporting
config::set_error_reporting();

?>
