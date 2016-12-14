<?php
if ( ! defined('WP_LOAD_IMPORTERS') && ( ! defined('DOING_AJAX') || ! DOING_AJAX ) ) {
	return;
}

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) ) {
		require_once $class_wp_importer;
	}
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}


class Tweet_Archiver_Importer extends WP_Importer {

	private $upload_destination;

	private $filenames;

	public $ajax_nonce_string = 'twitter-import-js';

	public function __construct() {
		$this->upload_destination = WP_CONTENT_DIR . '/tweet-archive/';
		add_action( 'plugins_loaded', array( $this, 'register_importer' ) );
		add_action( 'wp_ajax_process-twitter-archive-js-file', array( $this, 'handle_js_file_ajax' ) );
	}

	public function register_importer() {
		register_importer( 'tweet-archive', 'Twitter Archive', 'Import tweets from a ZIP file from Twitter', array( $this, 'dispatch' ) );
	}

	public function dispatch() {
		$this->setup_filesystem_access();
		$this->header();

		if ( empty( $_GET['step'] ) ) {
			$step = 0;
		} else {
			$step = (int) $_GET['step'];
		}

		switch ( $step ) {
			case 0 :
				// $this->cleanup_previous_import_files();
				// $this->greet();

				$this->test();
				// $this->prepare_filenames();
				// $this->show_post_upload_screen();
			break;
			case 1 :
				check_admin_referer('import-upload');
				$this->handle_upload();
				$this->prepare_filenames();
				$this->show_post_upload_screen();
			break;
		}

		$this->footer();
	}

	public function setup_filesystem_access() {
		require_once( ABSPATH .'/wp-admin/includes/file.php' );
		WP_Filesystem();
	}

	public function header() {
		echo '<div class="wrap">';
		echo '<h2>Tweet Importer</h2>';
	}

	public function footer() {
		echo '</div>'; // Closes div.wrap
	}

	public function greet() {
		wp_import_upload_form( add_query_arg( 'step', 1 ) );
	}

	function handle_upload() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>Sorry, there has been an error.</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		}

		if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>Sorry, there has been an error.</strong><br />';
			printf( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}

		$this->file_id = (int) $file['id'];
		$file = get_attached_file( $this->file_id );
		// Uploaded files get '.txt' appended to the end of them so we need to fix that
		$renamed_file = str_replace( '.txt', '', $file );
		rename( $file, $renamed_file );
		$this->file = $renamed_file;
		$unzipped = unzip_file( $renamed_file, $this->upload_destination );
		if ( ! $unzipped ) {
			echo 'There was a problem unzipping the file.';
			return false;
		}

		return true;
	}

	public function cleanup_previous_import_files() {
		global $wp_filesystem;
		if ( $this->is_dir_empty( $this->upload_destination ) ) {
			return;
		}
		return $wp_filesystem->rmdir( $this->upload_destination, true );
	}

	public function prepare_filenames() {
		$path = $this->upload_destination . 'data/js/tweets/';
		$files = glob( $path . '*.js' );
		$this->filenames = array();
		foreach( $files as $file ) {
			$this->filenames[] = str_replace( $path, '', $file );
		}
	}

	public function show_post_upload_screen() {
		wp_enqueue_script('jquery');
		$ajax_nonce = wp_create_nonce( $this->ajax_nonce_string );
		$files = implode( '\',\'', $this->filenames );
		$file_count = number_format( count( $this->filenames ) );
		?>
		<h2>Processing&hellip;</h2>
		<dl>
			<dt>Import Duration</dt>
			<dd id="import-duration"></dd>
			<dt>Processed</dt>
			<dd id="tweet-import-date">--</dd>
			<dt>Number of Tweets Imported</dt>
			<dd id="number-of-tweets">0</dd>
			<dt>Tweets imported per second</dt>
			<dd><span id="tweets-imported-per-second">0</span></dd>
			<dt>Progress</dt>
			<dd id="tweet-import-percentage">0%</dd>
			<dt>Files remaining</dt>
			<dd id="files-remaining-to-import"><?php echo $file_count; ?></dd>
		</dl>

		<script>
			jQuery(document).ready(function($) {
				var files = ['<?php echo $files; ?>'];
				var file = '';
				var totalFiles = files.length;
				var numberOfTweets = 0;
				var startTime = Date.now();
				var fileState = {
					files: files,
					totalFiles: files.length
				};

				function numberWithCommas(x) {
					return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
				}

				function process_file( pageToProcess ) {
					if(!pageToProcess) {
						pageToProcess = 1;
					}
					if ( pageToProcess <= 1 ) {
						file = files.pop();
					}
					var data = {
						'file': file,
						'nonce': '<?php echo $ajax_nonce; ?>',
						'action': 'process-twitter-archive-js-file',
						'max_tweets_to_process': 50,
						'page': pageToProcess
					};
					var theRequest = $.post(ajaxurl, data);
					theRequest.done(function( response ) {
						var importDate = response.data.message;
						var page = response.data.page;
						var maxPages = response.data.max_pages;
						var percentage = files.length / totalFiles * 100;
						percentage = 100 - percentage;
						percentage = percentage.toFixed(1) + '%';
						numberOfTweets += response.data.tweets;
						timeDiff = (Date.now() - startTime) / 1000;
						tweetsPerSecond = numberOfTweets / timeDiff;

						$('#tweet-import-date').text( importDate );
						$('#tweet-import-percentage').text( percentage );
						$('#number-of-tweets').text( numberWithCommas( numberOfTweets ) );
						$('#files-remaining-to-import').text( files.length );
						$('#import-duration').text( timeDiff + ' seconds' );
						$('#tweets-imported-per-second').text( numberWithCommas( tweetsPerSecond.toFixed(2) ) );

						var nextPage = page + 1;
						if ( nextPage <= maxPages ) {
							process_file( nextPage );
						} else if ( files.length > 0 ) {
							process_file();
						}
					});
					theRequest.fail(function( response ) {
						alert( file + ' failed!' );
					});
				}
				process_file();
			});
		</script>
		<?php
	}

	public function handle_js_file_ajax() {
		check_ajax_referer( $this->ajax_nonce_string, 'nonce' );
		$file_name = sanitize_text_field( $_POST['file'] );
		$tweets_per_page = 9999;
		if ( ! empty( $_POST['max_tweets_to_process'] ) ) {
			$tweets_per_page = intval( $_POST['max_tweets_to_process'] );
		}
		$page = 1;
		if ( ! empty( $_POST['page'] ) ) {
			$page = intval( $_POST['page'] );
		}
		$path = $this->upload_destination . 'data/js/tweets/' . $file_name;
		if ( ! file_exists( $path ) ) {
			$data = (object) array(
				'message' => 'Bad filename! ' . $file_name . ' does not exist.',
			);
			http_response_code(500);
			wp_send_json_error( $data );
		}

		$full_file = file_get_contents( $path );
		$json_string = substr( $full_file, strpos( $full_file, "\n" ) + 1 );
		$json_payload = json_decode( $json_string );

		// Do interesting things with the JSON payload
		$start = $page * $tweets_per_page - $tweets_per_page;
		$tweets = array_slice( $json_payload, $start, $tweets_per_page );
		foreach ( $tweets as $tweet ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet( $tweet );
			$tweet_import->save();
		}

		$file_name_parts = explode( '_', $file_name );
		$year = $file_name_parts[0];
		$month = $file_name_parts[1];
		$month_name = date('F', mktime(0, 0, 0, intval( $month ), 10));
		$total_tweets = count( $json_payload );
		$max_pages = ceil( $total_tweets / $tweets_per_page );

		$data = (object) array(
			'message' => $month_name . ' ' . $year,
			'year' => $year,
			'month' => $month_name,
			'total_tweets' => $total_tweets,
			'tweets' => count( $tweets ),
			'page' => $page,
			'max_pages' => $max_pages,
		);
		wp_send_json_success( $data );
	}

	public function is_dir_empty( $dir ) {
		if ( ! is_readable($dir) ) {
			return NULL;
		}
		return ( count( scandir( $dir ) ) == 2 );
	}

	public function test() {
		// $this->test_import_api_tweets();
		// $this->test_import_archive_js();
		$this->test_import_latest_tweets();
		// $this->test_detailed_user_refresh();
	}

	public function test_import_archive_js() {
		$file_name = '2016_08.js';
		$path = $this->upload_destination . 'data/js/tweets/' . $file_name;
		$full_file = file_get_contents( $path );
		$json_string = substr( $full_file, strpos( $full_file, "\n" ) + 1 );
		$json_payload = json_decode( $json_string );
		// $json_payload = array_slice( $json_payload, 0, 200 );

		foreach ( $json_payload as $index => $tweet ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet( $tweet );
			$tweet_import->save();
		}
		echo 'done!';
		return;

		// For 2011_10.js
		$examples = array(
			'Retweet' => 4,
			'Reply' => 0,
			'URL' => 12,
		);

		// For 2016_08.js
		$examples = array(
			'Image' => 15,
			'Video' => 10,
			'GIF' => 53,
		);
		foreach ( $examples as $label => $i ) {
			// $tweet = new Tweet_Archiver_Import_Tweet( $json_payload[ $i ] );
			// $tweet->save();
			echo '<h2>' . $label . '</h2>';

			echo '<xmp>';
			var_dump( $json_payload[ $i ] );
			echo '</xmp>';
		}

		// echo '<xmp>';
		// var_dump( $json_payload );
		// echo '</xmp>';
	}

	public function test_import_api_tweets() {
		// App credentials
		// (must be in this order)
		$app = array(
			'consumer_key' => 'iimTLSxrnjtNO5M7XOJr9lDdV',
			'consumer_secret' => '2vfK9amYVLUEFe9xuF8JMeIlWuHxW6z2ajVKSG3zgCSeRk35xg',
			'access_token' => '64833-Rmh5dh1SYqD8ZpZeT2aQA4akY3aV1la27PV4pUOpH5NN',
			'access_token_secret' => 'Pgg3Za3RRRkFBZDe2gbX3Ef5GSXPNgdrVlNBqNESlx1pQ',
		);
		$tw = TwitterWP::start( $app );
		// $tweets = $tw->get_tweets( $user = 'kingkool68', $count = 40 );
		$args = array(
			'screen_name' => 'kingkool68',
			'contributor_details' => true,
			'include_rts' => 1,
			'exclude_replies' => false,
			'since_id' => '782749516361568256',
		);
		$tweets = $tw->token_endpoint( 'statuses/user_timeline.json', $args );

		foreach ( $tweets as $tweet ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet( $tweet );
			$tweet_import->save();
		}

	}

	public function test_detailed_media_refresh() {
		Tweet_Archiver_Import_Tweet::download_detailed_media();
	}

	public function test_detailed_user_refresh() {
		Tweet_Archiver_Import_Tweet::download_detailed_users();
	}

	public function test_import_latest_tweets() {
		$obj = new Tweet_Archiver_API_Fetcher;
		$obj->import_the_latest_tweets();
	}

}
new Tweet_Archiver_Importer();
