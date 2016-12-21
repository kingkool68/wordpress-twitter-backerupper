<?php

use \ForceUTF8\Encoding;

class Tweet_Archiver_Import_Tweet {

	private $tweet;

	private $extracted_data = false;

	public function __construct( $tweet = '' ) {
		$this->set_tweet( $tweet );
	}

	public function set_tweet( $tweet ) {
		$this->tweet = $tweet;
	}

	public function get_tweet() {
		return $this->tweet;
	}

	public function get_retweet() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->retweeted_status ) ) {
			return $tweet->retweeted_status;
		}
		return false;
	}

	public function is_retweet() {
		return ( $this->get_retweet() );
	}

	public function is_modified_tweet() {
		$text = $this->get_text();
		return ( stristr( $text, ' MT ' ) );
	}

	public function is_question() {
		$text = $this->get_text();
		return ( strstr( $text, '?' ) );
	}

	public function get_mentions() {
		$tweet = $this->get_tweet();
		if ( $this->is_retweet() ) {
			$tweet = $this->get_retweet();
		}
		if ( isset( $tweet->entities->user_mentions ) ) {
			return $tweet->entities->user_mentions;
		}
		return array();
	}

	public function is_mention() {
		$mention_count = count( $this->get_mentions() );
		if ( $mention_count == 0 ) {
			return false;
		}
		if ( $this->is_reply() && $mention_count <= 1 ) {
			return false;
		}
		return true;
	}

	public function is_reply() {
		if ( $this->is_retweet() ) {
			return false;
		}
		$extracted_data = $this->get_extracted_data();
		if (
			isset( $extracted_data['mentions_with_indices'][0]['indices'][0] ) &&
			$extracted_data['mentions_with_indices'][0]['indices'][0] == 0
		) {
			return true;
		}

		return false;
	}

	public function is_public_reply() {
		$text = $this->get_text();
		if ( $text[1] != '@' || ! isset( $text[1] ) ) {
			return false;
		}
		return true;
	}

	public function is_canoe() {
		$extracted_data = $this->get_extracted_data();
		$unique_mentions = array_unique( $extracted_data['mentions'] );
		if ( count( $unique_mentions ) >= 3 ) {
			return true;
		}
		return false;
	}

	public function is_quote_tweet() {
		$extracted_data = $this->get_extracted_data();
		if ( empty( $extracted_data['urls'] ) ) {
			return false;
		}

		return false;
	}

	public function does_tweet_exist() {
		global $wpdb;
		$tweet_id = $this->get_tweet_id();
		$query = $wpdb->prepare(
			"SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = 'tweet_id' AND `meta_value` = '%s'",
			$tweet_id
		);
		$existing_post_id = $wpdb->get_var( $query );
		if ( ! is_null( $existing_post_id ) ) {
			return $existing_post_id;
		}

		return false;
	}

	public function does_twitter_user_exist( $user_id = '' ) {
		global $wpdb;
		if ( ! $user_id ) {
			return false;
		}

		$query = $wpdb->prepare(
			"SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = 'twitter_user_id' AND `meta_value` = '%s'",
			$user_id
		);
		$existing_post_id = $wpdb->get_var( $query );
		if ( ! is_null( $existing_post_id ) ) {
			return $existing_post_id;
		}

		return false;
	}

	public function get_text() {
		if ( $rt_text = $this->get_retweet_text() ) {
			return $rt_text;
		}
		return $this->get_tweet_text();
	}

	public function get_tweet_text() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->full_text ) ) {
			return trim( $tweet->full_text );
		}
		return trim( $tweet->text );
	}

	public function get_retweet_text() {
		if ( $rt = $this->get_retweet() ) {
			if ( isset( $tweet->full_text ) ) {
				return trim( $tweet->full_text );
			}
			return trim( $rt->text );
		}
		return false;
	}

	public function get_id() {
		if ( $rt_id = $this->get_retweet_id() ) {
			return $rt_id;
		}
		return $this->get_tweet_id();
	}

	public function get_tweet_id() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->id_str ) ) {
			return $tweet->id_str;
		}
		return 0;
	}

	public function get_retweet_id() {
		$rt = $this->get_retweet();
		if ( $rt && isset( $rt->id_str ) ) {
			return $rt->id_str;
		}
		return 0;
	}

	public function get_date() {
		if ( $rt_date = $this->get_retweet_date() ) {
			return $rt_date;
		}
		return $this->get_tweet_date();
	}

	public function get_tweet_date() {
		$tweet = $this->get_tweet();
		return date( 'Y-m-d H:i:s', strtotime( $tweet->created_at ) );
	}

	public function get_retweet_date() {
		if ( $rt = $this->get_retweet() ) {
			return date( 'Y-m-d H:i:s', strtotime( $rt->created_at ) );
		}
		return false;
	}

	public function get_extracted_data() {
		if ( $this->extracted_data === false ) {
			$tweet_text = $this->get_text();
			$this->extracted_data = Tweet_Archiver_Helpers::extract_text( $tweet_text );
		}
		return $this->extracted_data;
	}

	public function get_retweet_count() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->retweet_count ) ) {
			return $tweet->retweet_count;
		}
		return 0;
	}

	public function get_favorite_count() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->favorite_count ) ) {
			return $tweet->favorite_count;
		}
		return 0;
	}

	public function get_hashtags() {
		$data = $this->get_extracted_data();
		return $data['hashtags'];
	}

	public function get_urls() {
		$tweet = $this->get_tweet();
		$urls = array();
		if ( isset( $tweet->entities->urls ) ) {
			$urls = $tweet->entities->urls;
		}
		if ( isset( $tweet->retweeted_status->entities->urls ) ) {
			$urls = $tweet->retweeted_status->entities->urls;
		}
		return $urls;
	}

	public function get_media() {
		$tweet = $this->get_tweet();
		$output = array();
		$media = false;
		if ( $this->has_media() ) {
			$media = $tweet->entities->media;
		}
		if ( $this->has_extended_media() ) {
			$media = $tweet->extended_entities->media;
		}

		if ( ! $media ) {
			return $output;
		}

		foreach ( $media as $item ) {
			$media_type = '';
			$media_sequence = 0;
			preg_match_all( '/(video|photo)\/(\d+)/i', $item->expanded_url, $matches );
			/*
				Given https://twitter.com/pasql/status/771025751420403712/video/1
				$matches = array(
					0 => array(
						0 => 'video/1',
					),
					1 => array(
						0 => 'video',
					),
					2 => array(
						0 => '1',
					),
				);
			 */
			if ( isset( $matches[1][0] ) ) {
				$media_type = $matches[1][0];
			}
			if ( isset( $matches[2][0] ) ) {
				$media_sequence = intval( $matches[2][0] );
			}
			if ( isset( $item->type ) ) {
				$media_type = $item->type;
			}

			// Look for video data
			$video_duration_millis = 0;
			$video_url = '';
			if ( isset( $item->video_info ) ) {
				$video_info = $item->video_info;
				if ( isset( $video_info->duration_millis ) ) {
					$video_duration_millis = $video_info->duration_millis;
				}

				if ( isset( $video_info->variants ) && is_array( $video_info->variants ) ) {
					$highest_bitrate = -1; // Sometimes the bitrate == 0
					foreach ( $video_info->variants as $variant ) {
						// If we're not dealing with an MP4 video than skip it
						if ( $variant->content_type != 'video/mp4' ) {
							continue;
						}
						if ( isset( $variant->bitrate ) && isset( $variant->url ) && $variant->bitrate > $highest_bitrate ) {
							$video_url = $variant->url;
							$highest_bitrate = $variant->bitrate;
						}
					}
				}
			}

			$url_token = $item->url;
			$url_token = str_replace( 'https://t.co/', '', $url_token );
			$url_token = str_replace( 'http://t.co/', '', $url_token );

			$result = array(
				'id_str' => $item->id_str,
				'media_type' => $media_type,
				'media_sequence' => $media_sequence,
				'expanded_url' => $item->expanded_url,
				'url' => $item->url,
				'url_token' => $url_token,
				'media_url' => $item->media_url_https,
				'video_url' => $video_url,
				'video_duration_millis' => $video_duration_millis,
			);
			$output[] = $result;
		}
		return $output;
	}

	public function has_extended_media() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->extended_entities->media ) && ! empty( $tweet->extended_entities->media ) ) {
			return true;
		}
		return false;
	}

	public function has_media() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->entities->media ) && ! empty( $tweet->entities->media ) ) {
			return true;
		}
		return false;
	}

	public function is_media_animated_gif() {
		$tweet = $this->get_tweet();
		if ( ! $this->has_media() ) {
			return false;
		}
		$media_items = $tweet->entities->media;
		foreach ( $media_items as $media ) {
			$expanded_url = $media->expanded_url;
			preg_match( '/status\/\d+\/photo/i', $expanded_url, $match );
			$media_url_https = $media->media_url_https;
			$video_thumb_pos = strpos( $media_url_https, 'tweet_video_thumb' );
			if ( $match && is_int( $video_thumb_pos ) ) {
				return true;
			}
		}
		return false;
	}

	public function is_media_video() {
		$tweet = $this->get_tweet();
		if ( ! $this->has_media() ) {
			return false;
		}
		$media_items = $tweet->entities->media;
		foreach ( $media_items as $media ) {
			$expanded_url = $media->expanded_url;
			preg_match( '/status\/\d+\/video/i', $expanded_url, $match );
			if ( $match ) {
				return true;
			}
		}
		return false;
	}

	public function is_media_photo() {
		$tweet = $this->get_tweet();
		if ( ! $this->has_media() ) {
			return false;
		}
		$media_items = $tweet->entities->media;
		foreach ( $media_items as $media ) {
			$expanded_url = $media->expanded_url;
			preg_match( '/status\/\d+\/photo/i', $expanded_url, $match );
			if ( $match ) {
				return true;
			}
		}
		return false;
	}

	public function parse_source( $source_html = '' ) {
		$dom = new DOMDocument();
		$dom->loadHTML( $source_html );
		$elem = $dom->getElementsByTagName('a')->item(0);
		return array(
			'name' => $elem->nodeValue,
			'url' => $elem->getAttribute('href'),
		);
	}

	public function get_source() {
		$source = $this->get_retweet_source();
		if ( ! $source ) {
			$source = $this->get_tweet_source();
		}
		return $source;
	}

	public function get_tweet_source() {
		$tweet = $this->get_tweet();
		return $this->parse_source( $tweet->source );
	}

	public function get_retweet_source() {
		$rt = $this->get_retweet();
		if ( ! $rt ) {
			return false;
		}
		return $this->parse_source( $rt->source );
	}

	public function get_permalink() {
		if ( $rt_permalink = $this->get_retweet_permalink() ) {
			return $rt_permalink;
		}
		return $this->get_tweet_permalink();
	}

	public function get_tweet_permalink() {
		$tweet = $this->get_tweet();
		return 'https://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id_str;
	}

	public function get_retweet_permalink() {
		$tweet = $this->get_retweet();
		if ( ! $tweet ) {
			return false;
		}
		return 'https://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id_str;
	}

	public function get_reply_id() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->in_reply_to_status_id_str ) ) {
			return $tweet->in_reply_to_status_id_str;
		}
		return 0;
	}

	public function get_reply_user_id() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->in_reply_to_user_id ) ) {
			return $tweet->in_reply_to_user_id;
		}
		return 0;
	}

	public function get_reply_user_screen_name() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->in_reply_to_screen_name ) ) {
			return $tweet->in_reply_to_screen_name;
		}
		return false;
	}

	public function get_reply_user_name() {
		$tweet = $this->get_tweet();
		if ( isset( $tweet->entities->user_mentions[0] ) ) {
			$mention = $tweet->entities->user_mentions[0];
			return $mention->name;
		}
		return false;
	}

	public function get_reply_permalink() {
		$reply_id = $this->get_reply_id();
		$reply_screen_name = $this->get_reply_user_screen_name();
		if ( ! $reply_id || ! $reply_screen_name ) {
			return false;
		}
		return 'https://twitter.com/' . $reply_screen_name . '/status/' . $reply_id;
	}

	public function get_user( $field = false ) {
		if ( $rt_user = $this->get_retweet_user( $field ) ) {
			return $rt_user;
		}
		return $this->get_tweet_user( $field );
	}

	public function get_tweet_user( $field = false ) {
		$output = false;
		$tweet = $this->get_tweet();
		if ( isset( $tweet->user ) ) {
			$output = $tweet->user;
			if ( $field && isset( $output->{$field} ) ) {
				return $output->{$field};
			}
		}
		return $output;
	}

	public function get_retweet_user( $field = false ) {
		$output = false;
		$rt = $this->get_retweet();
		if ( $rt && isset( $rt->user ) ) {
			$output = $rt->user;
			if ( $field && isset( $output->{$field} ) ) {
				return $output->{$field};
			}
		}
		return $output;
	}

	public function get_tweet_character_count() {
		$text = $this->get_text();
		return strlen( utf8_decode( $text ) );
	}

	public function categorize_tweet() {
		$output = array();

		if ( $this->is_canoe() ) {
			$output[] = 'canoe';
		}

		if ( $this->is_retweet() ) {
			$output[] = 'retweet';
		}

		if ( $this->is_modified_tweet() ) {
			$output[] = 'modified-tweet';
		}

		if ( $this->is_quote_tweet() ) {
			$output[] = 'quote-tweet';
		}

		if ( $this->is_reply() ) {
			$output[] = 'reply';
		}

		if ( $this->is_public_reply() ) {
			$output[] = 'public-reply';
		}

		if ( $this->is_mention() ) {
			$output[] = 'mention';
		}

		if ( empty( $output ) ) {
			$output[] = 'regular';
		}

		if ( $this->get_urls() ) {
			$output[] = 'has-link';
		}

		if ( $this->get_hashtags() ) {
			$output[] = 'has-hashtag';
		}

		if ( $this->is_question() ) {
			$output[] = 'question';
		}

		if ( $this->get_tweet_character_count() == 140 ) {
			$output[] = 'twoosh';
		}

		if ( $this->has_media() ) {
			$output[] = 'has-media';

			if ( $this->is_media_animated_gif() ) {
				$output[] = 'has-animated-gif';
			}
			if ( $this->is_media_video() ) {
				$output[] = 'has-video';
			}
			if ( $this->is_media_photo() ) {
				$output[] = 'has-photo';
			}
		}

		return $output;
	}

	public function maybe_tag_sources( $post_id = 0 ) {
		$source = $this->get_source();
		if ( empty( $source ) ) {
			return;
		}
		$name = $source['name'];
		$tax = 'tweet-source';
		$term = term_exists( $name, $tax );
		if ( ! $term ) {
			$term = wp_insert_term( $name, $tax );
			add_term_meta( $term['term_id'], 'source_url', $source['url'] );
		}
		wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax );
	}

	public function maybe_tag_types( $post_id = 0 ) {
		$tweet_types = $this->categorize_tweet(); // Array of tweet-type slugs
		if ( empty( $tweet_types ) ) {
			return;
		}
		$tax = 'tweet-type';
		wp_set_object_terms( $post_id, $tweet_types, $tax, $append = true );
	}

	public function maybe_tag_hashtags( $post_id = 0 ) {
		 $hashtags = $this->get_hashtags(); // Array of hashtag names
		 if ( empty( $hashtags ) ) {
			 return;
		 }
		 $tax = 'hashtag';
		 foreach ( $hashtags as $hashtag ) {
			 $term = term_exists( $hashtag, $tax );
	 		if ( ! $term ) {
	 			$term = wp_insert_term( $hashtag, $tax );
	 		}
	 		$result = wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax, $append = true );
		 }
	}

	public function maybe_tag_urls( $post_id = 0, $urls = null ) {
		if ( ! $urls ) {
			$urls = $this->get_urls(); // Object of tweet-link
		}
		if ( empty( $urls ) || ! is_array( $urls ) ) {
			return;
		}
		$tax = 'tweet-link';
		foreach ( $urls as $link ) {
			$url = $link->url;
			$expanded_url = $link->expanded_url;
			$term = term_exists( $url, $tax );
			if ( ! $term ) {
				$args = array(
					'description' => $expanded_url,
				);
				$term = wp_insert_term( $url, $tax, $args );
				add_term_meta( $term['term_id'], 'expanded_url', $expanded_url );
				$current_time = current_time( 'timestamp', $gmt = 1 );
				add_term_meta( $term['term_id'], 'last_updated_gmt', $current_time );
			}
			wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax, $append = true );
		}
	}

	public function maybe_untag_urls($post_id = 0, $urls = null ) {
		if ( ! $urls ) {
			$urls = $this->get_urls(); // Object of tweet-link
		}
		if ( empty( $urls ) || ! is_array( $urls ) ) {
			return;
		}
		$tax = 'tweet-link';
		foreach ( $urls as $link ) {
			$url = $link->url;
			$term = term_exists( $url, $tax );
			if ( ! $term ) {
				continue;
			}
			wp_remove_object_terms( $post_id, intval( $term['term_id'] ), $tax );
		}
	}

	public function maybe_tag_mentions( $post_id = 0 ) {
		$mentions = $this->get_mentions();
		if ( empty( $mentions ) ) {
			return;
		}
		$tax = 'mention';
		foreach ( $mentions as $mention ) {
			$id = $mention->id;
			$name = $mention->name;
			$screen_name = $mention->screen_name;
			$term = term_exists( $screen_name, $tax );
			if ( ! $term ) {
				$term = wp_insert_term( $screen_name, $tax );
			}
			wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax, $append = true );
			$this->maybe_add_twitter_user( $mention );
		}
	}

	public function maybe_tag_users( $post_id = 0 ) {
		$user = $this->get_user();
		if ( empty( $user ) ) {
			return;
		}
		$tax = 'twitter-user';
		$id = $user->id;
		$name = $user->name;
		$screen_name = $user->screen_name;
		$profile_image_url = $user->profile_image_url_https;
		$verified = $user->verified;
		$term = term_exists( $screen_name, $tax );
		if ( ! $term ) {
			$term = wp_insert_term( $screen_name, $tax );
		}
		wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax );
		$this->maybe_add_twitter_user( $user );
	}

	public function maybe_add_twitter_user( $user = null ) {
		if ( ! $user || ! is_object( $user ) ) {
			return false;
		}

		if ( ! isset( $user->id_str ) ) {
			return false;
		}

		$is_mention = false;
		$is_user = false;
		if ( isset( $user->indices ) ) {
			$is_mention = true;
		}
		if ( isset( $user->verified ) ) {
			$is_user = true;
		}

		$user_post_id = 0;
		if ( $user_id = $this->does_twitter_user_exist( $user->id_str ) ) {
			$user_post_id = intval( $user_id );
		}

		$current_time = current_time( 'timestamp', $gmt = 1 );
		if ( ! $user_post_id ) {
			// No post id so we need to insert one
			$new_user = array(
				'post_type' => 'twitter-user',
				'post_status' => 'publish',
				'post_title' => $user->screen_name,
				'post_excerpt' => $user->name,
				'guid' => 'https://twitter.com/' . $user->screen_name,
			);
			$user_post_id = wp_insert_post( $new_user, $wp_error = true );
			if ( is_wp_error( $user_post_id ) ) {
				return false;
			}

			$meta = array(
				'twitter_user_id' => $user->id_str,
				'twitter_user_name' => $user->name,
				'twitter_user_screen_name' => $user->screen_name,
				'check_twitter_user' => 1,
				'last_updated_gmt' => $current_time,
			);

			foreach ( $meta as $key => $value ) {
				update_post_meta( $user_post_id, $key, $value );
			}

		}

		if ( $user_post_id && $is_mention ) {
			// The post exists and it's a mention so there should be nothing new to add
			return true;
		}

		// Common properties:
		// name
		// screen_name
		// id_str

		// User Properties:
		// protected
		// profile_image_url_https
		// verified

		// Download Profile Image
		$profile_image_url = $user->profile_image_url_https;
		$profile_image_url = str_replace( '_normal', '', $profile_image_url );
		$this->download_profile_image( $profile_image_url, $user_post_id );

		$meta = array(
			'twitter_user_profile_image_url' => $profile_image_url,
			'last_updated_gmt' => $current_time,
		);

		// Download Banner Image
		if ( isset( $user->profile_banner_url ) && ! empty( $user->profile_banner_url ) ) {
			$banner_url = $user->profile_banner_url . '/1500x500';
			$banner_attachment_id = $this->download_banner_image( $banner_url, $user_post_id );
			if ( $banner_attachment_id ) {
				$meta['_profile_banner_id'] = $banner_attachment_id;
			}
		}

		$user_meta = array( // meta_key => user object property to check
			'twitter_user_id' => 'id_str',
			'twitter_user_name' => 'name',
			'twitter_user_screen_name' => 'screen_name',
			'twitter_user_location' => 'location',
			'twitter_user_description' => 'description',
			'twitter_user_url' => 'url',
			'twitter_user_verified' => 'verified',
			'twitter_user_protected' => 'protected',
			'twitter_user_created_at' => 'created_at',
			'twitter_user_link_color' => 'profile_link_color',
			'twitter_user_sidebar_border_color' => 'profile_sidebar_border_color',
			'twitter_user_sidebar_fill_color' => 'profile_sidebar_fill_color',
			'twitter_user_text_color' => 'profile_text_color',
			'twitter_user_banner_url' => 'profile_banner_url',
		);
		foreach ( $user_meta as $meta_key => $user_obj_prop ) {
			if ( isset( $user->{ $user_obj_prop } ) ) {
				$meta[ $meta_key ] = $user->{ $user_obj_prop };
			}
		}
		// When importing tweets from an archive of JS files some of the dates are wrong.
		// These need to be rechecked via the API which shows the right date/time according to twitter permalinks
		if ( strpos( $user->created_at, '00:00:00 +0000' ) ) {
			$meta['recheck_tweet_date'] = true;
		}
		foreach ( $meta as $key => $value ) {
			update_post_meta( $user_post_id, $key, $value );
		}

		update_post_meta( $user_post_id, 'raw_twitter_user', $user );

		if ( isset( $meta['twitter_user_description'] ) && $profile_image_url ) {
			// We got all the info we need so delete this post meta field
			delete_post_meta( $user_post_id, 'check_twitter_user' );
		}

		// Make the description the post body content
		if ( isset( $user->description ) ) {
			$args = array(
				'ID' => $user_post_id,
				'post_content' => $user->description,
			);
			wp_update_post( $args );
		}

		if ( isset( $user->created_at ) ) {
			$datetime = date( 'Y-m-d H:i:s', strtotime( $user->created_at ) );
			$args = array(
				'ID' => $user_post_id,
				'post_date' => get_date_from_gmt( $datetime ),
				'post_date_gmt' => $datetime,
				'edit_date' => true, // Required to actually change the datetime of a post
			);
			wp_update_post( $args );
		}

		// Download Profile Image
		$this->download_profile_image( $profile_image_url, $user_post_id );

		// Maybe Tag URLs
		$urls = array();
		if ( isset( $user->entities->url->urls ) ) {
			foreach ( $user->entities->url->urls as $user_url ) {
				$urls[] = (object) array(
					'url' => $user_url->url,
					'expanded_url' => $user_url->expanded_url,
				);
			}

			foreach ( $user->entities->description->urls as $user_url ) {
				$urls[] = (object) array(
					'url' => $user_url->url,
					'expanded_url' => $user_url->expanded_url,
				);
			}
		}

		if ( ! empty( $urls ) ) {
			$this->maybe_tag_urls( $user_post_id, $urls );
		}

		return true;
	}

	public function tag_media_type( $the_term = '', $post_id = 0 ) {
		$tax = 'media-type';
		$term = term_exists( $the_term, $tax );
		if ( ! $term ) {
			$term = wp_insert_term( $the_term, $tax );
		}
		wp_set_object_terms( $post_id, intval( $term['term_id'] ), $tax );
	}

	public function download_url( $url = '' ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}

		$tmp = download_url( $url );
		$file_array = array(
			'name' => basename( $url ),
			'tmp_name' => $tmp,
		);
		if ( is_wp_error( $tmp ) ) {
			error_log( 'Tried downloading ' . $url . ': ' . $tmp->get_error_message() );
			return array();
		}

		return $file_array;
	}

	public function download_media( $post_id = 0 ) {
		global $wpdb;


		if ( ! $this->has_media() ) {
			return;
		}

		$tweet = $this->get_tweet();
		$media = $this->get_media();
		$date = $this->get_date();
		$tweet_parent_id = $this->get_id();
		foreach ( $media as $item ) {
			$file_array = $this->download_url( $item['media_url'] );
			if ( ! $file_array ) {
				continue;
			}

			// Delete any previously saved media with the same tweet id
			$post_ids_to_delete = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` = 'tweet_id' AND `meta_value` = '%s' LIMIT 0,1000",
					$item['id_str']
				)
			);
			foreach ( $post_ids_to_delete as $post_id_to_delete ) {
				$post_id_to_delete = intval( $post_id_to_delete );
				wp_delete_attachment( $post_id_to_delete, $force_delete = true );
			}

			// Prep new data
			$attachment_data = array(
				'post_content' => '',
				'post_title' => $item['expanded_url'],
				'post_name' => $item['url_token'],
				'post_date_gmt' => $date,
				'menu_order' => $item['media_sequence'],
				'guid' => $item['expanded_url'],
			);
			$photo_attachment_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
			if ( ! $photo_attachment_id || is_wp_error( $photo_attachment_id ) ) {
				// echo $item['media_url'] . ' skipped!';
				continue;
			}

			$update = array(
				'ID' => intval( $photo_attachment_id ),
				'guid' => $item['expanded_url'],
			);
			wp_update_post( $update );

			$attachment_metadata = array(
				'tweet_id' => $item['id_str'],
				'tweet_parent_id' => $tweet_parent_id,
				'tweet_url' => $item['expanded_url'],
				'tweet_media_url' => $item['media_url'],
				'tweet_tco_url' => $item['url'],
				'video_duration_millis' => $item['video_duration_millis'],
			);

			if ( ! $this->has_extended_media() ) {
				$attachment_metadata['check_twitter_metadata'] = 1;
			}

			foreach ( $attachment_metadata as $key => $val ) {
				update_post_meta( $photo_attachment_id, $key, $val );
			}

			// Set the featured image
			if ( $item['media_sequence'] === 1 ) {
				add_post_meta( $post_id, '_thumbnail_id', $photo_attachment_id );
			}
			add_post_meta( $post_id, 'twitter_photo_id', $photo_attachment_id );

			// Tag the link
			if ( $item['url'] ) {
				$urls = array();
				$urls[] = (object) array(
					'url' => $item['url'],
					'expanded_url' => $item['media_url'],
				);
				$this->maybe_tag_urls( $photo_attachment_id, $urls );
			}
			$media_type = 'Photo';
			$this->tag_media_type( $media_type, $photo_attachment_id );

			if ( ! $item['video_url'] ) {
				continue;
			}
			// If we have a video, download and save that as well
			$file_array = $this->download_url( $item['video_url'] );
			if ( ! $file_array ) {
				continue;
			}
			// Same attachment data we used earlier...
			$video_attachment_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
			if ( ! $video_attachment_id || is_wp_error( $video_attachment_id ) ) {
				// echo $item['video_url'] . ' skipped!';
				continue;
			}

			$update = array(
				'ID' => intval( $video_attachment_id ),
				'guid' => $item['expanded_url'],
			);
			wp_update_post( $update );

			$attachment_metadata['tweet_media_url'] = $item['video_url'];
			foreach ( $attachment_metadata as $key => $val ) {
				update_post_meta( $video_attachment_id, $key, $val );
			}

			// Set the featured image
			add_post_meta( $video_attachment_id, '_thumbnail_id', $photo_attachment_id );
			add_post_meta( $post_id, 'twitter_video_id', $video_attachment_id );

			// Tag the link
			if ( $item['url'] ) {
				$urls = array();
				$urls[] = (object) array(
					'url' => $item['url'],
					'expanded_url' => $item['video_url'],
				);
				$this->maybe_tag_urls( $video_attachment_id, $urls );
				// Untag the Photo attachment URL since the video takes precedence
				$this->maybe_untag_urls( $photo_attachment_id, $urls );
			}

			$media_type = ucfirst( $item['media_type'] );
			$is_animated_gif = false;
			if ( $media_type == 'Animated_gif' ) {
				$media_type = 'Animated Gif';
				$is_animated_gif = true;
			}

			update_post_meta( $video_attachment_id, 'is_animated_gif', $is_animated_gif );
			$this->tag_media_type( $media_type, $video_attachment_id );
		}
	}

	public static function download_detailed_media() {
		$args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'meta_key' => 'check_twitter_metadata',
			'posts_per_page' => 100,
		);
		$query = new WP_Query( $args );
		$attachments = $query->posts;
		if ( ! $attachments ) {
			// Nothing to check so bail
			return;
		}
		$tweet_ids = array();
		foreach ( $attachments as $post ) {
			$tweet_parent_id = get_post_meta( $post->ID, 'tweet_parent_id', true );
			if ( $tweet_parent_id ) {
				$tweet_ids[ $tweet_parent_id ] = $post->post_parent ;
			}
		}

		$id_str = array_keys( $tweet_ids );
		$id_str = implode( ',', $id_str );
		if ( ! $id_str ) {
			return;
		}

		$creds = new Tweet_Archiver_Twitter_Credentials();
		$tw = $creds->get_twitter_connection();
		$args = array(
			'id' => $id_str,
			'include_entities' => true,
			'tweet_mode' => 'extended',
		);
		$tweets = $tw->token_endpoint( 'statuses/lookup.json', $args );
		foreach ( $tweets as $tweet ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet( $tweet );
			$tweet_id = $tweet_import->get_tweet_id();
			$post_id = $tweet_ids[ $tweet_id ];
			$tweet_import->download_media( $post_id );
		}
	}

	public static function download_detailed_users() {
		$args = array(
			'post_type' => 'twitter-user',
			'meta_key' => 'check_twitter_user',
			'posts_per_page' => 100,
		);
		$query = new WP_Query( $args );
		$twitter_users = $query->posts;
		if ( ! $twitter_users ) {
			$args = array(
				'post_type' => 'twitter-user',
				'meta_key' => 'last_updated_gmt',
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'posts_per_page' => 100,
			);
			$query = new WP_Query( $args );
			$twitter_users = $query->posts;
		}

		$screen_names = array();
		foreach ( $twitter_users as $user ) {
			$screen_name = get_the_twitter_user_username( $user );
			$screen_names[ $screen_name ] = $screen_name ;
		}

		$screen_names_str = array_keys( $screen_names );
		$screen_names_str = implode( ',', $screen_names );

		$creds = new Tweet_Archiver_Twitter_Credentials();
		$tw = $creds->get_twitter_connection();
		$args = array(
			'screen_name' => $screen_names_str,
			'include_entities' => true,
		);
		$users = $tw->token_endpoint( 'users/lookup.json', $args );
		foreach ( $users as $user ) {
			$tweet_import = new Tweet_Archiver_Import_Tweet();
			$tweet_import->maybe_add_twitter_user( $user );
		}
	}

	public function download_profile_image( $url = '', $post_id = 0 ) {
		global $wpdb;
		$post_id = intval( $post_id );
		if ( ! $url || ! $post_id ) {
			return false;
		}

		// Need to check if the profile image has already been downloaded. Search GUID for $URL
		$found_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `ID` FROM $wpdb->posts WHERE `guid` = '%s' LIMIT 0, 1;",
				$url
			)
		);
		if ( $found_id ) {
			return;
		}

		$file_array = $this->download_url( $url );
		if ( ! $file_array ) {
			return false;
		}

		// Prep new data
		$attachment_data = array(
			'post_content' => '',
			'post_title' => $file_array['name'],
			'guid' => $url,
		);
		$profile_attachment_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
		if ( ! $profile_attachment_id || is_wp_error( $profile_attachment_id ) ) {
			return false;
		}

		$update = array(
			'ID' => intval( $profile_attachment_id ),
			'guid' => $url,
		);
		wp_update_post( $update );

		// Set the featured image
		add_post_meta( $post_id, '_thumbnail_id', $profile_attachment_id );

		$media_type = 'Profile Image';
		$this->tag_media_type( $media_type, $profile_attachment_id );

		return $profile_attachment_id;
	}

	public function download_banner_image( $url = '', $post_id = 0 ) {
		global $wpdb;
		$post_id = intval( $post_id );
		if ( ! $url || ! $post_id ) {
			return false;
		}

		// Need to check if the profile image has already been downloaded. Search GUID for $URL
		$found_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `ID` FROM $wpdb->posts WHERE `guid` = '%s' LIMIT 0, 1;",
				$url
			)
		);
		if ( $found_id ) {
			return false;
		}

		$user_post = get_post( $post_id );
		$file_array = $this->download_url( $url );
		if ( ! $file_array ) {
			return false;
		}
		// These URLs don't contain the extension and WordPress doesn't allow files to be sideloaded without an extension, even if the file extension is bogus.
		$file_array['name'] = sanitize_title( $user_post->post_title . ' Banner' ) . '.jpg';

		// Prep new data
		$attachment_data = array(
			'post_content' => '',
			'post_title' => $file_array['name'],
			'guid' => $url,
		);
		$banner_attachment_id = media_handle_sideload( $file_array, $post_id, null, $attachment_data );
		if ( ! $banner_attachment_id || is_wp_error( $banner_attachment_id ) ) {
			return false;
		}

		$update = array(
			'ID' => intval( $banner_attachment_id ),
			'guid' => $url,
		);
		wp_update_post( $update );

		$media_type = 'Banner Image';
		$this->tag_media_type( $media_type, $banner_attachment_id );

		return $banner_attachment_id;
	}

	public function get_meta() {
		$tweet = $this->get_tweet();
		$rt = $this->get_retweet();
		$reply_permalink = $this->get_reply_permalink();

		$meta = array(
			'raw_tweet' => $tweet,
			'tweet_source' => $tweet->source,
			'tweet_id' => $this->get_tweet_id(),
			'tweet_date' => $tweet->created_at,
			'tweet_permalink' => $this->get_tweet_permalink(),
			'tweet_text' => $this->get_tweet_text(),
			'tweet_character_count' => $this->get_tweet_character_count(),
			'tweet_user_name' => $this->get_tweet_user('name'),
			'tweet_user_screen_name' => $this->get_tweet_user('screen_name'),
			'tweet_user_id' => $this->get_tweet_user('id'),
			'is_retweet' => false,
			'is_reply' => false,
			'retweet_count' => $this->get_retweet_count(),
			'favorite_count' => $this->get_favorite_count(),
			'count_last_updated_gmt' => current_time( 'timestamp', $gmt = 1 ),
		);
		if ( $rt ) {
			$meta['is_retweet'] = true;
			$rt_meta = array(
				'retweet_source' => $rt->source,
				'retweet_id' => $this->get_retweet_id(),
				'retweet_date' => $rt->created_at,
				'retweet_permalink' => $this->get_retweet_permalink(),
				'retweet_text' => $this->get_retweet_text(),
				'retweet_user_name' => $this->get_retweet_user('name'),
				'retweet_user_screen_name' => $this->get_retweet_user('screen_name'),
				'retweet_user_id' => $this->get_retweet_user('id'),
			);
			$meta = array_merge( $meta, $rt_meta );
		}
		if ( $reply_permalink ) {
			$meta['is_reply'] = true;
			$reply_meta = array(
				'reply_id' => $this->get_reply_id(),
				'reply_user_id' => $this->get_reply_user_id(),
				'reply_user_name' => $this->get_reply_user_name(),
				'reply_user_screen_name' => $this->get_reply_user_screen_name(),
				'reply_permalink' => $reply_permalink,
			);
			$meta = array_merge( $meta, $reply_meta );
		}

		$links = array();
		$url_objs = $this->get_urls();
		foreach ( $url_objs as $url ) {
			$links[] = $url->url;
		}
		$tco_links = implode( ' ', $links );
		if ( $tco_links ) {
			$meta['tco_links'] = $tco_links;
		}

		$hashtags = $this->get_hashtags();
		if ( $hashtags ) {
			$meta['hashtags'] = implode( ' ', $hashtags );
		}

		return $meta;
	}

	public function save() {
		// Check if the Tweet already exists
		if ( $this->does_tweet_exist() ) {
			return false;
		}

		// Let's insert the tweets
		$new_tweet = array(
			'post_type' => 'tweet',
			'post_status' => 'publish',
			'post_date' => get_date_from_gmt( $this->get_tweet_date() ),
			'post_date_gmt' =>  $this->get_tweet_date(),
			'post_content' => $this->get_text(),
			'post_title' => $this->get_text(),
			'post_name' => $this->get_id(),
			'post_parent' => $this->get_reply_id(),
			'guid' => $this->get_permalink(),
			'meta_input' => $this->get_meta(),
		);
		$inserted_id = wp_insert_post( $new_tweet, $wp_error = true );
		if ( is_wp_error( $inserted_id ) ) {
			// Maybe it's because of bad characters? Try again.
			$new_tweet['post_content'] = Encoding::fixUTF8( $new_tweet['post_content'] );
			$new_tweet['post_title'] = Encoding::fixUTF8( $new_tweet['post_title'] );
			$inserted_id = wp_insert_post( $new_tweet, $wp_error = true );
		}

		if ( is_wp_error( $inserted_id ) ) {
			// Welp! We tried!
			echo '<p class="insert-post-error">Error: ' . $inserted_id->get_error_message() . '<br>' . $new_tweet['guid'] . '</p>';
			return false;
		}
		$this->maybe_tag_sources( $inserted_id );
		$this->maybe_tag_types( $inserted_id );
		$this->maybe_tag_hashtags( $inserted_id );
		$this->maybe_tag_urls( $inserted_id );
		$this->maybe_tag_mentions( $inserted_id );
		$this->maybe_tag_users( $inserted_id );
		$this->download_media( $inserted_id );

		return true;
	}
}
