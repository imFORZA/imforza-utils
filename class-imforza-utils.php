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
} // end class.
} // end if.
