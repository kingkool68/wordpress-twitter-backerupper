<?php
class Tweet_Archiver_Content_Types {
	public function __construct() {
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'current_screen', array( $this, 'setup_tweet_terms' ) );
		add_action( 'tweet-link_edit_form_fields', array( $this, 'add_tweet_link_term_fields' ), 10, 1 );
		add_filter( 'manage_edit-tweet-link_columns', array( $this, 'filter_tweet_link_column_headers' ), 10, 3 );
		// add_filter( 'manage_tweet-link_custom_column', array( $this, 'filter_tweet_link_columns' ), 10, 3 );
		add_filter( 'term_description', array( $this, 'filter_tweet_link_columns' ), 10, 3 );
	}

	public function register_content_types() {
		$labels = array(
			'name'                  => 'Tweets',
			'singular_name'         => 'Tweet',
			'menu_name'             => 'Tweets',
			'name_admin_bar'        => 'Tweet',
			'archives'              => 'Tweet Archives',
			'parent_item_colon'     => 'Parent Tweet:',
			'all_items'             => 'All Tweets',
			'add_new_item'          => 'Add New Tweet',
			'add_new'               => 'Add New',
			'new_item'              => 'New Tweet',
			'edit_item'             => 'Edit Tweet',
			'update_item'           => 'Update Tweet',
			'view_item'             => 'View Tweet',
			'search_items'          => 'Search Tweet',
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into tweet',
			'uploaded_to_this_item' => 'Uploaded to this tweet',
			'items_list'            => 'Tweets list',
			'items_list_navigation' => 'Tweets list navigation',
			'filter_items_list'     => 'Filter tweets list',
		);
		$args = array(
			'label'                 => 'Tweet',
			'description'           => 'Post Type Description',
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-twitter',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		$args = apply_filters( 'twitter_archive_post_type_args', $args, 'tweet' );
		register_post_type( 'tweet', $args );

		$labels = array(
			'name'                  => 'Twitter Users',
			'singular_name'         => 'Twitter User',
			'menu_name'             => 'Twitter Users',
			'name_admin_bar'        => 'Twitter User',
			'archives'              => 'Twitter User Archives',
			'parent_item_colon'     => 'Parent Twitter User:',
			'all_items'             => 'All Twitter Users',
			'add_new_item'          => 'Add New Twitter User',
			'add_new'               => 'Add New',
			'new_item'              => 'New Twitter User',
			'edit_item'             => 'Edit Twitter User',
			'update_item'           => 'Update Twitter User',
			'view_item'             => 'View Twitter User',
			'search_items'          => 'Search Twitter User',
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into twitter user',
			'uploaded_to_this_item' => 'Uploaded to this twitter user',
			'items_list'            => 'Twitter users list',
			'items_list_navigation' => 'Twitter users list navigation',
			'filter_items_list'     => 'Filter twitter users list',
		);
		$args = array(
			'label'                 => 'Twitter User',
			'description'           => 'Post Type Description',
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', ),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-twitter',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'page',
		);
		$args = apply_filters( 'twitter_archive_post_type_args', $args, 'twitter-user' );
		register_post_type( 'twitter-user', $args );

		$labels = array(
			'name'                       => 'Types',
			'singular_name'              => 'Type',
			'menu_name'                  => 'Types',
			'all_items'                  => 'All Types',
			'parent_item'                => 'Parent Type',
			'parent_item_colon'          => 'Parent Type:',
			'new_item_name'              => 'New Type Name',
			'add_new_item'               => 'Add New Type',
			'edit_item'                  => 'Edit Type',
			'update_item'                => 'Update Type',
			'view_item'                  => 'View Type',
			'separate_items_with_commas' => 'Separate types with commas',
			'add_or_remove_items'        => 'Add or remove types',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Types',
			'search_items'               => 'Search Types',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No types',
			'items_list'                 => 'Types list',
			'items_list_navigation'      => 'Types list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'tweet-type' );
		register_taxonomy( 'tweet-type', array( 'tweet' ), $args );

		$labels = array(
			'name'                       => 'Hashtags',
			'singular_name'              => 'Hashtag',
			'menu_name'                  => 'Hashtags',
			'all_items'                  => 'All Hashtags',
			'parent_item'                => 'Parent Hashtag',
			'parent_item_colon'          => 'Parent Hashtag:',
			'new_item_name'              => 'New Hashtag Name',
			'add_new_item'               => 'Add New Hashtag',
			'edit_item'                  => 'Edit Hashtag',
			'update_item'                => 'Update Hashtag',
			'view_item'                  => 'View Hashtag',
			'separate_items_with_commas' => 'Separate hashtags with commas',
			'add_or_remove_items'        => 'Add or remove hashtags',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Hashtags',
			'search_items'               => 'Search Hashtags',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No hashtags',
			'items_list'                 => 'Hashtags list',
			'items_list_navigation'      => 'Hashtags list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'hashtag' );
		register_taxonomy( 'hashtag', array( 'tweet' ), $args );

		$labels = array(
			'name'                       => 'URLs',
			'singular_name'              => 'URL',
			'menu_name'                  => 'URLs',
			'all_items'                  => 'All URLs',
			'parent_item'                => 'Parent URL',
			'parent_item_colon'          => 'Parent URL:',
			'new_item_name'              => 'New URL Name',
			'add_new_item'               => 'Add New URL',
			'edit_item'                  => 'Edit URL',
			'update_item'                => 'Update URL',
			'view_item'                  => 'View URL',
			'separate_items_with_commas' => 'Separate URLs with commas',
			'add_or_remove_items'        => 'Add or remove URLs',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular URLs',
			'search_items'               => 'Search URLs',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No URLs',
			'items_list'                 => 'URLs list',
			'items_list_navigation'      => 'URLs list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'tweet-link' );
		register_taxonomy( 'tweet-link', array( 'tweet', 'twitter-user', 'attachment' ), $args );

		$labels = array(
			'name'                       => 'Sources',
			'singular_name'              => 'Source',
			'menu_name'                  => 'Sources',
			'all_items'                  => 'All Sources',
			'parent_item'                => 'Parent Source',
			'parent_item_colon'          => 'Parent Source:',
			'new_item_name'              => 'New Source Name',
			'add_new_item'               => 'Add New Source',
			'edit_item'                  => 'Edit Source',
			'update_item'                => 'Update Source',
			'view_item'                  => 'View Source',
			'separate_items_with_commas' => 'Separate sources with commas',
			'add_or_remove_items'        => 'Add or remove sources',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Sources',
			'search_items'               => 'Search Sources',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No sources',
			'items_list'                 => 'Sources list',
			'items_list_navigation'      => 'Sources list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'tweet-source' );
		register_taxonomy( 'tweet-source', array( 'tweet' ), $args );

		$labels = array(
			'name'                       => 'Mentions',
			'singular_name'              => 'Mention',
			'menu_name'                  => 'Mentions',
			'all_items'                  => 'All Mentions',
			'parent_item'                => 'Parent Mention',
			'parent_item_colon'          => 'Parent Mention:',
			'new_item_name'              => 'New Mention Name',
			'add_new_item'               => 'Add New Mention',
			'edit_item'                  => 'Edit Mention',
			'update_item'                => 'Update Mention',
			'view_item'                  => 'View Mention',
			'separate_items_with_commas' => 'Separate mentions with commas',
			'add_or_remove_items'        => 'Add or remove mentions',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Mentions',
			'search_items'               => 'Search Mentions',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No mentions',
			'items_list'                 => 'Mentions list',
			'items_list_navigation'      => 'Mentions list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'mention' );
		register_taxonomy( 'mention', array( 'tweet', 'twitter-user' ), $args );

		$labels = array(
			'name'                       => 'Users',
			'singular_name'              => 'User',
			'menu_name'                  => 'Users',
			'all_items'                  => 'All Users',
			'parent_item'                => 'Parent User',
			'parent_item_colon'          => 'Parent User:',
			'new_item_name'              => 'New User Name',
			'add_new_item'               => 'Add New User',
			'edit_item'                  => 'Edit User',
			'update_item'                => 'Update User',
			'view_item'                  => 'View User',
			'separate_items_with_commas' => 'Separate users with commas',
			'add_or_remove_items'        => 'Add or remove users',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Users',
			'search_items'               => 'Search Users',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No users',
			'items_list'                 => 'Users list',
			'items_list_navigation'      => 'Users list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => false,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'twitter-user' );
		register_taxonomy( 'twitter-user', array( 'tweet', 'twitter-user' ), $args );

		$labels = array(
			'name'                       => 'Media Types',
			'singular_name'              => 'Media Type',
			'menu_name'                  => 'Media Types',
			'all_items'                  => 'All Media Types',
			'parent_item'                => 'Parent Media Type',
			'parent_item_colon'          => 'Parent Media Type:',
			'new_item_name'              => 'New Media Type Name',
			'add_new_item'               => 'Add New Media Type',
			'edit_item'                  => 'Edit Media Type',
			'update_item'                => 'Update Media Type',
			'view_item'                  => 'View Media Type',
			'separate_items_with_commas' => 'Separate media types with commas',
			'add_or_remove_items'        => 'Add or remove media types',
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular Media Types',
			'search_items'               => 'Search Media Types',
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No media types',
			'items_list'                 => 'Media types list',
			'items_list_navigation'      => 'Media types list navigation',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		$args = apply_filters( 'twitter_archive_tax_args', $args, 'twitter-user' );
		register_taxonomy( 'media-type', array( 'attachment' ), $args );
	}

	public static function setup_tweet_terms() {
		// This should only fire on the plugin.php screen
		$screen = get_current_screen();
		if ( $screen->base != 'plugins' ) {
			return;
		}

		$terms = array(
			'Regular' => 'A plain old text Tweet.',
			'Mention' => 'A Tweet containing another user\'s Twitter username.',
			'Canoe' => 'A Twitter canoe is a conversation with more than three participants.',
			'Reply' => 'A Tweet that begins with another user\'s @username and is in reply to one of their Tweets.',
			'Public Reply' => 'A reply with another character (usually a period) in front to bypass Twitter\'s default reply behavior.',
			'Retweet' => 'A Tweet shared publicly with your followers.',
			'Modified Tweet' => 'Like a retweet, but the author wants to let you know it’s not a faithful reproduction of the original tweet.',
			'Quote' => 'A Retweet with additional commentary.',
			'Has Link' => 'A Tweet that includes at least one URL.',
			'Has Hashtag' => 'A Tweet that has at least one hashtag.',
			'Question' => 'A Tweet with one or more question marks.',
			'Twoosh' => 'A perfect 140 character tweet.',
			'Has Media' => 'A Tweet that has media.',
			'Has Photo' => 'A Tweet that has a photo.',
			'Has Video' => 'A Tweet that has a video.',
			'Has Animated Gif' => 'A Tweet that has an animated GIF.'
		);
		foreach ( $terms as $term => $description ) {
			wp_insert_term( $term, 'tweet-type', array( 'description' => $description ) );
		}
	}

	public function add_tweet_link_term_fields( $term ) {
		$expanded_url = get_term_meta( $term->term_id, 'expanded_url', true );
		if ( ! $expanded_url ) {
			$expanded_url = '';
		}
		?>
		<tr class="form-field term-description-wrap">
			<th scope="row"><label for="expanded-url">Expanded URL</label></th>
			<td>
				<input type="text" name="expanded-url" id="expanded-url" value="<?php echo esc_url( $expanded_url ); ?>">
				<p class="description">The full URL of the t.co link.</p>
			</td>
		</tr>
		<?php
		$last_updated = get_term_meta( $term->term_id, 'last_updated_gmt', true );
		if ( ! $last_updated ) {
			return;
		}
		$proper_date_format = date( 'Y-m-d H:i:s', intval( $last_updated ) );
		$desired_date_format = get_option('date_format') . ' ' . get_option('time_format');
		$last_updated = get_date_from_gmt( $proper_date_format, $desired_date_format );
		?>
		<tr class="form-field term-description-wrap">
			<th scope="row"><label for="last-updated">Last Updated</label></th>
			<td>
				<p id="last-updated"><?php echo $last_updated; ?></p>
			</td>
		</tr>
		<?php
	}

	public function filter_tweet_link_column_headers( $columns ) {
		$columns['name'] = 't.co';
		$columns['description'] = 'URL';
		unset( $columns['slug'] );
		return $columns;
	}

	public function filter_tweet_link_columns( $description, $term_id, $tax ) {
		if ( $tax != 'tweet-link' ) {
			return $description;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->id != 'edit-tweet-link' ) {
			return $description;
		}

		$expanded_url = get_term_meta( $term_id, 'expanded_url', true );
		$display_url = Tweet_Archiver_Helpers::get_display_url( $expanded_url );
		$output = '<a href="' . esc_url( $expanded_url ) . '" title="' . esc_url( $expanded_url ) . '" target="_blank">' . $display_url . '</a>';
		return $output;
	}
}
new Tweet_Archiver_Content_Types;
