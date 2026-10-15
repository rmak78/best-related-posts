<?php
/**
 * Keyword extraction (pure PHP, no WordPress dependencies beyond apply_filters).
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * English stopword list used when tokenizing.
 *
 * @return array<string,bool> Map of stopword => true.
 */
function boposts_stopwords() {
	static $map = null;

	if ( null === $map ) {
		$words = array(
			'about', 'above', 'after', 'again', 'against', 'all', 'also', 'and', 'any', 'are', 'because', 'been',
			'before', 'being', 'below', 'between', 'both', 'but', 'can', 'could', 'did', 'does', 'doing', 'down',
			'during', 'each', 'few', 'for', 'from', 'further', 'get', 'had', 'has', 'have', 'having', 'her', 'here',
			'hers', 'herself', 'him', 'himself', 'his', 'how', 'into', 'its', 'itself', 'just', 'like', 'make',
			'many', 'more', 'most', 'much', 'myself', 'new', 'nor', 'not', 'now', 'off', 'once', 'one', 'only',
			'other', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'she', 'should', 'some', 'such',
			'than', 'that', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they',
			'this', 'those', 'through', 'too', 'under', 'until', 'use', 'using', 'very', 'was', 'way', 'were',
			'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'would', 'you', 'your',
			'yours', 'yourself', 'yourselves', 'thing', 'things', 'something', 'really', 'still', 'well', 'know',
			'need', 'want', 'even', 'back', 'good', 'best', 'first', 'last', 'next', 'time', 'year', 'years', 'day',
			'days', 'http', 'https', 'www', 'com', 'org', 'net', 'nbsp', 'amp', 'quot', 'href', 'src', 'img', 'div',
			'span', 'class', 'style', 'width', 'height', 'alt', 'title', 'post', 'posts', 'page', 'read', 'click',
		);

		/**
		 * Filters the stopword list.
		 *
		 * @param string[] $words Lowercase stopwords.
		 */
		$words = apply_filters( 'boposts_stopwords', $words );
		$map   = array_fill_keys( $words, true );
	}

	return $map;
}

/**
 * Lowercases a string with multibyte support when available.
 *
 * @param string $text Text.
 * @return string
 */
function boposts_lower( $text ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

/**
 * Removes HTML tags, shortcodes and entities from a string.
 *
 * @param string $text Text.
 * @return string
 */
function boposts_plain_text( $text ) {
	$text = (string) $text;
	// Shortcodes and block comments.
	$text = preg_replace( '/<!--.*?-->/s', ' ', $text );
	$text = preg_replace( '/\[[^\]]*\]/', ' ', $text );
	$text = preg_replace( '#<(script|style)[^>]*>.*?</\1>#is', ' ', $text );
	$text = strip_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/[\s\x{00A0}]+/u', ' ', $text );
	return trim( (string) $text );
}

/**
 * Very light English plural stemming so "tomato" and "tomatoes" match.
 *
 * @param string $token Lowercase token.
 * @return string
 */
function boposts_stem( $token ) {
	$mb  = function_exists( 'mb_strlen' );
	$len = $mb ? mb_strlen( $token, 'UTF-8' ) : strlen( $token );

	if ( $len > 4 && preg_match( '/(sses|xes|zes|ches|shes|oes)$/u', $token ) ) {
		$token = preg_replace( '/es$/u', '', $token );
	} elseif ( $len > 4 && preg_match( '/[^aeiou]ies$/u', $token ) ) {
		$token = preg_replace( '/ies$/u', 'y', $token );
	} elseif ( $len > 3 && preg_match( '/[^s]s$/u', $token ) ) {
		$token = preg_replace( '/s$/u', '', $token );
	}

	/**
	 * Filters the stemmed form of a token.
	 *
	 * @param string $token Stemmed token.
	 */
	return apply_filters( 'boposts_stem', $token );
}

/**
 * Splits text into lowercase tokens, dropping short tokens, numbers and stopwords.
 *
 * @param string $text Text (may contain HTML).
 * @return string[] Tokens in document order (with repeats).
 */
function boposts_tokenize( $text ) {
	$text = boposts_lower( boposts_plain_text( $text ) );
	if ( '' === $text ) {
		return array();
	}

	$parts = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $parts ) ) {
		return array();
	}

	/**
	 * Filters the minimum keyword length.
	 *
	 * @param int $min_length Minimum length in characters.
	 */
	$min_length = (int) apply_filters( 'boposts_min_keyword_length', 3 );
	$stopwords  = boposts_stopwords();
	$tokens     = array();

	foreach ( $parts as $part ) {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $part, 'UTF-8' ) : strlen( $part );
		if ( $length < $min_length ) {
			continue;
		}
		if ( is_numeric( $part ) ) {
			continue;
		}
		if ( isset( $stopwords[ $part ] ) ) {
			continue;
		}
		$part = boposts_stem( $part );
		if ( isset( $stopwords[ $part ] ) ) {
			continue;
		}
		$tokens[] = $part;
	}

	return $tokens;
}

/**
 * Extracts the most significant keywords of a post.
 *
 * Title tokens count double. Content is limited to its first 2000 characters.
 *
 * @param string $title   Post title.
 * @param string $content Post content (HTML allowed).
 * @param int    $limit   Maximum number of keywords.
 * @return array<string,float> keyword => weight, best first.
 */
function boposts_extract_keywords( $title, $content, $limit = 0 ) {
	if ( $limit <= 0 ) {
		/**
		 * Filters how many keywords are extracted from the current post.
		 *
		 * @param int $limit Keyword limit.
		 */
		$limit = (int) apply_filters( 'boposts_keyword_limit', 12 );
	}

	$content = boposts_plain_text( $content );
	$content = function_exists( 'mb_substr' ) ? mb_substr( $content, 0, 2000, 'UTF-8' ) : substr( $content, 0, 2000 );

	$weights = array();
	foreach ( boposts_tokenize( $title ) as $token ) {
		$weights[ $token ] = isset( $weights[ $token ] ) ? $weights[ $token ] + 2 : 2;
	}
	foreach ( boposts_tokenize( $content ) as $token ) {
		$weights[ $token ] = isset( $weights[ $token ] ) ? $weights[ $token ] + 1 : 1;
	}

	if ( empty( $weights ) ) {
		return array();
	}

	// Stable sort: weight desc, then first occurrence (array order).
	$order = array_flip( array_keys( $weights ) );
	uksort(
		$weights,
		static function ( $a, $b ) use ( $weights, $order ) {
			if ( $weights[ $a ] === $weights[ $b ] ) {
				return $order[ $a ] - $order[ $b ];
			}
			return ( $weights[ $a ] > $weights[ $b ] ) ? -1 : 1;
		}
	);

	$weights = array_slice( $weights, 0, max( 1, $limit ), true );

	/**
	 * Filters the extracted keywords.
	 *
	 * @param array  $weights keyword => weight.
	 * @param string $title   Post title.
	 */
	return apply_filters( 'boposts_keywords', $weights, $title );
}

/**
 * Trims plain text to a maximum number of characters at a word boundary.
 *
 * @param string $text   Plain text.
 * @param int    $length Maximum characters.
 * @param string $more   Suffix appended when trimmed.
 * @return string
 */
function boposts_trim_text( $text, $length, $more = '…' ) {
	$text   = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	$length = (int) $length;

	if ( $length <= 0 ) {
		return '';
	}

	$mb = function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' );
	$len = $mb ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	if ( $len <= $length ) {
		return $text;
	}

	$cut = $mb ? mb_substr( $text, 0, $length + 1, 'UTF-8' ) : substr( $text, 0, $length + 1 );
	$pos = $mb ? mb_strrpos( $cut, ' ', 0, 'UTF-8' ) : strrpos( $cut, ' ' );
	if ( false !== $pos && $pos > 0 ) {
		$cut = $mb ? mb_substr( $cut, 0, $pos, 'UTF-8' ) : substr( $cut, 0, $pos );
	} else {
		$cut = $mb ? mb_substr( $cut, 0, $length, 'UTF-8' ) : substr( $cut, 0, $length );
	}

	return rtrim( $cut, " \t\n\r\0\x0B,;:.-" ) . $more;
}
