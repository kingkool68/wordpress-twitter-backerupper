<?php
class Tweet_Archiver_Twitter_Credentials {

	private $settings_page_slug = 'tweet-archiver-twitter-credentials';

	protected $settings_fields = array(
		'twitter-api-key',
		'twitter-api-secret',
		'twitter-access-token',
		'twitter-access-secret',
	);

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		add_options_page(
			'Twitter Settings',
			'Twitter Settings',
			'manage_options',
			$this->settings_page_slug,
			array( $this, 'render_admin_menu_page' )
		);
	}

	public function render_admin_menu_page() {
		// $this->verify_credentials();
		$this->save_option_page();
		$data = $this->get_api_credentials();
		$form_action = add_query_arg( array( 'page' => $this->settings_page_slug ), 'options-general.php' );
		?>
		<div class="wrap">
			<?php $this->show_current_authenticated_user(); ?>
			<h1>Twitter Settings</h1>
			<form action="<?php echo esc_attr( $form_action ); ?>" method="post">
				<p>Go to <a href="https://apps.twitter.com/app/new" target="_blank">https://apps.twitter.com/app/new</a> and register a new app. These are used to access Twitter's API on your behalf.</p>
				<table class="form-table">
					<tbody>
						<tr>
							<td colspan="2">Click on the <u>Keys and Access Tokens</u> tab. Copy the following application settings values:</td>
						</tr>
						<tr>
							<th scope="row"><label for="twitter-api-key">Consumer Key (API Key)</label></th>
							<td><input type="text" id="twitter-api-key" name="twitter-api-key" value="<?php echo esc_attr( $data['twitter-api-key'] ); ?>" size="35"></td>
						</tr>
						<tr>
							<th scope="row"><label for="twitter-api-secret">Consumer Secret (API Secret)</label></th>
							<td><input type="text" id="twitter-api-secret" name="twitter-api-secret" value="<?php echo esc_attr( $data['twitter-api-secret'] ); ?>" size="60"></td>
						</tr>
						<tr>
							<td colspan="2">Under <u>Your Access Token</u> generate an access token by clicking the <u>Create my access token</u> button. Copy the following access token values:</td>
						</tr>
						<tr>
							<th scope="row"><label for="twitter-access-token">Access Token</label></th>
							<td><input type="text" id="twitter-access-token" name="twitter-access-token" value="<?php echo esc_attr( $data['twitter-access-token'] ); ?>" size="60"></td>
						</tr>
						<tr>
							<th scope="row"><label for="twitter-access-secret">Access Token Secret</label></th>
							<td><input type="text" id="twitter-access-secret" name="twitter-access-secret" value="<?php echo esc_attr( $data['twitter-access-secret'] ); ?>" size="60"></td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function save_option_page() {
		$new_data = array();
		$data_has_changed = false;
		$old_data = $this->get_api_credentials();
		$fields = $this->settings_fields;
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$new_data[ $field ] = trim( $_POST[ $field ] );
				if ( isset( $old_data[ $field ] ) && $old_data[ $field ] != $new_data[ $field ] ) {
					$data_has_changed = true;
				}
			}
		}

		if ( $data_has_changed || ! $old_data || empty( $old_data ) ) {
			update_option( 'tweet_archiver_twitter_credentials', $new_data );
			update_option( 'authenticated_twitter_user', '' );
			$this->verify_credentials();
		}
	}

	public function get_api_credentials() {
		$output = array();
		$data = get_option( 'tweet_archiver_twitter_credentials' );
		$fields = $this->settings_fields;
		foreach ( $fields as $field ) {
			$output[ $field ] = '';
			if ( isset( $data[ $field ] ) ) {
				$output[ $field ] = $data[ $field ];
			}
		}
		return $output;
	}

	public function verify_credentials() {
		$twitter = $this->get_twitter_connection( $suppress_errors = true );
		if ( ! $twitter ) {
			return false;
		}
		$creds = $this->get_api_credentials();
		$access_token = $creds['twitter-access-token'];
		$pieces = explode( '-', $access_token );
		$user_id = $pieces[0];
		$args = array(
			'user_id' => $user_id,
		);
		$user = $twitter->token_endpoint( 'users/show.json', $args );
		update_option( 'authenticated_twitter_user', $user->screen_name );
		$importer = new Tweet_Archiver_Import_Tweet();
		$importer->maybe_add_twitter_user( $user );
		$user_post_id = $importer->does_twitter_user_exist( $user->id_str );
	}

	public function show_current_authenticated_user() {
		$user_screen_name = get_option( 'authenticated_twitter_user' );
		if ( ! $user_screen_name ) {
			return;
		}
		$user_post = get_twitter_user( $user_screen_name );
		$description = get_the_twitter_user_description( $user_post );
		$screen_name = $user_post->post_title;
		?>
		<style>
		.authenticated-user {
			background-color: #fff;
			border-radius: 4px;
			padding: 8px;
			margin-bottom: 18px;
			width: 470px;

			-webkit-box-shadow: 0px 3px 5px 1px #ccc;
			-moz-box-shadow: 0px 3px 5px 1px #ccc;
			box-shadow: 0px 3px 5px 1px #ccc;
		}
		.authenticated-user img {
			float: left;
			margin-right: 12px;
			margin-bottom: 4px;
			margin-top: 4px;
			margin-left: 4px;
		}
		.authenticated-user p {
			margin-bottom: 0;
		}
		</style>
		<div class="authenticated-user">
			<a href="https://twitter.com/<?php echo $screen_name; ?>" target="_blank"><?php echo get_the_twitter_user_profile_image( $user_post, array( 64, 64 ) ); ?></a>
			<a href="https://twitter.com/<?php echo $screen_name; ?>" target="_blank">@<?php echo $screen_name; ?></a>
			<p><?php echo $description; ?></p>
		</div>
		<?php
	}

	public function get_twitter_connection( $suppress_errors = false ) {
		$creds = $this->get_api_credentials();
		$fields = $this->settings_fields;
		foreach ( $fields as $field ) {
			if ( ! isset( $creds[ $field ] ) || empty( $creds[ $field ] ) ) {
				$admin_url = get_admin_url( null, 'options-general.php?page=' . $this->settings_page_slug );
				if ( $suppress_errors ) {
					return false;
				}
				wp_die( 'Missing a required API credential. See the ' . $this->get_twitter_settings_link() . '. Error: <strong>' . $field . '</strong> is missing or empty!' );
			}
		}
		// App credentials
		// (must be in this order)
		$app = array(
			'consumer_key' => $creds['twitter-api-key'],
			'consumer_secret' => $creds['twitter-api-secret'],
			'access_token' => $creds['twitter-access-token'],
			'access_token_secret' => $creds['twitter-access-secret'],
		);
		return TwitterWP::start( $app );
	}

	public function get_twitter_settings_link( $link_text = 'Twitter Settings' ) {
		$admin_url = get_admin_url( null, 'options-general.php?page=' . $this->settings_page_slug );
		return '<a href="' . esc_url( $admin_url ) . '">' . $link_text . '</a>';
	}
}
new Tweet_Archiver_Twitter_Credentials;
