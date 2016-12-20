<?php

class Tweet_Archiver_API_Fetcher {

	public function get_queued_tweets() {
		return get_option( 'tweet_archiver_queued_tweets' );
	}

	public function save_queued_tweets( $tweets = array(), $append = false ) {
		if ( $append ) {
			$old_tweets =  $this->get_queued_tweets();
			$tweets = array_merge( $old_tweets, $tweets );
		}
		update_option( 'tweet_archiver_queued_tweets', $tweets );
	}

	public function get_the_latest_tweet_id() {
		global $wpdb;
		$sql = "SELECT `meta_value` FROM {$wpdb->postmeta} WHERE 1=1 AND `meta_key` = 'tweet_id' ORDER BY `meta_value`+0 DESC LIMIT 0, 1";
		return $wpdb->get_var( $sql );
	}

	public function import_the_latest_tweets() {
		$tweet_ids = $this->fetch_the_latest_tweet_ids();
		if ( empty( $tweet_ids ) ) {
			return;
		}
		$this->save_queued_tweets( $tweet_ids );
		$this->import_queued_tweets();
	}

	public function fetch_the_latest_tweet_ids() {
		// Get the latest Tweet ID
		$latest_tweet_id = $this->get_the_latest_tweet_id();
		$creds = new Tweet_Archiver_Twitter_Credentials();
		$tw = $creds->get_twitter_connection();
		$screen_name = get_option( 'authenticated_twitter_user' );
		$tweet_ids = array();
		$max_id = false;
		$continue_fetching = true;
		$loop_count = 0;

		$args = array(
			'screen_name' => $screen_name,
			'since_id' => $latest_tweet_id,
			'exclude_replies' => false,
			'include_rts' => true,
			'count' => 200, // Max 200
			'tweet_mode' => 'extended',
		);
		while ( $continue_fetching ) {
			$tweets = $tw->token_endpoint( 'statuses/user_timeline.json', $args );
			foreach ( $tweets as $tweet ) {
				if ( ! $continue_fetching ) { break; }
				if ( $tweet->id_str === $latest_tweet_id ) {
					$continue_fetching = false;
					break;
				}
				$tweet_ids[] = $tweet->id_str;
				$args['max_id'] = $tweet->id_str;
			}
			unset( $tweets );
			$loop_count++;
			if ( $loop_count > 3200 / $args['count'] ) {
				$continue_fetching = false;
				break;
			}
			if ( count( $tweet_ids ) >= 3200 ) {
				$continue_fetching = false;
				break;
			}
		}

		return array_unique( $tweet_ids );
	}

	public function import_queued_tweets() {
		$max_tweets_to_process = 100;
		// Get our queue of Tweet IDs
		$all_tweet_ids = $this->get_queued_tweets();
		// We can only process 100 tweets at a time max, so get the first 100 tweet IDs
		$tweet_ids = array_slice( $all_tweet_ids, 0, $max_tweets_to_process );
		// The rest of the Tweet IDs we'll hold on to for updating the queue later
		$remaining_ids = array_slice( $all_tweet_ids, $max_tweets_to_process + 1 );

		$creds = new Tweet_Archiver_Twitter_Credentials();
		$tw = $creds->get_twitter_connection();
		$id_str = implode( ',', $tweet_ids );
		$args = array(
			'id' => $id_str,
			'include_entities' => true,
			'trim_user' => false,
			'tweet_mode' => 'extended',
		);
		$tweets = $tw->token_endpoint( 'statuses/lookup.json', $args );
		if ( is_wp_error( $tweets ) ) {
			return;
		}
		foreach ( $tweets as $tweet ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet( $tweet );
			$tweet_import->save();
		}

		if ( ! empty( $remaining_ids ) ) {
			$this->save_queued_tweets( $remaining_ids );
			$result = wp_schedule_single_event( time() + 10, 'import_queued_tweets_action', array( 'rand' => rand() ) );
		}
	}
}

function import_the_latest_tweets() {
	$obj = new Tweet_Archiver_API_Fetcher;
	$obj->import_the_latest_tweets();
}

function import_queued_tweets_action() {
	$obj = new Tweet_Archiver_API_Fetcher;
	$obj->import_queued_tweets();
}
add_action( 'import_queued_tweets_action', 'import_queued_tweets_action' );
