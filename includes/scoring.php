<?php
/**
 * Relevance scoring (pure PHP).
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scoring weights.
 *
 * @return array<string,float>
 */
function boposts_weights() {
	$weights = array(
		'tag'             => 3.0,
		'category'        => 2.0,
		'title_keyword'   => 3.0,
		'content_keyword' => 2.0,
		'recency'         => 0.01,
	);

	/**
	 * Filters the scoring weights.
	 *
	 * @param array $weights Weights keyed by signal.
	 */
	$weights = apply_filters( 'boposts_weights', $weights );

	foreach ( array( 'tag', 'category', 'title_keyword', 'content_keyword', 'recency' ) as $key ) {
		$weights[ $key ] = isset( $weights[ $key ] ) ? (float) $weights[ $key ] : 0.0;
	}

	return $weights;
}

/**
 * Keyword overlap between the current post's keywords and a token list, as a
 * value in 0..1. Full credit is reached once the matched keyword weight hits
 * the cap (default 6, e.g. the main title keyword plus two content keywords),
 * so the signal is bounded by its weight but a strong topical match does not
 * get diluted by the long tail of minor keywords.
 *
 * @param string[]            $tokens   Candidate tokens.
 * @param array<string,float> $keywords Current post keywords => weight.
 * @return float
 */
function boposts_keyword_overlap( array $tokens, array $keywords ) {
	if ( empty( $tokens ) || empty( $keywords ) ) {
		return 0.0;
	}

	$present = array_fill_keys( $tokens, true );
	$sum     = 0.0;
	foreach ( $keywords as $keyword => $weight ) {
		if ( isset( $present[ $keyword ] ) ) {
			$sum += (float) $weight;
		}
	}

	/**
	 * Filters the matched keyword weight that counts as a full match.
	 *
	 * @param float $cap Cap.
	 */
	$cap = (float) apply_filters( 'boposts_keyword_cap', 6.0 );

	return $cap > 0 ? min( 1.0, $sum / $cap ) : 0.0;
}

/**
 * Scores one candidate post against the current post.
 *
 * Both arrays have the shape:
 *   id (int), tags (int[]), cats (int[]), title_tokens (string[]),
 *   content_tokens (string[]), timestamp (int, unix).
 *
 * @param array               $candidate Candidate post data.
 * @param array               $current   Current post data.
 * @param array<string,float> $keywords  Current post keywords => weight.
 * @param array<string,float> $weights   Weights from boposts_weights().
 * @param int|null            $now       Reference time for recency (unix).
 * @return float
 */
function boposts_score_candidate( array $candidate, array $current, array $keywords, array $weights, $now = null ) {
	$score = 0.0;

	$shared_tags = count( array_intersect( (array) $candidate['tags'], (array) $current['tags'] ) );
	$shared_cats = count( array_intersect( (array) $candidate['cats'], (array) $current['cats'] ) );

	$score += $weights['tag'] * $shared_tags;
	$score += $weights['category'] * $shared_cats;
	$score += $weights['title_keyword'] * boposts_keyword_overlap( (array) $candidate['title_tokens'], $keywords );
	$score += $weights['content_keyword'] * boposts_keyword_overlap( (array) $candidate['content_tokens'], $keywords );

	// Recency tie-break: strictly less than one weight unit so it never beats a real match.
	if ( $weights['recency'] > 0 && ! empty( $candidate['timestamp'] ) ) {
		$now      = null === $now ? time() : (int) $now;
		$age_days = max( 0, ( $now - (int) $candidate['timestamp'] ) / DAY_IN_SECONDS );
		$score   += $weights['recency'] * ( 1 - min( $age_days, 365 ) / 365 );
	}

	/**
	 * Filters the computed score for a candidate.
	 *
	 * @param float $score     Score.
	 * @param array $candidate Candidate data.
	 * @param array $current   Current post data.
	 */
	return (float) apply_filters( 'boposts_candidate_score', $score, $candidate, $current );
}

/**
 * Sorts candidates by score (desc) then timestamp (desc).
 *
 * @param array $scored List of ['id' => int, 'score' => float, 'timestamp' => int].
 * @return array
 */
function boposts_sort_scored( array $scored ) {
	usort(
		$scored,
		static function ( $a, $b ) {
			if ( $a['score'] !== $b['score'] ) {
				return ( $a['score'] > $b['score'] ) ? -1 : 1;
			}
			if ( $a['timestamp'] !== $b['timestamp'] ) {
				return ( $a['timestamp'] > $b['timestamp'] ) ? -1 : 1;
			}
			return $a['id'] - $b['id'];
		}
	);

	return $scored;
}
