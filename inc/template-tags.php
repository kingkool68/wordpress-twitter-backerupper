<?php
/**
 * Checks if a post object is a Twitter User post type
 * @param  object $post The post object to check
 * @return boolean
 */
function is_twitter_user_post( $post = false ) {
	if ( ! is_object( $post ) ) {
		return false;
	}

	if ( isset( $post->post_type ) && $post->post_type == 'twitter-user' ) {
		return true;
	}

	return false;
}

/**
 * Checks if a post object is a Tweet post type
 * @param  object $post The post object to check
 * @return boolean
 */
function is_tweet_post( $post = false ) {
	if ( ! is_object( $post ) ) {
		return false;
	}

	if ( isset( $post->post_type ) && $post->post_type == 'tweet' ) {
		return true;
	}

	return false;
}

/**
 * Checks if a $post object is a retweet or not
 * @param  integer|object $post WP Post object
 * @return boolean
 */
function is_retweet( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	if ( ! is_tweet_post( $post ) ) {
		return false;
	}
	if ( get_post_meta( $post->ID, 'is_retweet', true ) === '1' ) {
		return true;
	}
	return false;
}

/**
 * Get the full name of the author of the tweet
 * @param  integer|object $post WP Post object of the tweet
 * @return string   The name of the Twitter user of the tweet
 */
function get_the_tweet_full_name( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_retweet() ) {
		return get_post_meta( $post->ID, 'retweet_user_name', true );
	}
	if ( $tweet_user_name = get_post_meta( $post->ID, 'tweet_user_name', true ) ) {
		return $tweet_user_name;
	}
}

/**
 * Display the full name of the author of the tweet
 * @return string  The name of the Twitter user of the tweet
 */
function the_tweet_full_name() {
	$full_name = get_the_tweet_full_name();
	$full_name = apply_filters( 'the_tweet_full_name', $full_name );
	echo $full_name;
}

/**
 * Get the username of the author of the tweet
 * @param  integer|object $post WP Post object of the tweet
 */
function get_the_tweet_username( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( $rt_username = get_post_meta( $post->ID, 'retweet_user_screen_name', true ) ) {
		return $rt_username;
	}
	if ( $tweet_username = get_post_meta( $post->ID, 'tweet_user_screen_name', true ) ) {
		return $tweet_username;
	}
}

/**
 * Display the username of the author of the tweet
 */
function the_tweet_username () {
	$username = get_the_tweet_username();
	$username = apply_filters( 'the_tweet_username', $username );
	echo $username;
}

/**
 * Get the Twitter user term link
 * @param  object|int|string $term The term object, ID, or slug whose link will be retrieved
 * @return string       URL of the Twitter user term
 */
function get_the_tweet_username_link( $term = 0 ) {
	$cache_key = 'the_tweet_username_link_' . $term;
	if ( $link = wp_cache_get( $cache_key ) ) {
		return $link;
	}
	$link = get_term_link( $term, 'twitter-user' );
	if ( is_wp_error( $link ) ) {
		$link = false;
	}
	wp_cache_set( $cache_key, $link );
	return $link;
}

/**
 * Display the Twitter user term link
 */
function the_tweet_username_link() {
	$post = get_post();
	$username_slug = get_the_tweet_username( $post->ID );
	if ( ! $username_slug ) {
		return;
	}
	$username_link = get_the_tweet_username_link( $username_slug );
	$username_link = apply_filters( 'the_tweet_username_link', $username_link, $username_slug );
	echo esc_url( $username_link );
}

/**
 * Get link to the users Twitter profile on Twitter.com
 * @param  integer|object $post WP Post
 * @return string        Twitter profile link
 */
function get_the_twitter_profile_link( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( $username = get_the_tweet_username( $post ) ) {
		return 'https://twitter.com/' . $username;
	}
}

/**
 * Display a link to the users Twitter profile on Twitter.com
 */
function the_twitter_profile_link() {
	$link = get_the_twitter_profile_link();
	$username = get_the_tweet_username();
	$link = apply_filters( 'the_twitter_profile_link', $link, $username );
	echo esc_url( $link );
}

/**
 * Get the full name of a Twitter user being replied to
 * @param  integer|object $post WP Post
 * @return string        Full name of Twitter user being replied to
 */
function get_the_tweet_reply_full_name( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( $reply_full_name = get_post_meta( $post->ID, 'reply_user_name', true ) ) {
		return $reply_full_name;
	}
}

/**
 * Display the full name of a Twitter user being replied to
 */
function the_tweet_reply_full_name() {
	$full_name = get_the_tweet_reply_full_name();
	$full_name = apply_filters( 'the_tweet_reply_full_name', $full_name );
	echo $full_name;
}

/**
 * Get permalink of Tweet being replied to
 * @param  integer|object $post WP Post
 * @return string        URL of Tweet being replied to
 */
function get_the_tweet_reply_permalink( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( $reply_permalink = get_post_meta( $post->ID, 'reply_permalink', true ) ) {
		return $reply_permalink;
	}
}

/**
 * Display permalink of Tweet being replied to
 */
function the_tweet_reply_permalink() {
	$reply_permalink = get_the_tweet_reply_permalink();
	$reply_permalink = apply_filters( 'the_tweet_reply_permalink', $reply_permalink );
	echo esc_url( $reply_permalink );
}

/**
 * Get Twitter ID of a tweet
 * @param  integer|object $post WP Post
 * @return string        Twitter ID
 */
function get_the_tweet_id( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( $id = get_post_meta( $post->ID, 'retweet_id', true ) ) {
		return $id;
	}
	if ( $id = get_post_meta( $post->ID, 'tweet_id', true ) ) {
		return $id;
	}
}

/**
 * Display Twitter ID of a tweet
 */
function the_tweet_id() {
	$id = get_the_tweet_id();
	$id = apply_filters( 'the_tweet_id', $id );
	return $id;
}

/**
 * Get Twitter reply intent URL
 * @param  string|ID $post_id Post ID of Tweet
 * @return string          URL of reply intent
 */
function get_the_reply_intenet_url( $post_id = null ) {
	$tweet_id = get_the_tweet_id( $post_id );
	return 'https://twitter.com/intent/tweet?in_reply_to=' . $tweet_id;
}

/**
 * Display Twitter reply intent URL
 */
function the_reply_intent_url() {
	$url = get_the_reply_intenet_url();
	$url = apply_filters( 'the_reply_intent_url', $url );
	echo esc_url( $url );
}

/**
 * Get Twitter retweet intent URL
 * @param  string|ID $post_id Post ID of Tweet
 * @return string          URL of retweet intent
 */
function get_the_retweet_intenet_url( $post_id = null ) {
	$tweet_id = get_the_tweet_id( $post_id );
	return 'https://twitter.com/intent/retweet?tweet_id=' . $tweet_id;
}

/**
 * Display Twitter retweet intent URL
 */
function the_retweet_intent_url() {
	$url = get_the_retweet_intenet_url();
	$url = apply_filters( 'the_retweet_intent_url', $url );
	echo esc_url( $url );
}

/**
 * Get Twitter like intent URL
 * @param  string|ID $post_id Post ID of Tweet
 * @return string          URL of like intent
 */
function get_the_like_intenet_url( $post_id = null ) {
	$tweet_id = get_the_tweet_id( $post_id );
	return 'https://twitter.com/intent/like?tweet_id=' . $tweet_id;
}

/**
 * Display Twitter like intent URL
 */
function the_like_intent_url() {
	$url = get_the_like_intenet_url();
	$url = apply_filters( 'the_like_intent_url', $url );
	echo esc_url( $url );
}

/**
 * Get Twitter.com permalink of a Tweet
 * @param  integer|object $post WP Post
 * @return string        URL of Twitter.com permalink of a Tweet
 */
function get_the_tweet_permalink( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	return $post->guid;
}

/**
 * Display Twitter.com permalink of a Tweet
 */
function the_tweet_permalink() {
	$url = get_the_tweet_permalink();
	$url = apply_filters( 'the_tweet_permalink', $url );
	echo esc_url( $url );
}

/**
 * Get the date of the tweet
 * @param  integer|object $post WP Post
 * @return string        Formatted date string of the tweet
 */
function get_the_tweet_date( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}

	$date_format = get_option('date_format');
	$time_format = get_option('time_format');
	$format = $date_format . ' ' . $time_format;

	if ( is_retweet() ) {
		$datetime = get_post_meta( $post->ID, 'retweet_date', true);
		$datetime_string = date( 'Y-m-d H:i:s', strtotime( $datetime ) );
		return get_date_from_gmt( $datetime_string, $format );
	}

	return mysql2date( $format, $post->post_date );
}

/**
 * Display the date of the tweet
 */
function the_tweet_date() {
	$date = get_the_tweet_date();
	$date = apply_filters( 'the_tweet_date', $date );
	echo $date;
}

/**
 * Get the profile image associated with a Tweet
 * @param  string|array Image size. Accepts any valid image size, or an array of width and height values in pixels (in that order)
 * @param  string|array  $attr Attributes for the image markup
 * @return string       HTML img element or empty string on failure
 */
function get_the_tweet_profile_image( $size = 'thumbnail', $attr = array(), $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$username = get_the_tweet_username( $post->ID );
	$args = array(
		'post_type' => 'twitter-user',
		'meta_query' => array(
			array(
				'key' => 'twitter_user_screen_name',
				'value' => $username,
			),
		),
		'no_found_rows' => true,
		'update_post_term_cache' => false,
		'fields' => 'ids',
	);
	$user_posts = new WP_Query( $args );
	if ( ! isset( $user_posts->posts[0] ) ) {
		return;
	}
	$user_post_id = $user_posts->posts[0];
	$featured_image_id = get_post_thumbnail_id( $user_post_id );
	$attr = array(
		'class' => 'avatar',
	);
	$html = wp_get_attachment_image( $featured_image_id, $size, false, $attr );
	return $html;
}

/**
 * Display the profile image associated with a Tweet
 * @param  string|array Image size. Accepts any valid image size, or an array of width and height values in pixels (in that order)
 * @param  string|array  $attr Attributes for the image markup
 */
function the_tweet_profile_image( $size = array( 48, 48 ), $attr = array() ) {
	$html = get_the_tweet_profile_image( $size, $attr );
	$html = apply_filters( 'the_tweet_profile_image', $html );
	echo $html;
}

/**
 * Get a Twitter User post
 * @param  string|int|object $val Post ID, Twitter Username, or WP Post
 * @return object      Twitter User WP Post
 */
function get_twitter_user( $val = '' ) {
	if ( is_twitter_user_post( $val ) ) {
		return $val;
	}

	if ( is_tweet_post( $val ) ) {
		$val = get_the_tweet_username( $val );
	}
	// Assume a screen name string
	$meta_key = 'twitter_user_screen_name';
	if ( is_numeric( $val ) ) {
		// Assume this is a User ID
		$meta_key = 'twitter_user_id';
	}

	$args = array(
		'post_type' => 'twitter-user',
		'meta_query' => array(
			array(
				'key' => $meta_key,
				'value' => strval( $val ),
			),
		),
		'no_found_rows' => true,
		'posts_per_page' => 1,
		'update_post_term_cache' => false,
	);
	$user_posts = new WP_Query( $args );
	if ( ! isset( $user_posts->posts[0] ) ) {
		return false;
	}
	return $user_posts->posts[0];
}

/**
 * Get Twitter User's description
 * @param  integer|object $post WP Post object
 * @return string        Twitter User's description
 */
function get_the_twitter_user_description( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	return $post->post_content;
}

/**
 * Display Twitter User's description
 * @param  integer|object $post WP Post object
 */
function the_twitter_user_description( $user_val = null ) {
	$description = get_the_twitter_user_description( $user_val );
	$description = apply_filters( 'the_twitter_user_description', $description );
	$description = apply_filters( 'the_content', $description );
	echo $description;
}

/**
 * Get a Twitter Users profile image
 * @param  integer|object $post WP Post
 * @param  string|array Image size. Accepts any valid image size, or an array of width and height values in pixels (in that order)
 * @param  string|array  $attr Attributes for the image markup
 * @return string       HTML img element or empty string on failure
 */
function get_the_twitter_user_profile_image( $post = 0, $size = 'thumbnail', $attr = array() ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	$user_post = get_twitter_user( $post );
	$user_post_id = $user_post->ID;
	$featured_image_id = get_post_thumbnail_id( $user_post_id );
	$attr = array(
		'class' => 'avatar',
	);
	$html = wp_get_attachment_image( $featured_image_id, $size, false, $attr );
	return $html;
}

/**
 * Display a Twitter Users profile image
 * @param  string|array Image size. Accepts any valid image size, or an array of width and height values in pixels (in that order)
 * @param  string|array  $attr Attributes for the image markup
 */
function the_twitter_user_profile_image( $size = array( 48, 48 ), $attr = array(), $user = null ) {
	$html = get_the_twitter_user_profile_image( $user, $size, $attr );
	$html = apply_filters( 'the_twitter_user_profile_image', $html );
	echo $html;
}

/**
 * Get Twitter User's username
 * @param  integer|object $post WP Post object
 * @return string        Twitter User's username
 */
function get_the_twitter_user_username( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	return $post->post_title;
}

function the_twitter_user_username( $user = 0 ) {
	$username = get_the_twitter_user_username( $user );
	$username = apply_filters( 'the_twitter_user_username', $username );
	echo $username;
}

function get_the_twitter_user_name( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	return get_post_meta( $post->ID, 'twitter_user_name', true );
}

function the_twitter_user_name( $user ) {
	$name = get_the_twitter_user_name( $user );
	$name = apply_filters( 'the_twitter_user_name', $name );
	echo $name;
}

function get_the_twitter_user_location( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	return get_post_meta( $post->ID, 'twitter_user_location', true );
}

function the_twitter_user_location( $user = 0 ) {
	$location = get_the_twitter_user_location( $user );
	$location = apply_filters( 'the_twitter_user_location', $location );
	echo $location;
}

function get_tco_expanded_url( $tco_url = '' ) {
	if ( $term = get_term_by( 'name', $tco_url, 'tweet-link' ) ) {
		return $term->description;
	}
	return $tco_url;
}

function get_the_twitter_user_url( $post = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	$url = get_post_meta( $post->ID, 'twitter_user_url', true );
	if ( ! $url ) {
		return;
	}
	$url = get_tco_expanded_url( $url );
	return $url;
}

function the_twitter_user_url( $user = 0 ) {
	$url = get_the_twitter_user_url( $user );
	$url = apply_filters( 'the_twitter_user_url', $url );
	echo esc_url( $url );
}

function get_the_twitter_user_join_date( $post = 0, $format = '', $gmt = false ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return;
	}
	if ( is_tweet_post( $post ) ) {
		$post = get_twitter_user( $post );
	}
	$date_str = $post->post_date;
	if ( $gmt ) {
		$date_str = $post->post_date_gmt;
	}
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}
	return mysql2date( $format, $date_str );
}

function the_twitter_user_join_date( $user = 0, $format = null, $gmt = false ) {
	$date = get_the_twitter_user_join_date( $user, $format, $gmt );
	$date = apply_filters( 'the_twitter_user_join_date', $date );
	echo $date;
}

function get_the_tweet_media( $size = 'large', $post = 0 ) {
	$post = get_post( $post );
	$args = array(
		'post_type' => 'attachment',
		'posts_per_page' => -1,
		'orderby' => 'ID', // Videos always have a higher ID than images :p
		'order' => 'ASC',
		'post_parent' => $post->ID,

		// For performance
		'no_found_rows' => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
	);
	$attachment_posts = get_children( $args );
	if ( empty( $attachment_posts ) )  {
		return;
	}
	$media_attachments = array();
	foreach ( $attachment_posts as $attachment ) {
		$key = $attachment->post_title;
		$ID = $attachment->ID;
		$type = $attachment->post_mime_type;
		$type = explode( '/', $type )[0];
		$media_attachments[ $key ] = (object) array(
			'ID' => $ID,
			'type' => $type,
		);
	}

	foreach ( $media_attachments as $twitter_permalink => $media ) {
		$media_metadata = get_post_meta( $media->ID, '_wp_attachment_metadata', true );
		switch( $media->type ) {
			case 'image':
				$html = wp_get_attachment_image( $media->ID, $size, false );
				echo $html;
			break;

			case 'video':
				$video_src = wp_get_attachment_url( $media->ID );
				$dimensions = Tweet_Archiver_Helpers::scale_down_dimensions( array(
					'width' => $media_metadata['width'],
					'height' => $media_metadata['height'],
					'max_width' => 506,
					'max_height' => 506,
				) );
				$args = array(
					'src' => $video_src,
					'width' => $dimensions['width'],
					'height' => $dimensions['height'],
				);
				if ( is_singular() ) {
					$args['autoplay'] = '1';
				}
				if ( get_post_meta( $media->ID, 'is_animated_gif', true ) ) {
					$args['loop'] = true;
				}
				$html = wp_video_shortcode( $args );
				echo '<div class="tweet-video">' . $html . '</div>';
			break;
		}
	}
}

function the_tweet_media( $size = 'large' ) {
	$media = get_the_tweet_media( $size );
	$media = apply_filters( 'the_tweet_media', $media );
	echo $media;
}
