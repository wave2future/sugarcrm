<?php
/* 
 * Mockup disabled function in Sina App Engine
 * 
 * @author: yinhm <yinhm@twitter>
 */

class SaeDisabled {

	public static function get_magic_quotes_gpc() {
		return 0;
	}

	public static function php_uname($mode = "a") {
		if ($mode == 's') {
			return 'Linux';
		}
		if ($mode == 'r') {
			return '1.0.2';
		}
		return 'Linux Sina App Engine';
	}


	public static function get_magic_quotes_gpc() {
		return 0;
	}

	# no effect
	public static function set_time_limit($limit) {
	}

}
