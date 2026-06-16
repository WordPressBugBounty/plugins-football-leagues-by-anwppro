<?php
/**
 * AnWP Football Leagues :: Sitemap.
 *
 * Optimizes WordPress sitemaps for FL CPTs by replacing
 * WP_Query + get_permalink() with direct DB queries.
 *
 * @since   0.18.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: Sitemap.
 */
class AnWPFL_Sitemap {

	/**
	 * Parent plugin class.
	 *
	 * @var AnWP_Football_Leagues
	 */
	protected $plugin = null;

	/**
	 * Map CPT name => permalink structure key.
	 *
	 * @var array
	 */
	private array $permalink_keys = [
		'anwp_match'       => 'match',
		'anwp_competition' => 'competition',
		'anwp_club'        => 'club',
		'anwp_player'      => 'player',
		'anwp_referee'     => 'referee',
		'anwp_staff'       => 'staff',
		'anwp_stadium'     => 'stadium',
		'anwp_standing'    => 'standing',
	];

	/**
	 * Constructor.
	 *
	 * @param AnWP_Football_Leagues $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_filter( 'wp_sitemaps_posts_pre_url_list', [ $this, 'get_url_list' ], 10, 3 );
		add_filter( 'wp_sitemaps_posts_pre_max_num_pages', [ $this, 'get_max_num_pages' ], 10, 2 );
	}

	/**
	 * Short-circuit the default WP_Sitemaps_Posts URL list for FL CPTs.
	 *
	 * @param array[]|null $url_list  Default null.
	 * @param string       $post_type Post type name.
	 * @param int          $page_num  Page number.
	 *
	 * @return array[]|null URL list or null to use default.
	 */
	public function get_url_list( $url_list, $post_type, $page_num ) {

		if ( ! isset( $this->permalink_keys[ $post_type ] ) ) {
			return null;
		}

		// Fall back to default for plain permalinks.
		if ( ! get_option( 'permalink_structure' ) ) {
			return null;
		}

		global $wpdb;

		$max_urls = wp_sitemaps_get_max_urls( 'post' );
		$offset   = ( $page_num - 1 ) * $max_urls;

		$permalink_slug = $this->plugin->options->get_permalink_structure()[ $this->permalink_keys[ $post_type ] ] ?? $this->permalink_keys[ $post_type ];
		$base_url       = home_url( '/' . $permalink_slug . '/' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_name, post_modified_gmt FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY ID ASC LIMIT %d OFFSET %d",
				$post_type,
				$max_urls,
				$offset
			)
		);

		if ( ! $rows ) {
			return [];
		}

		$url_list = [];

		foreach ( $rows as $row ) {
			$url_list[] = [
				'loc'     => $base_url . $row->post_name . '/',
				'lastmod' => wp_date( DATE_W3C, strtotime( $row->post_modified_gmt ) ),
			];
		}

		return $url_list;
	}

	/**
	 * Short-circuit the default max pages count for FL CPTs.
	 *
	 * @param int|null $max_num_pages Default null.
	 * @param string   $post_type     Post type name.
	 *
	 * @return int|null Page count or null to use default.
	 */
	public function get_max_num_pages( $max_num_pages, $post_type ) {

		if ( ! isset( $this->permalink_keys[ $post_type ] ) ) {
			return null;
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			return null;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
				$post_type
			)
		);

		return (int) ceil( $total / wp_sitemaps_get_max_urls( 'post' ) );
	}
}
