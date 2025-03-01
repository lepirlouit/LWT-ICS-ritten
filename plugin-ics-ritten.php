<?php

/*
 * Plugin Name: Ics Ritten
 * Version : 1.0
 * Requires at least: 5.3.0
 */

 // source : https://gist.github.com/Jany-M/af50d5c4a0eec2692734d76383ed4dd8


// Add a custom endpoint "calendar"
function add_calendar_feed(){
	add_feed('calendar', 'export_ics');
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
    $hook = 'do_feed_' . 'calendar';

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

    // Query the event
    $the_event = new WP_Query(array(
        'p' => $_REQUEST['id'],
        'post_type' => 'any',
    ));
    
    if($the_event->have_posts()) :
        
        while($the_event->have_posts()) : $the_event->the_post();
	
		// If your version of WP < 5.3.0 use the code below

        /*  The correct date format, for ALL dates is date_i18n('Ymd\THis\Z',time(), true)
            So if your date is not in this format, use that function    */
	
        $start_date = date_i18n("Ymd\THis\Z", get_post_meta( get_the_ID(), 'custom-field-of-start-date', true )); // EDIT THIS WITH YOUR OWN VALUE
        $end_date = date_i18n("Ymd\THis\Z", get_post_meta( get_the_ID(), 'custom-field-of-end-date', true )); // EDIT THIS WITH YOUR OWN VALUE
        $deadline = date_i18n("Ymd\THis\Z", get_post_meta( get_the_ID(), 'custom-field-of-deadline-date', true )); // EDIT THIS WITH YOUR OWN VALUE
        
		// Otherwise, if your version of WP >= 5.3.0 use this code
		
		$start_date = get_post_meta( get_the_ID(), 'custom-field-of-start-date', true ); // EDIT THIS WITH YOUR OWN VALUE
        $end_date = get_post_meta( get_the_ID(), 'custom-field-of-end-date', true ); // EDIT THIS WITH YOUR OWN VALUE
        if($start_date != '' && !isValidTimeStamp($start_date)) {
            $start_date = strtotime($start_date);
			$start_date = wp_date("Ymd\THis", $start_date);
        }
        if($end_date != '' && !isValidTimeStamp($end_date)) {
            $end_date = strtotime($end_date);
			$end_date = wp_date('Ymd\THis', $end_date);
		} else {
        	$end_date = wp_date("Ymd\THis", $start_date + (1 * 60 * 60)); // 1 hour after
        }    
		$deadline = date_i18n("Ymd\THis\Z", get_post_meta( get_the_ID(), 'custom-field-of-deadline-date', true )); // EDIT THIS WITH YOUR OWN VALUE
		
		// The rest is the same for any version
		$timestamp = date_i18n('Ymd\THis\Z',time(), true);
		$uid = get_the_ID();
		$created_date = get_post_time('Ymd\THis\Z', true, $uid );
		$organiser = get_bloginfo('name'); // EDIT THIS WITH YOUR OWN VALUE
        $address = ''; // EDIT THIS WITH YOUR OWN VALUE
        $url = get_the_permalink();
        $summary = get_the_excerpt();
        $content = html_entity_decode(trim(preg_replace('/\s\s+/', ' ', get_the_content()))); // removes newlines and double spaces
        $title = html_entity_decode(get_the_title());

        //Give the iCal export a filename
        $filename = urlencode( $title.'-ical-' . date('Y-m-d') . '.ics' );
        $eol = "\r\n";

        //Collect output
        ob_start();

        // Set the correct headers for this file
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=".$filename);
        header('Content-type: text/calendar; charset=utf-8');
        header("Pragma: 0");
        header("Expires: 0");

// The below ics structure MUST NOT have spaces before each line
// Credit for the .ics structure goes to https://gist.github.com/jakebellacera/635416
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//<?php echo get_bloginfo('name'); ?> //NONSGML Events //EN
CALSCALE:GREGORIAN
X-WR-CALNAME:<?php echo get_bloginfo('name').$eol;?>
BEGIN:VEVENT
CREATED:<?php echo $created_date.$eol;?>
UID:<?php echo $uid.$eol;?>
DTEND;VALUE=DATE:<?php echo $end_date.$eol; ?>
DTSTART;VALUE=DATE:<?php echo $start_date.$eol; ?>
DTSTAMP:<?php echo $timestamp.$eol; ?>
LOCATION:<?php echo escapeString($address).$eol; ?>
DESCRIPTION:<?php echo $content.$eol; ?>
SUMMARY:<?php echo $title.$eol; ?>
ORGANIZER:<?php echo escapeString($organiser).$eol;?>
URL;VALUE=URI:<?php echo escapeString($url).$eol; ?>
TRANSP:OPAQUE
BEGIN:VALARM
ACTION:DISPLAY
TRIGGER;VALUE=DATE-TIME:<?php echo $deadline.$eol; ?>
DESCRIPTION:Reminder for <?php echo escapeString(get_the_title()); echo $eol; ?>
END:VALARM
END:VEVENT
<?php
        endwhile;
?>
END:VCALENDAR
<?php
        //Collect output and echo
        $eventsical = ob_get_contents();
        ob_end_clean();
        echo $eventsical;
        exit();

    endif;

}
// USAGE in TEMPLATE

// <a href="<?php echo get_feed_link('calendar'); ?>?id=<?php echo get_the_ID(); ?>"> Download the ics/ical </a>
?>


