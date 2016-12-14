<?php
/*
Plugin Name: Twitter Backerupper
Plugin URI: http://www.russellheimlich.com
Description: Keep your own archive of your tweets and media.
Version: 0.0.1
Author: Russell Heimlich
Author URI: http://www.russellheimlich.com
GitHub Plugin URI: https://github.com/kingkool68/wordpress-twitter-backerupper
*/

class Twitter_Backerupper {

	public function __construct() {
		$this->include_stuff();
		$this->include_external_dependencies();
	}

	public function include_stuff() {
		require_once( 'inc/class-tweet-helpers.php' );
		require_once( 'inc/class-register-content-types.php' );
		require_once( 'inc/class-twitter-credentials.php' );
		require_once( 'inc/class-tweet-importer.php' );
		require_once( 'inc/class-api-fetcher.php' );
		require_once( 'inc/class-scheduled-events.php' );
		require_once( 'inc/template-tags.php' );
		if ( is_admin() ) {
			require_once( 'inc/class-archive-importer.php' );
		}
	}

	public function include_external_dependencies() {
		// For text extraction of tweets
		require_once( 'lib/twitter-text-php/lib/Twitter/Extractor.php' );
		// A WordPress specific Twitter library
		require_once( 'lib/TwitterWP/lib/TwitterWP.php' );
		// UTF-8 Fixer for dealing with emojis
		require_once( 'lib/ForceUTF8/Encoding.php' );
	}
}
new Twitter_Backerupper;

// Helper for now
function twitter_archive_make_main_query_tweets( $query ) {
	if ( $query->is_main_query() && ! is_admin() ) {
		$query->set( 'post_type', 'tweet' );
	}
}
add_action( 'pre_get_posts', 'twitter_archive_make_main_query_tweets' );
