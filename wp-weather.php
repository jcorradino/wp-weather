<?php
/*
Plugin Name: WP-Weather
Description: Display current weather conditions with Weather Underground and IPinfoDB
Version: 1.0
Author: Jason Corradino
Author URI: http://imyourdeveloper.com
License: GPL2

Copyright 2012  Jason Corradino  (email : Jason@ididntbreak.it)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include_once("wp-weather-widget.php");

class WP_Weather_Admin {
	
	/**
	 * Initializes WP Weather Admin Setup
	 *
	 * @author Jason Corradino
	 *
	 */
	function init() {
		add_action('admin_init', array(__CLASS__, "plugin_init"));
		add_action('admin_menu', array(__CLASS__, "setup_pages"));
	}
	
	/**
	 * Initializes the plugin settings pages and sets up fields on admin_init
	 *
	 * @author Jason Corradino
	 *
	 */
	function plugin_init() {
		if ($_GET['page'] == 'wp_weather') {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_style('thickbox');
			wp_register_script('wp-weather-script', plugin_dir_path( __FILE__ ).'/wp-weather/assets/wp-weather.js', array('jquery','media-upload','thickbox'));
			wp_enqueue_script('wp-weather-script');
			wp_register_style( 'wp-weather-sprite-template', plugin_dir_path( __FILE__ ).'/wp-weather/assets/wp-weather-sprite-template.css');
			wp_enqueue_style( 'wp-weather-sprite-template' );
			wp_register_style( 'wp-weather-admin-style', plugin_dir_path( __FILE__ ).'/wp-weather/assets/wp-weather-admin.css');
			wp_enqueue_style( 'wp-weather-admin-style' );
		};
		register_setting( 'wp_weather_options', 'wp_weather_options', array(__CLASS__, "validate_fields"));
		add_settings_section('weather_underground_api', 'Weather Underground API', array(__CLASS__, "wunderground_api_text"), 'wp_weather');
		add_settings_field('weather_underground_api_field', 'API Key', array(__CLASS__, "wunderground_api_textbox"), 'wp_weather', 'weather_underground_api');
		add_settings_section('ipinfodb_api', 'IPinfoDB API', array(__CLASS__, "ipinfodb_api_text"), 'wp_weather');
		add_settings_field('ipinfodb_api_field', 'API Key', array(__CLASS__, "ipinfodb_api_textbox"), 'wp_weather', 'ipinfodb_api');
		add_settings_section('image_select', 'Imageset', array(__CLASS__, "image_select_text"), 'wp_weather');
		add_settings_field('image_select_field', 'Select Imageset', array(__CLASS__, "image_select_checkboxes"), 'wp_weather', 'image_select');
		add_settings_section('image_select_custom', '', array(__CLASS__, "image_select_text_custom"), 'wp_weather');
		add_settings_field('image_select_custom_field', 'Upload Custom Sprite', array(__CLASS__, "image_select_checkboxes_custom"), 'wp_weather', 'image_select');
	}
	
	/**
	 * Validates and saves option information
	 *
	 * @author Jason Corradino
	 *
	 * @return array	Option data to save
	 */
	function validate_fields() {
		$options = get_option('wp_weather_options');
		
		if ($options['imageset_sprite'] != $_POST['upload_image']) {
			$conditions = array("chanceflurries","chancerain","chancesleet","chancesnow","chancetstorms","clear","cloudy","flurries","fog","hazy","mostlycloudy","mostlysunny","partlycloudy","partlysunny","sleet","rain","snow","sunny","tstorms");
			foreach ($conditions as $condition) {
				if ($out != "") {$out .= ", ";}
				$out .= ".weather_$condition";
			}
			$out .= " {background-image: url({$_POST['upload_image']});}\n";
			$css = file_get_contents(plugin_dir_path(__FILE__).'assets/wp-weather-sprite-template.css');
			$out .= $css;
			file_put_contents(plugin_dir_path(__FILE__).'assets/wp-weather.css', $out);
			$version = $options['css_version']+1;
		} else {
			$version = $options['css_version'];
		}
		
		return array(
			"wunderground_api" => $_POST['weather_underground_api_field'],
			"ipinfodb_api" => $_POST['ipinfodb_api_field'],
			"imageset" => $_POST['imageset'],
			"imageset_sprite" => $_POST['upload_image'],
			"css_version" => $version
		);
	}
	
	/**
	 * Description for WUnderground API key field
	 *
	 * @author Jason Corradino
	 *
	 * @return bool
	 */
	function wunderground_api_text() {
		echo '<p>Your Weather Underground API key, can be found <a href="http://www.wunderground.com/weather/api/">here</a>.</p>';
		return true;
	}

	/**
	 * Text box for WUnderground API key field
	 *
	 * @author Jason Corradino
	 *
	 */
	function wunderground_api_textbox() {
		$options = get_option('wp_weather_options');
		echo '<input type="text" name="weather_underground_api_field" id="wunderground-api" value="'.$options['wunderground_api'].'" />';
	}
	
	
	/**
	 * Description for IP Info DB API key field
	 *
	 * @author Jason Corradino
	 *
	 * @return bool
	 */
	function ipinfodb_api_text() {
		echo '<p>Your IPinfoDB API key, can be found <a href="http://ipinfodb.com/ip_location_api.php">here</a>.</p>';
		return true;
	}

	/**
	 * Text box for IP Info DB API key field
	 *
	 * @author Jason Corradino
	 *
	 */
	function ipinfodb_api_textbox() {
		$options = get_option('wp_weather_options');
		echo '<input type="text" name="ipinfodb_api_field" id="ipinfodb-api" value="'.$options['ipinfodb_api'].'" />';
	}
	
	/**
	 * Description for imageset selection
	 *
	 * @author Jason Corradino
	 *
	 * @return bool
	 */
	function image_select_text() {
		echo '<p>These are the imagesets available, select the one you would like to use, or submit your own.</p>';
		return true;
	}

	/**
	 * Generates selections for imageset selection
	 *
	 * @author Jason Corradino
	 *
	 */
	function image_select_checkboxes() {
		$options = get_option('wp_weather_options');
		if ($options['imageset'] == "") {
			$options['imageset'] = "k";
		}
		$conditions = array("chanceflurries","chancerain","chancesleet","chancesnow","chancetstorms","clear","cloudy","flurries","fog","hazy","mostlycloudy","mostlysunny","partlycloudy","partlysunny","sleet","rain","snow","sunny","tstorms");
		$image_sets = range("a", "k");
		foreach ($image_sets as $image_set) {
			echo "<section class='imageset'><input type='radio' name='imageset' value='$image_set' ";
			if ($options['imageset'] == $image_set) {
				echo "checked='checked'";
			}
			echo "><div class='images'>";
				foreach ($conditions as $condition) {
					echo "<img src='http://icons-ak.wxug.com/i/c/$image_set/$condition.gif' height='42' />";
				}
			echo '</div></section>';
		}
	}
	
	/**
	 * Mostly useless placeholder function, sits in place instead of displaying a description for custom sprite imageset select box
	 *
	 * @author Jason Corradino
	 *
	 * @return bool
	 */
	function image_select_text_custom () {
		return true;
	}
	
	/**
	 * Setup for custom sprite selector box, includes sprite setup and sprite uploader functionality
	 *
	 * @author Jason Corradino
	 *
	 */
	function image_select_checkboxes_custom () {
		$options = get_option('wp_weather_options');
		$conditions = array("chanceflurries","chancerain","chancesleet","chancesnow","chancetstorms","clear","cloudy","flurries","fog","hazy","mostlycloudy","mostlysunny","partlycloudy","partlysunny","sleet","rain","snow","sunny","tstorms");
		echo '
			<section class="imageset">
			<input type="radio" name="imageset" value="customSprite"';
			if ($options['imageset'] == "customSprite") {
				echo "checked='checked'";
			}
		echo '><div class="images">
			<div class="imageUploader">
		';
		foreach ($conditions as $condition) {
			echo '<div class="weather_'.$condition.' weather_sprite_icon" style="background-image:url(';
			echo ($options['imageset_sprite'] != "") ? $options['imageset_sprite'] : WP_PLUGIN_URL."/wp-weather/assets/wp-weather-sprite.png";
			echo ');"></div>';
		}
		echo '
			</div>
			<label for="upload_image" class="wp_weather_upload_image">
				<input id="upload_image" type="hidden" name="upload_image" value="" />
				<input id="upload_image_button" type="button" value="Upload New Sprite" />
				<small>Click "Insert into Post" to set, use <a href="'.WP_PLUGIN_URL.'/wp-weather/assets/wp-weather-sprite.png" target="_blank">default sprite</a> as a template</small>
			</label>
			</div>
			</section>
		';
	}
	
	/**
	 * Sets up the "setup page" in the WP Admin backend
	 *
	 * @author Jason Corradino
	 *
	 */
	function setup_pages() {
		add_options_page('WP Weather', 'WP Weather', 'manage_options', 'wp_weather', array(__CLASS__, "plugin_options"));
	}
	
	/**
	 * Sets HTML within settings page
	 *
	 * @author Jason Corradino
	 *
	 */
	function plugin_options() {
		?>
			<div class="wrap">
				<div id="icon-edit" class="icon32 icon32-wp-weather">
					<br>
				</div>
				<h2>WP Weather Settings</h2>
				<form action="options.php" method="post" id="wp_weather_options">
					<p><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
					<?php settings_fields('wp_weather_options'); ?>
					<?php do_settings_sections('wp_weather'); ?>
					<p><input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
				</form>
			</div>
		<?php
	}
}

class WP_Weather {
	
	var $data_lookup;
	
	var $city_state;
	
	private $_state_abbr = array('AL'=>"Alabama",
				                'AK'=>"Alaska", 
				                'AZ'=>"Arizona", 
				                'AR'=>"Arkansas", 
				                'CA'=>"California", 
				                'CO'=>"Colorado", 
				                'CT'=>"Connecticut", 
				                'DE'=>"Delaware", 
								'DC'=>"District of Columbia",
				                'FL'=>"Florida", 
				                'GA'=>"Georgia", 
				                'HI'=>"Hawaii", 
				                'ID'=>"Idaho", 
				                'IL'=>"Illinois", 
				                'IN'=>"Indiana", 
				                'IA'=>"Iowa", 
				                'KS'=>"Kansas", 
				                'KY'=>"Kentucky", 
				                'LA'=>"Louisiana", 
				                'ME'=>"Maine", 
				                'MD'=>"Maryland", 
				                'MA'=>"Massachusetts", 
				                'MI'=>"Michigan", 
				                'MN'=>"Minnesota", 
				                'MS'=>"Mississippi", 
				                'MO'=>"Missouri", 
				                'MT'=>"Montana",
				                'NE'=>"Nebraska",
				                'NV'=>"Nevada",
				                'NH'=>"New Hampshire",
				                'NJ'=>"New Jersey",
				                'NM'=>"New Mexico",
				                'NY'=>"New York",
				                'NC'=>"North Carolina",
				                'ND'=>"North Dakota",
				                'OH'=>"Ohio", 
				                'OK'=>"Oklahoma", 
				                'OR'=>"Oregon", 
				                'PA'=>"Pennsylvania", 
				                'RI'=>"Rhode Island", 
				                'SC'=>"South Carolina", 
				                'SD'=>"South Dakota",
				                'TN'=>"Tennessee", 
				                'TX'=>"Texas", 
				                'UT'=>"Utah", 
				                'VT'=>"Vermont", 
				                'VA'=>"Virginia", 
				                'WA'=>"Washington", 
				                'WV'=>"West Virginia", 
				                'WI'=>"Wisconsin", 
				                'WY'=>"Wyoming");
	
	/**
	 * Fetches current conditions
	 *
	 * @param string|int [OPTIONAL] $zip	Zip code to use in lieu of user-selected/discovered location
	 *
	 * @author Jason Corradino
	 *
	 * @return object	Weather information
	 */
	function get_current_conditions($zip="") {
		$user = get_current_user_id();
		$userLocation  = get_user_meta( $user, 'user_zipcode', true );
		$usercity  = get_user_meta( $user, 'user_city', true );
		$userstate  = get_user_meta( $user, 'user_state', true );
		if ($zip != "") { // use pre-set zip
			$transient = get_transient("{$this->data_lookup}-$zip");
			$this->city_state = $this->zip2loc($zip);
			if ($transient == "") {
				$conditions = $this->wunderground_api($zip);
				set_transient("{$this->data_lookup}-$zip", $conditions, 900);
			} else {
				$conditions = $transient;
			}
		} elseif ($_COOKIE["weather_location"] != "" || $_POST['update_zipcode'] != "") {
			$czip = $_POST["update_zipcode"] ? $_POST["update_zipcode"] : $_COOKIE["weather_location"];
			$transient = get_transient("{$this->data_lookup}-$czip");
			$this->city_state = $this->zip2loc($czip);
			if ($transient == "") {
				$conditions = $this->wunderground_api($czip);
				set_transient("{$this->data_lookup}-$czip", $conditions, 900);
			} else {
				$conditions = $transient;
			}
		} elseif  ($userLocation != "") { // use user zip
			$transient = get_transient("{$this->data_lookup}-$userLocation");
			$this->city_state = "$usercity, $userstate";
			if ($transient == "") {
				$conditions = $this->wunderground_api("$userLocation");
				set_transient("{$this->data_lookup}-$userLocation", $conditions, 900);
			} else {
				$conditions = $transient;
			}
		} else { // lookup weather based on IP location
			$location = $this->location_api();
			if ($location->statusCode == "OK") {
				//$coords['lon'] = $location->longitude;
				//$coords['lat'] = $location->latitude;
				//$locationCode = ($location->zipCode != "" && $location->zipCode != "-") ? $location->zipCode : "{$location->countryCode}-{$location->cityName}";
				$transient = get_transient("{$this->data_lookup}-{$location->zipCode}");
				if ($transient == "") {
					$conditions = $this->wunderground_api("{$location->zipCode}");
					set_transient("{$this->data_lookup}-$locationCode", $conditions, 900);
				} else {
					$conditions = $transient;
				}
				$this->city_state = $this->zip2loc($location->zipCode);
			}
		}
		
		if ($conditions != "") {
			return $conditions;
		} else {
			return false;
		}
	}
	
	/**
	 * Fetches user's current location based on REMOTE_ADDR
	 *
	 * @author Jason Corradino
	 *
	 * @return object	User's current location (longitude/latitude/city/etc)
	 */
	function location_api() {
		$options = get_option('wp_weather_options');
		//$uri = 'http://api.ipinfodb.com/v3/ip-city/?key='.$options["ipinfodb_api"].'&format=xml&ip='.$_SERVER['REMOTE_ADDR'];
		//$uri = 'http://api.ipinfodb.com/v3/ip-city/?key='.$options["ipinfodb_api"].'&format=xml&ip=141.101.116.82'; // London
		$uri = 'http://api.ipinfodb.com/v3/ip-city/?key='.$options["ipinfodb_api"].'&format=xml&ip=12.34.4.33'; // Chicago
		$data = $this->get_data($uri);
		if(substr_count($data,'ode>ERROR') ){
			return false;
		} else {
			$location = simplexml_load_string($data);
		}
		return $location;
	}
	
	/**
	 * Fetches user's Weather
	 *
	 * @param string [REQUIRED] $query	Location string sent to Weather Underground
	 *
	 * @author Jason Corradino
	 *
	 * TODO - Allow other API lookups (such as multi-day forecast, radar, etc)
	 *
	 * @return object	Weather inforation
	 */
	function wunderground_api($query) {
		$options = get_option('wp_weather_options');
		if ($this->data_lookup == "5day") {$api = "forecast10day";}
			else {$api = $this->data_lookup;}
		$uri = "http://api.wunderground.com/api/{$options['wunderground_api']}/$api/q/$query.json";
		$return = $this->get_data($uri);
		$data = json_decode($return);
		if ($data->response->error != "") {
			return false;
		} else {
			return $data;
		}
	}
	
	/**
	 * Converts zip code to "city, state" format
	 *
	 * @param string|int [REQUIRED] $zip		User's zip code
	 *
	 * @author Jason Corradino
	 *
	 * @return string	City and state
	 */
	function zip2loc($zip) {
		$transient = get_transient("citystate-$zip");
		if ($transient == "") {
			$uri = "http://zip.elevenbasetwo.com/v2/US/$zip";
			$json = $this->get_data($uri);
			$location = json_decode($json);
			$loc_string = $location->city.", ".$this->state2abbr($location->state);
			set_transient("citystate-$zip", $loc_string, 432000);
			return $loc_string;
		} else {
			return $transient;
		}
	}
	
	/**
	 * Looks up state abbreviation
	 *
	 * @param string [REQUIRED] $state		User's state
	 *
	 * @author Jason Corradino
	 *
	 * @return string	State abbreviation
	 */
	function state2abbr($state) {
		$abbr = array_search(trim($state), $this->_state_abbr);
		if($abbr !== false) {
			return $abbr;
		}
		return $state;
	}
	
	/**
	 * Makes the API call to the remote server
	 *
	 * @param string [REQUIRED] $URI			Request destination
	 *
	 * @param string|int [Optional] $timeout	Amount of time to wait for an API response
	 *
	 * @author Jason Corradino
	 *
	 * @return array	API response
	 */
	function get_data($uri, $timeout=5) {
		if($timeout==0 or !$timeout){$timeout=5;}
		if(ini_get('allow_url_fopen')) {
			$opts = array('http' => array('timeout' => $timeout));
			$context  = stream_context_create($opts);
			$return = @file_get_contents($uri,false,$context);
		} else {
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $uri);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
			$return = @curl_exec($ch);
			curl_close($ch);
		}
		return $return;
	}
}


/**
 * Includes generated CSS if user is using a custom sprite
 *
 * @author Jason Corradino
 *
 */
function WP_Weather_includes() {
	$options = get_option('wp_weather_options');
	if ($options['imageset'] == "customSprite") {
		wp_register_style( 'wp-weather-style', WP_PLUGIN_URL.'/wp-weather/assets/wp-weather.css', null, $options['css_version']);
		wp_enqueue_style( 'wp-weather-style' );
	}
}


if (is_admin()) {
	WP_Weather_Admin::init();
} else {
	add_action('init', 'WP_Weather_includes');
}
?>