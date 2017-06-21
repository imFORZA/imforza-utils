<?php

if( ! class_exists( 'IMFORZA_Utils' ) ) {

/**
 * Group of utility methods for use by imFORZA
 * All methods are static, this is just a sort of namespacing class wrapper.
 */
class IMFORZA_Utils {

	/**
	 * Determine if imFORZA is in development mode?
	 *
	 * @return bool
	 */
	public static function is_development_mode() {
		$development_mode = false;
		if ( defined( 'IMFORZA_DEBUG' ) ) {
			$development_mode = IMFORZA_DEBUG;
		}
		elseif ( site_url() && false === strpos( site_url(), '.' ) ) {
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
	 * Schedule cron Job.
	 *
	 * @static
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

		return in_array( $remote_ip, $local_ips );
	}

	/**
	 * Checks if current site is forbidden.
	 *
	 * @return boolean : Returns true if current site is forbidden source.
	 */
	public static function is_forbidden_source() {
		// Forbidden domains.
		$forbidden = array(
			'staging.wpengine.com',
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

} // end class.

} // end if.
