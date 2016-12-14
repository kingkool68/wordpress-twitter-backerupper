<?php
class Tweet_Archiver_Scheduled_Events {

	private $media_event_key = 'tweet_archiver_refresh_media';
	private $users_event_key = 'tweet_archiver_refresh_users';
	private $import_tweets_key = 'tweet_archiver_import_latest_tweets';

	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'init', array( $this, 'schedule_events' ) );
		register_deactivation_hook( __FILE__, array( $this, 'cleanup_scheduled_events' ) );

		// Listeners for scheduled event actions
		add_action( $this->media_event_key, array( $this, 'refresh_media' ) );
		add_action( $this->users_event_key, array( $this, 'refresh_users' ) );
		add_action( $this->import_tweets_key, array( $this, 'import_latest_tweets' ) );
	}

	public function cron_schedules( $schedules = array() ) {
		$key = 'quarterhourly';
		if ( ! isset( $schedules[ $key ] ) ) {
			$schedules[ $key ] = array(
				'interval' => 15 * MINUTE_IN_SECONDS,
				'display'=> 'Quarter Hourly',
			);
		}

		$key = 'halfhourly';
		if ( ! isset( $schedules[ $key ] ) ) {
			$schedules[ $key ] = array(
				'interval' => 30 * MINUTE_IN_SECONDS,
				'display'=> 'Half Hourly',
			);
		}
		return $schedules;
	}

	public function schedule_events() {
		if ( ! wp_next_scheduled( $this->media_event_key ) ) {
			wp_schedule_event( time(), 'halfhourly', $this->media_event_key );
		}

		if ( ! wp_next_scheduled( $this->users_event_key ) ) {
			wp_schedule_event( time(), 'halfhourly', $this->users_event_key );
		}

		if ( ! wp_next_scheduled( $this->import_tweets_key ) ) {
			wp_schedule_event( time(), 'quarterhourly', $this->import_tweets_key );
		}

	}

	public function cleanup_scheduled_events() {
		$keys = array(
			$this->users_event_key,
			$this->media_event_key,
			$this->import_tweets_key,
		);

		foreach ( $keys as $key ) {
			$event_timestamp = wp_next_scheduled( $key );
			wp_unschedule_event( $event_timestamp, $key );
		}

	}

	public function refresh_media() {
		Tweet_Archiver_Import_Tweet::download_detailed_media();
	}

	public function refresh_users() {
		Tweet_Archiver_Import_Tweet::download_detailed_users();
	}

	public function import_latest_tweets() {
		$obj = new Tweet_Archiver_API_Fetcher;
		$obj->import_the_latest_tweets();
	}
}
new Tweet_Archiver_Scheduled_Events;
