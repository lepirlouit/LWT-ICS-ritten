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
function export_ics(){
    $eol = "\r\n";
    $groep = $_GET['groep'];
    
    echo "BEGIN:VCALENDAR".$eol;
    echo "VERSION:2.0".$eol;
    echo "PRODID:-//".get_bloginfo('name')."//NONSGML Events //EN".$eol;
    echo "CALSCALE:GREGORIAN".$eol;
    echo "X-WR-CALNAME:".get_bloginfo('name')." - ".$groep.$eol;
    echo "X-WR-TIMEZONE:Europe/Brussels".$eol;
     //Collect output
     ob_start();
      // Set the correct headers for this file
        // header("Content-Description: File Transfer");
        // header("Content-Disposition: attachment; filename=".$filename);
        header('Content-type: text/calendar; charset=utf-8');
        header("Pragma: no-cache");
        header("Expires: 0");

global $wpdb;
$sql = "SELECT zondagritten.`id`,zondagritten.`omloop`, zondagritten.`vertrekuur`, zondagritten.`baankapitein`, zondagritten.`medewerker`, zondagritten.`volgwagen`, zondagritten.`afstand`, zondagritten.`datum`  FROM zondagritten WHERE zondagritten.`ploeg` like %s";
$preparedSatement = $wpdb->prepare( $sql, $groep );
$results = $wpdb->get_results($preparedSatement);
date_default_timezone_set('Europe/Brussels');
foreach ( $results as $rit ) {
    $title = "LWT - ".$groep." rit ".$rit->omloop;
 	// The rest is the same for any version
	$timestamp = date_i18n('Ymd\THis\Z',time(), true);
	$uid = "rit-".$rit->id;
	// $created_date = get_post_time('Ymd\THis\Z', true, $uid );
	$organiser = "Leeuwse Wieler Toeristen"; // EDIT THIS WITH YOUR OWN VALUE
    $address = 'Schaliestraat 2, 1602 Sint-Pieters-Leeuw';
    $url = "https://leeuwsewielertoeristen.be/lwt1/";
    $content = "<span><br>";
    if (!empty($rit->afstand)){
	    $content .= "<b>Afstand</b>: ".$rit->afstand."km<br>";
    }
    if (!empty($rit->baankapitein)) {
	    $content .= "<b>Baankapitein</b>: ".$rit->baankapitein."<br>";
    }
    if (!empty($rit->medewerker)) {
	    $content .= "<b>Medewerker</b>: ".$rit->medewerker."<br>";
    }
    if (!empty($rit->volgwagen)) {
	    $content .= "<b>Volgwagen</b>: ".$rit->volgwagen."<br>";
    }
    $content .= "</span>";


// The below ics structure MUST NOT have spaces before each line
    // Credit for the .ics structure goes to https://gist.github.com/jakebellacera/635416
    $parsed=date_parse($rit->datum." ".$rit->vertrekuur." CET");
    $start_date = mktime(
        $parsed['hour'], 
        $parsed['minute'], 
        $parsed['second'], 
        $parsed['month'], 
        $parsed['day'], 
        $parsed['year']
    );
    $end_date = mktime(
	$parsed['hour']+4,
        $parsed['minute'],
        $parsed['second'],
        $parsed['month'],
        $parsed['day'],
        $parsed['year']
);
    $end_date = wp_date("Ymd\THis\Z", $end_date, new DateTimeZone("UTC"));
    $start_date = wp_date("Ymd\THis\Z", $start_date, new DateTimeZone("UTC"));
?>
BEGIN:VEVENT<?php echo $eol;?>
UID:<?php echo $uid.$eol;?>
DTSTAMP:<?php echo $timestamp.$eol; ?>
DTSTART:<?php echo  $start_date.$eol; ?>
DTEND:<?php echo $end_date.$eol; ?>
<?php
    $line = "DESCRIPTION:".$content;
    if (strlen($line)> 75){
	        $firstChunk = substr($line, 0, 75);
    $least = substr($line, 75);
    preg_match_all('/.{1,74}/', $least, $matches);

    echo $firstChunk.$eol;
    foreach ($matches[0] as $chunk) {
        echo " " . $chunk.$eol;
    }
} else {
    echo $line.$eol;
}
?>
SUMMARY:<?php echo escapeString($title).$eol; ?>
URL;VALUE=URI:<?php echo escapeString($url).$eol; ?>
LOCATION:<?php echo escapeString($address).$eol; ?>
<?php
echo "END:VEVENT".$eol;
// end foreach
}
echo "END:VCALENDAR".$eol;

        //Collect output and echo
        echo ob_get_clean();
        exit();
}

add_shortcode('calendar-ritten', 'rittenIcal_shortcode');
function rittenIcal_shortcode( $atts = [], $content = null) {
	$atts = shortcode_atts( array(
		'groep' => 'something',
	), $atts );
	ob_start();

    // do something to $content
    // always return
	?><div><label for="iCalUrl">iCal Url: </label><input id="iCalUrl" type="text" readonly size="65" value="<?php global $feedname;echo get_feed_link($feedname)."?groep=".esc_html( $atts['groep'] ); ?>" /></div><?php
    return ob_get_clean();
}


?>