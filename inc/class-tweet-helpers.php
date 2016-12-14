<?php
class Tweet_Archiver_Helpers {

	public function __construct() {
		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'twitterize_link', array( $this, 'hide_twitter_media_links' ), 10, 3 );
	}

	public static function extract_text( $text = '' ) {
		$e = Twitter_Extractor::create( $text );
		return array(
			'hashtags' => $e->extractHashtags(),
			'urls' => $e->extractURLs(),
			'mentions' => $e->extractMentionedScreennames(),
			'mentions_with_indices' => $e->extractMentionedUsernamesWithIndices(),
		);
	}

	public static function twitterize_text( $text = '' ) {
		$data = self::extract_text( $text );
		$site_url = trailingslashit( get_site_url() );
		foreach ( $data['hashtags'] as $hashtag ) {
			$link = '<a href="' . $site_url . 'hashtag/' . $hashtag . '/">#' . $hashtag . '</a>';
			$text = str_replace( '#' . $hashtag, $link, $text );
		}
		foreach ( $data['mentions'] as $mention ) {
			$link = '<a href="' . $site_url . 'mention/' . $mention . '/">@' . $mention . '</a>';
			$text = str_replace( '@' . $mention, $link, $text );
		}
		foreach ( $data['urls'] as $url ) {
			$expanded_url = get_tco_expanded_url( $url );
			if ( ! $expanded_url ) {
				$expanded_url = $url;
			}
			$display_url = self::get_display_url( $expanded_url );
			$link = '<a href="' . esc_url( $expanded_url ) . '">' . $display_url . '</a>';
			$link = apply_filters( 'twitterize_link', $link, $expanded_url, $url );
			$text = str_replace( $url, $link, $text );
		}
		return $text;
	}

	public static function the_content( $text = '' ) {
		return self::twitterize_text( $text );
	}

	public function hide_twitter_media_links( $link, $expanded_url, $url ) {
		if ( stristr( $expanded_url, '//pbs.twimg.com' ) ) {
			return;
		}
		return $link;
	}

	public static function get_display_url( $url = '', $max_characters = 32 ) {
		// Original: https://twitter.com/billy_penn/status/770290027226099712
		// Shortened: twitter.com/billy_penn/sta…
		$url = str_replace( 'https://www.', '', $url );
		$url = str_replace( 'http://www.', '', $url );
		$url = str_replace( 'https://', '', $url );
		$url = str_replace( 'http://', '', $url );
		if ( strlen( $url ) > $max_characters ) {
			$url = substr( $url, 0, $max_characters );
			$url .= '&hellip;';
		}
		return $url;
	}

	public static function scale_down_dimensions( $args = array() ) {
		$defaults = array(
			'width' => false,
			'height' => false,
			'max_width' => 9999,
			'max_height' => 9999,
		);
		$args = wp_parse_args( $args, $defaults );
		array_map( 'intval', $args );
		if ( ! $args['width'] || ! $args['height'] ) {
			return;
		}

		if ( $args['width'] > $args['max_width'] ) {
			$args['height'] = ( $args['height'] / $args['width'] ) * $args['max_width'];
			$args['width'] = $args['max_width'];
		}

		if ( $args['height'] > $args['max_height'] ) {
			$args['width'] = ( $args['width'] /  $args['height'] ) * $args['max_height'];
			$args['height'] = $args['max_height'];
		}

		return $args;
	}

}
new Tweet_Archiver_Helpers;
