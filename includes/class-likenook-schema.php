<?php
/**
 * Structured data: like and dislike counts as schema.org interactionStatistic.
 *
 * The counts are added to the page's existing Article (or WebPage) entity
 * instead of a second, competing one:
 *
 * - Yoast SEO, Rank Math and All in One SEO, through their schema filters.
 * - Any other theme or plugin that prints JSON-LD in the head: the matching
 *   entity in its output is extended.
 * - No JSON-LD on the page: a small WebPage entity is added.
 *
 * Counts are only marked up when vote results are visible (counts,
 * percentages or score), since search engines expect structured data to match
 * what visitors can see.
 *
 * @package Likenook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds interactionStatistic to the current post's schema.
 */
class Likenook_Schema {

	/**
	 * Entity types that describe the post itself, best match first.
	 */
	const ARTICLE_TYPES = array( 'Article', 'BlogPosting', 'NewsArticle', 'TechArticle', 'Report', 'ScholarlyArticle', 'SocialMediaPosting', 'LiveBlogPosting', 'HowTo', 'Recipe', 'Review' );

	/**
	 * Page-level types to fall back to.
	 */
	const PAGE_TYPES = array( 'WebPage', 'ItemPage', 'AboutPage', 'CollectionPage', 'ProfilePage', 'QAPage', 'FAQPage' );

	/**
	 * Whether the counts were already added on this request.
	 *
	 * @var bool
	 */
	private static $added = false;

	/**
	 * Whether the head is being buffered.
	 *
	 * @var bool
	 */
	private static $buffering = false;

	/**
	 * Hooks into WordPress and SEO plugins.
	 */
	public static function init() {
		add_filter( 'wpseo_schema_article', array( __CLASS__, 'filter_node' ) );
		add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'filter_yoast_webpage' ) );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'filter_graph' ), 99 );
		add_filter( 'aioseo_schema_output', array( __CLASS__, 'filter_graph' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_buffer_head' ) );
	}

	/**
	 * Whether an SEO plugin with a schema filter handles the markup.
	 *
	 * @return bool
	 */
	private static function seo_plugin_active() {
		return defined( 'WPSEO_VERSION' ) || class_exists( 'RankMath' ) || defined( 'AIOSEO_VERSION' );
	}

	/**
	 * The interactionStatistic list for the current post, or null when
	 * nothing should be marked up.
	 *
	 * @return array|null
	 */
	public static function statistics() {
		if ( ! Likenook_Settings::get( 'schema' ) || ! is_singular() ) {
			return null;
		}
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || ! self::shows_buttons( $post ) ) {
			return null;
		}

		$counts = Likenook_Votes::counts( 'post', $post->ID );
		$list   = array();
		if ( Likenook_Votes::reaction_enabled( 'like' ) && $counts['likes'] ) {
			$list[] = array(
				'@type'                => 'InteractionCounter',
				'interactionType'      => 'https://schema.org/LikeAction',
				'userInteractionCount' => $counts['likes'],
			);
		}
		if ( Likenook_Votes::reaction_enabled( 'dislike' ) && $counts['dislikes'] ) {
			$list[] = array(
				'@type'                => 'InteractionCounter',
				'interactionType'      => 'https://schema.org/DislikeAction',
				'userInteractionCount' => $counts['dislikes'],
			);
		}

		/**
		 * Filters the interactionStatistic entries added to the post's schema.
		 * Return an empty array to add nothing.
		 *
		 * @param array   $list   InteractionCounter entries.
		 * @param WP_Post $post   Post.
		 * @param array   $counts Like and dislike totals.
		 */
		$list = apply_filters( 'likenook_schema_statistics', $list, $post, $counts );
		return $list ? $list : null;
	}

	/**
	 * Whether this post shows the buttons with their counts visible.
	 *
	 * @param WP_Post $post Post.
	 * @return bool
	 */
	private static function shows_buttons( $post ) {
		// Hidden counts, or counts shown only after voting, aren't visible to everyone.
		if ( ! in_array( Likenook_Settings::get( 'counts' ), array( 'show', 'percent', 'score' ), true ) || 'product' === $post->post_type || post_password_required( $post ) ) {
			return false;
		}
		$by_hand = has_block( 'likenook/buttons', $post ) || has_shortcode( $post->post_content, 'likenook' );
		$auto    = Likenook_Settings::type_enabled( $post->post_type ) && 'manual' !== Likenook_Settings::get( 'position' ) && ! get_post_meta( $post->ID, Likenook_Votes::META_HIDE, true );
		return $by_hand || $auto;
	}

	/**
	 * Adds the counts to one entity, keeping any statistics already there.
	 *
	 * @param array $node  Schema entity.
	 * @param array $stats InteractionCounter entries.
	 * @return array
	 */
	private static function add_to( $node, $stats ) {
		$existing = isset( $node['interactionStatistic'] ) ? $node['interactionStatistic'] : array();
		if ( $existing && ! isset( $existing[0] ) ) {
			$existing = array( $existing );
		}
		$node['interactionStatistic'] = array_merge( (array) $existing, $stats );
		self::$added                  = true;
		return $node;
	}

	/**
	 * Yoast SEO article piece, and any single entity passed to a filter.
	 *
	 * @param array $node Schema entity.
	 * @return array
	 */
	public static function filter_node( $node ) {
		$stats = self::$added ? null : self::statistics();
		return $stats && is_array( $node ) ? self::add_to( $node, $stats ) : $node;
	}

	/**
	 * Yoast SEO WebPage piece, used when the page has no article piece.
	 *
	 * @param array $node Schema entity.
	 * @return array
	 */
	public static function filter_yoast_webpage( $node ) {
		if ( is_singular( 'post' ) ) {
			return $node;
		}
		return self::filter_node( $node );
	}

	/**
	 * Rank Math and All in One SEO: a list or keyed map of entities.
	 *
	 * @param array $graph Entities.
	 * @return array
	 */
	public static function filter_graph( $graph ) {
		$stats = self::$added ? null : self::statistics();
		if ( ! $stats || ! is_array( $graph ) ) {
			return $graph;
		}
		$key = self::find( $graph );
		if ( null !== $key ) {
			$graph[ $key ] = self::add_to( $graph[ $key ], $stats );
		}
		return $graph;
	}

	/**
	 * URLs that identify the current post in schema.
	 *
	 * @return string[]
	 */
	private static function urls() {
		$post = get_queried_object();
		$urls = array( get_permalink( $post ), wp_get_canonical_url( $post ) );
		$urls = array_filter( array_map( 'untrailingslashit', array_filter( $urls ) ) );
		return array_unique( $urls );
	}

	/**
	 * Whether an entity refers to the current post.
	 *
	 * @param array $node Entity.
	 * @return bool
	 */
	private static function is_current( $node ) {
		$candidates = array();
		foreach ( array( '@id', 'url' ) as $field ) {
			if ( isset( $node[ $field ] ) && is_string( $node[ $field ] ) ) {
				$candidates[] = $node[ $field ];
			}
		}
		if ( isset( $node['mainEntityOfPage'] ) ) {
			$main         = $node['mainEntityOfPage'];
			$candidates[] = is_array( $main ) ? ( isset( $main['@id'] ) ? $main['@id'] : '' ) : (string) $main;
		}

		$urls = self::urls();
		foreach ( $candidates as $candidate ) {
			$candidate = untrailingslashit( strtok( (string) $candidate, '#' ) );
			if ( in_array( $candidate, $urls, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Key of the best entity to extend: an article about this post first,
	 * then its WebPage. With no URL match, the only article on the page.
	 *
	 * @param array $graph Entities.
	 * @return int|string|null
	 */
	private static function find( $graph ) {
		$best      = null;
		$best_rank = PHP_INT_MAX;
		$articles  = array();

		foreach ( $graph as $key => $node ) {
			if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
				continue;
			}
			$types      = (array) $node['@type'];
			$is_article = (bool) array_intersect( $types, self::ARTICLE_TYPES );
			if ( $is_article ) {
				$articles[] = $key;
			}
			if ( ! self::is_current( $node ) ) {
				continue;
			}
			if ( $is_article ) {
				$rank = 0;
			} elseif ( in_array( 'WebPage', $types, true ) ) {
				$rank = 1;
			} elseif ( array_intersect( $types, self::PAGE_TYPES ) ) {
				$rank = 2;
			} else {
				continue;
			}
			if ( $rank < $best_rank ) {
				$best      = $key;
				$best_rank = $rank;
			}
		}

		if ( null === $best && 1 === count( $articles ) ) {
			$best = $articles[0];
		}
		return $best;
	}

	/**
	 * Without a known SEO plugin, buffers the head so the counts can join the
	 * JSON-LD printed by the theme or another plugin.
	 */
	public static function maybe_buffer_head() {
		if ( self::seo_plugin_active() || is_feed() || ! self::statistics() ) {
			return;
		}
		add_action( 'wp_head', array( __CLASS__, 'start_buffer' ), -99999 );
		add_action( 'wp_head', array( __CLASS__, 'end_buffer' ), 99999 );
	}

	/**
	 * Starts buffering the head.
	 */
	public static function start_buffer() {
		self::$buffering = ob_start();
	}

	/**
	 * Prints the head with the counts added to its JSON-LD, or with a small
	 * JSON-LD block of our own when the page has none that fits.
	 */
	public static function end_buffer() {
		if ( ! self::$buffering ) {
			return;
		}
		self::$buffering = false;
		$head            = (string) ob_get_clean();
		$stats           = self::statistics();

		if ( $stats ) {
			$head = preg_replace_callback(
				'#(<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>)(.*?)(</script>)#is',
				function ( $match ) use ( $stats ) {
					if ( self::$added ) {
						return $match[0];
					}
					$data = json_decode( trim( $match[2] ), true );
					if ( ! is_array( $data ) ) {
						return $match[0];
					}
					$data = self::inject( $data, $stats );
					if ( ! self::$added ) {
						return $match[0];
					}
					return $match[1] . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . $match[3];
				},
				$head
			);

			if ( ! self::$added ) {
				$head .= self::own_block( $stats );
			}
		}

		echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Head markup printed by WordPress and other plugins, passed through unchanged apart from the JSON-LD we re-encode.
	}

	/**
	 * Adds the counts inside one decoded JSON-LD document.
	 *
	 * @param array $data  Decoded JSON-LD: a graph, a single entity or a list.
	 * @param array $stats InteractionCounter entries.
	 * @return array
	 */
	private static function inject( $data, $stats ) {
		if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
			$key = self::find( $data['@graph'] );
			if ( null !== $key ) {
				$data['@graph'][ $key ] = self::add_to( $data['@graph'][ $key ], $stats );
			}
			return $data;
		}
		if ( isset( $data[0] ) ) {
			$key = self::find( $data );
			if ( null !== $key ) {
				$data[ $key ] = self::add_to( $data[ $key ], $stats );
			}
			return $data;
		}
		if ( null !== self::find( array( $data ) ) ) {
			$data = self::add_to( $data, $stats );
		}
		return $data;
	}

	/**
	 * A WebPage entity of our own, for pages without fitting JSON-LD.
	 *
	 * @param array $stats InteractionCounter entries.
	 * @return string
	 */
	private static function own_block( $stats ) {
		$post = get_queried_object();
		$url  = wp_get_canonical_url( $post );
		$url  = $url ? $url : get_permalink( $post );
		$node = array(
			'@context'             => 'https://schema.org',
			'@type'                => 'WebPage',
			'@id'                  => $url,
			'url'                  => $url,
			'name'                 => wp_strip_all_tags( get_the_title( $post ) ),
			'interactionStatistic' => $stats,
		);
		self::$added = true;
		return wp_get_inline_script_tag(
			wp_json_encode( $node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ),
			array(
				'type'  => 'application/ld+json',
				'class' => 'likenook-schema',
			)
		);
	}
}
