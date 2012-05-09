<?php
/*
Plugin Name: wp-expurgate
Plugin URI: http://digital.bigfish.co.uk/2012/04/avoiding-mixed-content-with-ssl/
Description: Run your blog on SSL? Want to avoid mixed content warnings? Use this!
Plugin Author: Rob Miller
Author URI: http://www.bigfish.co.uk/
Version: 1.0
*/

class expurgate {

	public function init() {
		$this->cache_dir = dirname(__FILE__) . '/expurgate/cache';
		$this->key_file  = $this->cache_dir . '/key.txt';

		$this->expurgate_url = plugins_url('expurgate/expurgate.php', __FILE__);

		if ( $this->installed() ) {
			add_filter('the_content', array(&$this, 'convert_urls'));
			add_filter('comment_text', array(&$this, 'convert_urls'));
		} else {
			add_action('admin_notices', array(&$this, 'install_notice'));
		}
	}

	// Checks whether the prerequisites for running Expurgate have been met.
	private function installed() {
		$key_exists = is_readable($this->key_file);

		if ( !$key_exists ) {
			$this->generate_key();
		}

		$key_exists = is_readable($this->key_file);

		if ( $key_exists ) {
			$this->key = file_get_contents($this->key_file);
		}

		return ( $key_exists );
	}

	// Displays a message in the admin if Expurgate doesn't have everything it
	// needs to run
	public function install_notice() {
		$this->generate_key();
		if ( !is_readable($this->key_file) ) {
			echo '
			<div class="error">
				<p>
					I couldn’t find a key file in
					<strong>' . dirname(__FILE__) . '/expurgate/cache/</strong>.
					Without one, expurgate won’t work.
				</p>
				<p>
					I tried to create one, but I couldn’t!
				</p>
			</div>
			';
		}

		if ( !is_writable($this->cache_dir) ) {
			echo '
			<div class="error">
				<p>
					The expurgate cache directory isn’t writable. Please 
					correct this with:
				</p>
				<p>
					chmod 777 ' . dirname(__FILE__) . '/expurgate/cache/
				</p>
			</div>
			';
		}
	}

	// Attempts to generate a key and write it to the expurgate key file.
	private function generate_key() {
		if ( !is_writable(dirname($this->key_file)) ) {
			return false;
		}

		$charset  = 'abcdefghijklmnopqrstuvwxyz';
		$charset .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charset .= '0123456789';
		$charset .= '!@$%^*()-_=+;:\'"\\|,<.>/?`~';

		$key = '';

		for ( $i = 0; $i < 512; $i++ ) {
			$key .= $charset[mt_rand(0, strlen($charset))];
		}

		return file_put_contents($this->key_file, $key);
	}

	// Parses content for HTTP URLs and runs them through expurgate.
	public function convert_urls($content) {
		preg_match_all(
			'/<img[^>]+src=["\'](http:\/\/[^\'"]+)[\'|"][^>]*>/',
			$content,
			$matches
		);

		foreach ( (array) $matches[1] as $url ) {
			$checksum = $this->calculate_checksum($url);

			$new_url = $this->expurgate_url . '?url=' . $url . '&checksum=' . $checksum;

			$content = str_replace($url, $new_url, $content);
		}

		return $content;
	}

	private function calculate_checksum($url) {
		return hash_hmac('sha256', $url, $this->key);
	}

}

$expurgate = new expurgate();
add_action('init', array(&$expurgate, 'init'));
