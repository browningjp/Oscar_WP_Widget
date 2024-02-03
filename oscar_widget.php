<?php
/*
Plugin Name: Oscar Upcoming Shows
Description: Widget to display upcoming shows, pulled from Oscar box office system
Version: 0.2
Author: Jonny Browning
*/

class oscar_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of widget
'oscar_widget',

// Widget name will appear in UI
__('Oscar Upcoming Shows', 'oscar_widget_domain'),

// Widget description
array( 'description' => __( 'Displays upcoming shows from Oscar box office system', 'oscar_widget_domain' ), )
);
}

// Show date/time comparison function
private static function compareShowDates($a, $b) {
	if ((string)$a['StartDate'] == (string)$b['StartDate']) {
		if ((string)$a['StartTime'] == (string)$b['StartTime']) {
			return 0;
		}
		$a_time = strtotime((string)$a['StartTime']);
		$b_time = strtotime((string)$b['StartTime']);
		return ($a_time < $b_time) ? -1 : 1;
	}
	$a_date = strtotime((string)$a['StartDate']);
	$b_date = strtotime((string)$b['StartDate']);

	return ($a_date < $b_date) ? -1 : 1;
}

// Widget front end
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
$feed_url = $instance['feed_url'];
$num_shows = $instance['num_shows']; // number of shows to display

// Fetch shows from Oscar
$response = wp_remote_get($feed_url);
$body = wp_remote_retrieve_body($response);

// convert result to XML object
$xml  = simplexml_load_string($body);

// Add performances to array
$performances = array();
foreach ($xml->Performance as $performance) {
	$performances[] = $performance;
}

// Sort shows by date ascending
usort($performances, array($this,'compareShowDates'));

// Add Programmes corresponding to the first performances to a new array
$programmeIDs = array();
$programmes = array();
foreach ($performances as $performance) {
	// If programme already appeared, delete this performance
	if (!in_array((string)$performance['ProgrammeID'], $programmeIDs)) {
		$programmeIDs[] = (string)$performance['ProgrammeID'];
		$programmes[] = $xml->xpath('//Programme[@ID="' . $performance['ProgrammeID'] . '"]')[0];
	}
}

// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];

// Display shows, if num_shows > 0
if ($num_shows > 0) {
	echo '<ul>';
	$i = 0;
	foreach ($programmes as $programme) {
	// foreach ($xml->Programme as $programme) {
		echo '<li><a href="' . $programme['BookingURL'] . '">' . $programme['ReportTitle'] . '</a></li>';
		if (++$i == $num_shows) break; // stop after num_shows shows
	}
	echo '</ul>';
}

echo $args['after_widget'];
}

// Widget Backend
public function form( $instance ) {

// Set title from saved value, or use default
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'oscar_widget_domain' );
}

// Set feed URL from saved value, or blank
if ( isset( $instance ['feed_url']) ) {
	$feed_url = $instance[ 'feed_url' ];
} else {
	$feed_url = __( '', 'oscar_widget_domain' );
}

// Set number of shows to display from saved value, or use default of 5
if ( isset( $instance[ 'num_shows' ] ) ) {
$num_shows = $instance[ 'num_shows' ];
}
else {
$num_shows = __( 5, 'oscar_widget_domain' );
}

// Widget admin form
?>
<p>

<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />

<label for="<?php echo $this->get_field_id( 'feed_url' ); ?>"><?php _e( 'Feed URL:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'feed_url' ); ?>" name="<?php echo $this->get_field_name( 'feed_url' ); ?>" type="text" value="<?php echo esc_attr( $feed_url ); ?>" />

<label for="<?php echo $this->get_field_id( 'num_shows' ); ?>"><?php _e( 'Number of shows to display:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'num_shows' ); ?>" name="<?php echo $this->get_field_name( 'num_shows' ); ?>" type="number" value="<?php echo esc_attr( $num_shows ); ?>" />

</p>
<?php
}

// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();

// If title and feed_URL not blank, strip_tags before saving
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['feed_url'] = ( ! empty( $new_instance['feed_url'] ) ) ? strip_tags( $new_instance['feed_url'] ) : '';

// Check new value of num_shows isset and > 0, otherwise default to 5
$instance['num_shows'] = ( isset( $new_instance['num_shows'] ) && $new_instance['num_shows'] >= 0) ? strip_tags( $new_instance['num_shows'] ) : 5;
return $instance;
}
} // Class oscar_widget ends here

// Register and load the widget
function osc_load_widget() {
	register_widget( 'oscar_widget' );
}
add_action( 'widgets_init', 'osc_load_widget' );

?>
