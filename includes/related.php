<?php
/**
 * Finding related posts: candidates, scoring orchestration, caching.
 *
 * @package BestRelatedPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomies used for relatedness, keyed by signal name.
 *
 * @param WP_Post $post Current post.
 * @return array{tag:string[],category:string[]}
 */
function boposts_taxonomies( $post ) {
	$available = get_object_taxonomies( $post->post_type );
	$map       = array(
		'tag'      => array_values( array_intersect( array( 'post_tag' ), $available ) ),
		'category' => array_values( array_intersect( array( 'category' ), $available ) ),
	);

	/**
	 * Filters which taxonomies count as "tags" and "categories" for scoring.
	 *
	 * @param array   $map  ['tag' => string[], 'category' => string[]].
	 * @param WP_Post $post Current post.
	 */
	$map = apply_filters( 'boposts_taxonomies', $map, $post );

	$map['tag']      = array_values( array_intersect( (array) $map['tag'], $available ) );
	$map['category'] = array_values( array_intersect( (array) $map['category'], $available ) );

	return $map;
}

/**
 * Term ids that carry no meaning for relatedness (the default category).
 *
 * @return int[]
 */
function boposts_ignored_term_ids() {
	static $ids = null;
	if ( null === $ids ) {
		/**
		 * Filters term ids ignored when computing relatedness.
		 *
		 * @param int[] $ids Term ids. Default: the site's default category.
		 */
		$ids = array_map( 'intval', (array) apply_filters( 'boposts_ignored_term_ids', array( (int) get_option( 'default_category' ) ) ) );
	}
	return $ids;
}

/**
 * Term ids of a post for a list of taxonomies (uses the object cache).
 *
 * @param int      $post_id    Post id.
 * @param string[] $taxonomies Taxonomy names.
 * @return int[]
 */
function boposts_term_ids( $post_id, array $taxonomies ) {
	$ids     = array();
	$ignored = boposts_ignored_term_ids();
	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! in_array( (int) $term->term_id, $ignored, true ) ) {
					$ids[] = (int) $term->term_id;
				}
			}
		}
	}
	return $ids;
}

/**
 * Collects candidate post ids from taxonomy overlap and keyword search.
 *
 * @param WP_Post $post     Current post.
 * @param array   $options  Plugin options.
 * @param array   $tax      Result of boposts_taxonomies().
 * @param array   $keywords keyword => weight.
 * @return int[]
 */
function boposts_get_candidate_ids( $post, array $options, array $tax, array $keywords ) {
	$base = array(
		'post_type'              => $options['post_types'],
		'post_status'            => 'publish',
		'post__not_in'           => array( $post->ID ),
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'suppress_filters'       => false,
	);

	/**
	 * Filters the base WP_Query arguments used for candidate queries.
	 *
	 * @param array   $base Query arguments.
	 * @param WP_Post $post Current post.
	 */
	$base = apply_filters( 'boposts_candidate_query_args', $base, $post );

	$ids = array();

	// 1. Posts sharing any tag or category.
	$tax_query = array( 'relation' => 'OR' );
	foreach ( array_merge( $tax['tag'], $tax['category'] ) as $taxonomy ) {
		$term_ids = boposts_term_ids( $post->ID, array( $taxonomy ) );
		if ( $term_ids ) {
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => $term_ids,
			);
		}
	}
	if ( count( $tax_query ) > 1 ) {
		$query = new WP_Query(
			array_merge(
				$base,
				array(
					'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'posts_per_page' => (int) apply_filters( 'boposts_max_taxonomy_candidates', 100 ),
				)
			)
		);
		$ids   = array_merge( $ids, $query->posts );
	}

	// 2. Posts matching ANY of the strongest keywords in title or content.
	// (WP_Query's "s" requires every word to match, so use one OR query instead.)
	$search_terms = array_slice( array_keys( $keywords ), 0, (int) apply_filters( 'boposts_search_keyword_count', 5 ) );
	if ( $search_terms ) {
		$ids = array_merge( $ids, boposts_keyword_candidate_ids( $post, $options, $search_terms ) );
	}

	$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

	/**
	 * Filters the maximum number of candidates that are scored.
	 *
	 * @param int $max Candidate cap.
	 */
	$cap = (int) apply_filters( 'boposts_max_candidates', 150 );

	return array_slice( $ids, 0, max( 1, $cap ) );
}

/**
 * Published posts whose title or content contains any of the given keywords.
 *
 * @param WP_Post  $post     Current post.
 * @param array    $options  Plugin options.
 * @param string[] $keywords Keywords (already lowercase, stemmed).
 * @return int[]
 */
function boposts_keyword_candidate_ids( $post, array $options, array $keywords ) {
	global $wpdb;

	$types = array_values( array_filter( array_map( 'sanitize_key', (array) $options['post_types'] ) ) );
	if ( empty( $types ) || empty( $keywords ) ) {
		return array();
	}

	$where  = array();
	$params = array();
	foreach ( $keywords as $keyword ) {
		$like     = '%' . $wpdb->esc_like( $keyword ) . '%';
		$where[]  = '(post_title LIKE %s OR post_content LIKE %s)';
		$params[] = $like;
		$params[] = $like;
	}

	$type_placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	$params            = array_merge( $types, array( (int) $post->ID ), $params );
	$params[]          = max( 1, (int) apply_filters( 'boposts_max_keyword_candidates', 50 ) );

	$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$type_placeholders}) AND ID <> %d AND (" . implode( ' OR ', $where ) . ') ORDER BY post_date DESC LIMIT %d';

	$ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery

	return array_map( 'intval', (array) $ids );
}

/**
 * Recent published posts used to pad short result lists.
 *
 * @param WP_Post $post    Current post.
 * @param array   $options Plugin options.
 * @param int[]   $exclude Ids to exclude.
 * @param int     $count   How many.
 * @return int[]
 */
function boposts_recent_fallback_ids( $post, array $options, array $exclude, $count ) {
	$exclude[] = $post->ID;
	$query     = new WP_Query(
		array(
			'post_type'              => $options['post_types'],
			'post_status'            => 'publish',
			'post__not_in'           => array_unique( $exclude ),
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'posts_per_page'         => $count,
		)
	);
	return array_map( 'intval', $query->posts );
}

/**
 * Computes related post ids for a post (uncached).
 *
 * @param WP_Post $post    Current post.
 * @param int     $max     Number of results.
 * @param array   $options Plugin options.
 * @return int[]
 */
function boposts_compute_related_ids( $post, $max, array $options ) {
	$tax      = boposts_taxonomies( $post );
	$keywords = boposts_extract_keywords( $post->post_title, $post->post_content );
	$ids      = boposts_get_candidate_ids( $post, $options, $tax, $keywords );

	$scored = array();

	if ( $ids ) {
		// Load posts and their terms in two queries so the loop hits the cache.
		_prime_post_caches( $ids, false, true );

		$current = array(
			'id'             => $post->ID,
			'tags'           => boposts_term_ids( $post->ID, $tax['tag'] ),
			'cats'           => boposts_term_ids( $post->ID, $tax['category'] ),
			'title_tokens'   => array(),
			'content_tokens' => array(),
			'timestamp'      => (int) get_post_time( 'U', true, $post ),
		);
		$weights = boposts_weights();
		$now     = time();

		foreach ( $ids as $id ) {
			$candidate_post = get_post( $id );
			if ( ! $candidate_post instanceof WP_Post ) {
				continue;
			}
			$content = function_exists( 'mb_substr' )
				? mb_substr( boposts_plain_text( $candidate_post->post_content ), 0, 2000, 'UTF-8' )
				: substr( boposts_plain_text( $candidate_post->post_content ), 0, 2000 );

			$candidate = array(
				'id'             => $id,
				'tags'           => boposts_term_ids( $id, $tax['tag'] ),
				'cats'           => boposts_term_ids( $id, $tax['category'] ),
				'title_tokens'   => boposts_tokenize( $candidate_post->post_title ),
				'content_tokens' => boposts_tokenize( $content ),
				'timestamp'      => (int) get_post_time( 'U', true, $candidate_post ),
			);

			$score = boposts_score_candidate( $candidate, $current, $keywords, $weights, $now );
			// Ignore candidates whose only signal is recency.
			if ( $score <= $weights['recency'] ) {
				continue;
			}
			$scored[] = array(
				'id'        => $id,
				'score'     => $score,
				'timestamp' => $candidate['timestamp'],
			);
		}
	}

	$scored  = boposts_sort_scored( $scored );
	$related = array_column( array_slice( $scored, 0, $max ), 'id' );

	/**
	 * Filters whether to pad short result lists with recent posts.
	 *
	 * @param bool    $pad  Default true.
	 * @param WP_Post $post Current post.
	 */
	if ( count( $related ) < $max && apply_filters( 'boposts_fallback_recent', true, $post ) ) {
		$related = array_merge( $related, boposts_recent_fallback_ids( $post, $options, $related, $max - count( $related ) ) );
	}

	/**
	 * Filters the final related post ids.
	 *
	 * @param int[]   $related Related post ids, best first.
	 * @param WP_Post $post    Current post.
	 * @param array   $scored  All scored candidates.
	 */
	return apply_filters( 'boposts_related_ids', array_values( array_unique( $related ) ), $post, $scored );
}

/**
 * Current cache generation (bumped whenever content or settings change).
 *
 * @return int
 */
function boposts_cache_generation() {
	return (int) get_option( 'boposts_cache_gen', 1 );
}

/**
 * Invalidates all cached results (once per request).
 */
function boposts_bump_cache_generation() {
	static $bumped = false;
	if ( $bumped ) {
		return;
	}
	$bumped = true;
	update_option( 'boposts_cache_gen', max( time(), boposts_cache_generation() + 1 ) );
}

/**
 * Returns related post ids for a post, using the transient cache.
 *
 * @param int $post_id Post id (0 = current post).
 * @param int $max     Number of results (0 = option value).
 * @return int[]
 */
function boposts_find_related_ids( $post_id = 0, $max = 0 ) {
	$post = get_post( $post_id ? $post_id : get_the_ID() );
	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	$options = boposts_get_options();
	$max     = (int) $max;
	if ( $max <= 0 ) {
		$max = (int) $options['max'];
	}
	if ( $max <= 0 ) {
		$max = 4;
	}

	/**
	 * Filters whether caching is disabled.
	 *
	 * @param bool $disabled Default false.
	 */
	$use_cache = ! apply_filters( 'boposts_disable_cache', false );
	$key       = 'boposts_' . boposts_cache_generation() . '_' . $post->ID . '_' . md5( $max . '|' . implode( ',', (array) $options['post_types'] ) . '|' . BOPOSTS_VERSION );

	if ( $use_cache ) {
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['ids'] ) ) {
			return array_map( 'intval', $cached['ids'] );
		}
	}

	$ids = boposts_compute_related_ids( $post, $max, $options );

	if ( $use_cache ) {
		/**
		 * Filters the cache lifetime in seconds.
		 *
		 * @param int $ttl Seconds.
		 */
		$ttl = (int) apply_filters( 'boposts_cache_ttl', (int) $options['cache_ttl'] );
		set_transient( $key, array( 'ids' => $ids, 'time' => time() ), max( 60, $ttl ) );
	}

	return $ids;
}

/**
 * Back-compat: related posts for the current post as WP_Post objects.
 *
 * @return WP_Post[]
 */
function boposts_find_posts() {
	$posts = array();
	foreach ( boposts_find_related_ids() as $id ) {
		$p = get_post( $id );
		if ( $p instanceof WP_Post ) {
			$posts[] = $p;
		}
	}
	return $posts;
}

/* ---- Cache invalidation hooks ------------------------------------------- */

/**
 * Invalidate on post save (published posts of configured types only).
 *
 * @param int     $post_id Post id.
 * @param WP_Post $post    Post.
 */
function boposts_on_save_post( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, (array) boposts_get_options()['post_types'], true ) ) {
		return;
	}
	if ( 'publish' !== $post->post_status && 'trash' !== $post->post_status ) {
		return;
	}
	boposts_bump_cache_generation();
}
add_action( 'save_post', 'boposts_on_save_post', 10, 2 );

/**
 * Invalidate when a post enters or leaves the published state.
 *
 * @param string  $new  New status.
 * @param string  $old  Old status.
 * @param WP_Post $post Post.
 */
function boposts_on_transition_status( $new, $old, $post ) {
	if ( $new === $old || ( 'publish' !== $new && 'publish' !== $old ) ) {
		return;
	}
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, (array) boposts_get_options()['post_types'], true ) ) {
		return;
	}
	boposts_bump_cache_generation();
}
add_action( 'transition_post_status', 'boposts_on_transition_status', 10, 3 );

/**
 * Invalidate on post deletion.
 *
 * @param int $post_id Post id.
 */
function boposts_on_delete_post( $post_id ) {
	$type = get_post_type( $post_id );
	if ( $type && in_array( $type, (array) boposts_get_options()['post_types'], true ) ) {
		boposts_bump_cache_generation();
	}
}
add_action( 'deleted_post', 'boposts_on_delete_post' );

/**
 * Invalidate when terms are assigned to a configured post type.
 *
 * @param int    $object_id Object id.
 * @param array  $terms     Terms.
 * @param array  $tt_ids    Term taxonomy ids.
 * @param string $taxonomy  Taxonomy.
 */
function boposts_on_set_object_terms( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( ! in_array( $taxonomy, array( 'post_tag', 'category' ), true ) ) {
		return;
	}
	if ( 'publish' !== get_post_status( $object_id ) ) {
		return;
	}
	boposts_bump_cache_generation();
}
add_action( 'set_object_terms', 'boposts_on_set_object_terms', 10, 4 );

/**
 * Invalidate on term edits/deletions.
 *
 * @param int    $term_id  Term id.
 * @param int    $tt_id    Term taxonomy id.
 * @param string $taxonomy Taxonomy.
 */
function boposts_on_term_change( $term_id, $tt_id, $taxonomy ) {
	if ( in_array( $taxonomy, array( 'post_tag', 'category' ), true ) ) {
		boposts_bump_cache_generation();
	}
}
add_action( 'edited_term', 'boposts_on_term_change', 10, 3 );
add_action( 'delete_term', 'boposts_on_term_change', 10, 3 );
