<?php

/*
 * Plugin Name: Ics Ritten
 * Plugin URI: https://le.pirlou.it/wp_plugins
 * Version : 3.0
 * Requires at least: 5.3.0
 * Author: Benoît de Biolley
 * Author URI: https://le.pirlou.it/
 */

 // source : https://gist.github.com/Jany-M/af50d5c4a0eec2692734d76383ed4dd8

global $feedname;
$feedname = 'calendar-ritten';

// Add a custom endpoint "calendar"
function add_calendar_feed(){
    global $feedname;
	add_feed($feedname, 'export_ics');
}
add_action('init', 'add_calendar_feed');



/**
 * Activate the plugin.
 */
function pluginprefix_activate() { 
	// Trigger our function that registers the custom endpoint "calendar"
	add_calendar_feed(); 
	// Clear the permalinks after the endpoint "calendar" has been registered.
	flush_rewrite_rules(); 
}
register_activation_hook( __FILE__, 'pluginprefix_activate' );

/**
 * Deactivation hook.
 */
function pluginprefix_deactivate() {
	// Unregister the endpoint "calendar", so the rules are no longer in memory.
    global $feedname;
    $hook = 'do_feed_' . $feedname;

	// Remove default function hook.
	remove_action( $hook, $hook );
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pluginprefix_deactivate' );

/*  
	For a better understanding of ics requirements and time formats
    please check https://gist.github.com/jakebellacera/635416
*/

// UTILS

// Check if string is a timestamp
function isValidTimeStamp($timestamp) {
    //if($timestamp == '') return;
    return ((string) (int) $timestamp === $timestamp) 
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}

// Escapes a string of characters
function escapeString($string) {
	return preg_replace('/([\,;])/','\\\$1', $string);
}

// Shorten a string to desidered characters lenght - eg. shorter_version($string, 100);
function shorter_version($string, $lenght) {
if (strlen($string) >= $lenght) {
		return substr($string, 0, $lenght);
	} else {
		return $string;
	}
}





// Calendar function
function export_ics(){?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php echo get_bloginfo('name'); ?> //NONSGML Events //EN
CALSCALE:GREGORIAN
X-WR-CALNAME:<?php echo get_bloginfo('name').$eol;?>
X-WR-TIMEZONE:Europe/Brussels
<?php 
     //Give the iCal export a filename
     $filename = urlencode( $title.'-ical-' . date('Y-m-d') . '.ics' );
     $eol = "\r\n";
     //Collect output
     ob_start();
      // Set the correct headers for this file
        // header("Content-Description: File Transfer");
        // header("Content-Disposition: attachment; filename=".$filename);
        header('Content-type: text/calendar; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");

$groep = $_GET['groep'];
global $wpdb;
$sql = "SELECT zondagritten.`id`,zondagritten.`omloop`, zondagritten.`vertrekuur`, zondagritten.`baankapitein`, zondagritten.`medewerker`, zondagritten.`afstand`, zondagritten.`datum`  FROM zondagritten WHERE zondagritten.`ploeg` like %s";
$preparedSatement = $wpdb->prepare( $sql, $groep );
$results = $wpdb->get_results($preparedSatement);
foreach ( $results as $rit ) {
 	// The rest is the same for any version
	$timestamp = date_i18n('Ymd\THis\Z',time(), true);
	$uid = "rit-".$rit->id;
	// $created_date = get_post_time('Ymd\THis\Z', true, $uid );
	// $organiser = get_bloginfo('name'); // EDIT THIS WITH YOUR OWN VALUE
    $address = 'Schaliestraat 2, 1602 Sint-Pieters-Leeuw'; // EDIT THIS WITH YOUR OWN VALUE
    // $url = get_the_permalink();
    // $summary = get_the_summary($rit);
    //  $content = html_entity_decode(trim(preg_replace('/\s\s+/', ' ', get_the_content()))); // removes newlines and double spaces
     $title = "LWT - ".$ploeg." rit: ".$rit->omloop;


// The below ics structure MUST NOT have spaces before each line
// Credit for the .ics structure goes to https://gist.github.com/jakebellacera/635416
?>

BEGIN:VEVENT
CREATED:<?php echo $created_date.$eol;?>
UID:<?php echo $uid.$eol;?>
DTSTART;VALUE=DATE:<?php echo $rit->datum.$eol; ?>
LOCATION:<?php echo escapeString($address).$eol; ?>
END:VEVENT
<?php
// end foreach
}
?>
END:VCALENDAR
<?php
        //Collect output and echo
        $eventsical = ob_get_contents();
        ob_end_clean();
        echo $eventsical;
        exit();


}*/

add_shortcode('calendar-ritten', 'rittenIcal_shortcode');
function rittenIcal_shortcode( $atts = [], $content = null) {
    // do something to $content
    // always return
    ?><label for="iCalUrl">iCal Url:</label><input id="iCalUrl" type="text" readonly value="<?php global $feedname;echo get_feed_link($feedname); ?>?group=tempo"/> <?php
    return ;
}


?>


