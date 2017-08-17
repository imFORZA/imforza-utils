<?php
/**
 * A Utility library for imFORZA developers with commonly used functions.
 *
 * @package imforza-utils
 */

if ( ! class_exists( 'IMFORZA_Utils' ) ) {

	/**
	 * Group of utility methods for use by imFORZA
	 * All methods are static, this is just a sort of namespacing class wrapper.
	 */
	class IMFORZA_Utils {

		/**
		 * Block Direct File Access.
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function block_direct_file_access() {

			/* Exit if accessed directly. */
			if ( ! defined( 'ABSPATH' ) ) {
				exit;
			}
		}


		/**
		 * Advanced error_log method. Prints arrays recursively.
		 *
		 * @static
		 * @param  [Mixed] $data : Data to print.
		 */
		public static function error_log( $data ) {
			error_log( print_r( $data, true ) );
		}

		/**
		 * Schedule cron Job.
		 *
		 * @static
		 * @param  [String] $hook        : Hook to use for cron event.
		 * @param  [String] $recurrence  : Cron recurrence period. i.e. daily, monthly, etc.
		 * @param  [Int]    $time        : Time to execute cron.
		 */
		public static function schedule_cron( $hook, $recurrence = null, $time = null ) {
			// If reccurence and time not sent, then set defaults.
			$recurrence = $recurrence ?? 'daily';
			$time = $time ?? time();

			// Schedule.
			if ( ! wp_next_scheduled( $hook ) ) {
				wp_schedule_event( $time, $recurrence , $hook );
			}
		}

		/**
		 * Schedule cron Job.
		 *
		 * @static
		 * @param  [String] $hook        : Cron hook to unschedule.
		 */
		public static function unschedule_cron( $hook ) {
			$timestamp = wp_next_scheduled( $hook );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		/**
		 * Checks if the request is coming from localhost.
		 *
		 * @static
		 * @return boolean : Returns true if call is being made from localhost.
		 */
		public static function is_localhost() {
			// Get remote IP.
			$remote_ip = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

			$local_ips = array(
			'127.0.0.1', // IPv4.
			'::1',			 // IPv6.
			);

			$local_ips = apply_filters( 'imutils_local_ips', $local_ips );

			return in_array( $remote_ip, $local_ips, true );
		}

		/**
		 * Checks if current site is forbidden.
		 *
		 * @static
		 * @return boolean : Returns true if current site is forbidden source.
		 */
		public static function is_forbidden_source() {
			// Forbidden domains.
			$forbidden = array(
				'.wpengine.com',
				'.dev',
				'.local',
			);

			// Filter to add or remove domains from forbidden array.
			$forbidden = apply_filters( 'imutils_forbiddden_domains', $forbidden );

			// Grab the installs url.
			$this_domain = parse_url( get_site_url(), PHP_URL_HOST );

			foreach ( $forbidden as $domain ) {
				if ( false !== strpos( $this_domain, $domain ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if current user is accessing site from a mac.
		 *
		 * @static
		 * @return boolean : True if mac, else false.
		 */
		public static function is_user_mac() {
			$user_agent = getenv( 'HTTP_USER_AGENT' );
			if ( strpos( $user_agent, 'Mac' ) !== false ) {
				return true;
			}

			return false;
		}

		/**
		 * Determine if imFORZA is in development mode?
		 *
		 * @static
		 * @return bool
		 */
		public static function is_development_mode() {
			$development_mode = false;
			if ( defined( 'IMFORZA_DEBUG' ) ) {
				$development_mode = IMFORZA_DEBUG;
			} elseif ( site_url() && false === strpos( site_url(), '.' ) ) {
				$development_mode = true;
			}

			/**
			 * Filter the imforza development mode.
			 *
			 * @param bool $development_mode Is imforza development mode active.
			 */
			return apply_filters( 'imforza_development_mode', $development_mode );
		}

		/**
		 * Convert a csv file to an array with keys.
		 *
		 * @param  string  $file_path Path to csv file.
		 * @param  boolean $to_json   True to return array as json.
		 * @return array|WP_Error|json
		 */
		public static function fcsv_to_array( $file_path, bool $to_json = false ){
			// Open file, return error if file could not be opened.
			if ( ( $file = fopen( $file_path, 'r')) === false) {
				return new WP_Error( 'file-error',  __( 'Error: Could not open file.', 'hostops' ) );
			}

			$keys = array_map( 'trim', fgetcsv( $file ) );
			$data = array();

			while ( $row = fgetcsv( $file ) ) {
				$data[] = array_combine( $keys, array_map( 'trim', $row ) );
			}

			fclose($file);

			if( true === $to_json ){
				$data = wp_json_encode( $data );
			}

			return $data;
		}

		/**
		 * Takes a CSV string and converts it to an array.
		 *
		 * Must be formatted where first line is keys, and all preceeding lines are values.
		 *
		 * @param  [type]  $string  [description]
		 * @param  boolean $to_json [description]
		 * @return array
		 */
		public static function scsv_to_array( $string, bool $to_json = false ){
			$string = stripslashes( $string );

			$lines = array_map( 'trim', preg_split( '/(\r\n|\r|\n)/', $string ) );

			if( count( $lines ) == 0 ){
				return array();
			}

			// removes null items
			$keys = array_map( 'trim', str_getcsv( $lines[0] ) );
			$ideal_length = count( $keys );

			// Removes elements that do not have the correct number of parameters (though it's OK if they're blank, long as there's enough?);
			$data = array();
			foreach( array_slice( $lines, 1 ) as $line ){
				$s = str_getcsv( $line );
				if( count( $s ) == $ideal_length ){
					array_push( $data, $s );
				}else if( count( $s ) > $ideal_length ){ // Helpful in case of trailing commas.
					array_push( $data, array_slice( $s, 0, $ideal_length ) );
				}
			}

			// Maps values to the keys
			$output = array();
			for( $i = 0; $i<count($data); $i++){
				 array_push( $output, array_combine( $keys, array_map( 'trim', $data[$i] ) ) ); // Default delineator is ','
			}

			if( $to_json === true ){
				return wp_json_encode( $lines );
			}

			return $output;

		}

	} // end class.

	/**
	 * Wrapper function for Block Direct File Access.
	 *
	 * @access private
	 * @return void
	 */
	function _block_direct_file_access() {
		IMFORZA_Utils::block_direct_file_access();
	}


	/**
	 * Wrapper function for IMFORZA_Utils::error_log();
	 *
	 * @param  [Mixed] $data : Data to print.
	 */
	function _error_log( $data ) {
		IMFORZA_Utils::error_log( $data );
	}

	/**
	 * Wrapper function for IMFORZA_Utils::schedule_cron();
	 *
	 * @param  [String] $hook        : Hook to use for cron event.
	 * @param  [String] $recurrence  : Cron recurrence period. i.e. daily, monthly, etc.
	 * @param  [Int]    $time        : Time to execute cron.
	 */
	function _schedule_cron( $hook, $recurrence = null, $time = null ) {
		IMFORZA_Utils::schedule_cron( $hook, $recurrence, $time );
	}

	/**
	 * Wrapper function for IMFORZA_Utils::unschedule_cron();
	 *
	 * @param  [String] $hook        : Cron hook to unschedule.
	 */
	function _unschedule_cron( $hook ) {
		IMFORZA_Utils::unschedule_cron( $hook );
	}

	/**
	 * Wrapper function for IMFORZA_Utils::is_localhost();
	 *
	 * @return boolean : Returns true if call is being made from localhost.
	 */
	function _is_localhost() {
		return IMFORZA_Utils::is_localhost();
	}

	/**
	 * Wrapper function for IMFORZA_Utils::is_forbidden_source();
	 *
	 * @return boolean : Returns true if current site is forbidden source.
	 */
	function _is_forbidden_source() {
		return IMFORZA_Utils::is_forbidden_source();
	}

	/**
	 * Wrapper function for IMFORZA_Utils::is_user_mac();
	 *
	 * @return boolean : True if mac, else false.
	 */
	function _is_user_mac() {
		return IMFORZA_Utils::is_user_mac();
	}

	/**
	 * Wrapper function for IMFORZA_Utils::is_development_mode();
	 *
	 * @return bool
	 */
	function _is_development_mode() {
		return IMFORZA_Utils::is_development_mode();
	}

	/**
	 * Wrapper function for IMFORZA_Utils::csv_to_array();
	 *
	 * @return array|WP_Error|json
	 */
	function _fcsv_to_array( $file_path, bool $to_json = false ){
		return IMFORZA_Utils::fcsv_to_array( $file_path, $to_json );
	}
	/** Wrapper for _fcsv_to_array in case of incompatibility */
	function _csv_to_array( $file_path, bool $to_json = false ){
		return _fcsv_to_array( $file_path, $to_json );
	}

	/**
	 * Wrapper function for IMFORZA_Utils::csv_s_to_array();
	 *
	 * @return array|WP_Error|json
	 */
	function _scsv_to_array( string $string, bool $to_json = false ){
		return IMFORZA_Utils::scsv_to_array( $string, $to_json );
	}

} // end if.
