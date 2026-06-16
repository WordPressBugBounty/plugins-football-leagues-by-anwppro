<?php
/**
 * AnWP Football Leagues :: DB.
 * based & inspired by Better_Search_Replace::DB class
 *
 * @since   0.16.0
 * @package AnWP_Football_Leagues
 */

/**
 * AnWP Football Leagues :: DB.
 */
abstract class AnWPFL_DB {

	/**
	 * The name of our database table
	 */
	public $table_name;

	/**
	 * The name of the primary column
	 */
	public $primary_key;

	/**
	 * Per-request row cache shared across all subclasses.
	 * Keyed by "{table_name}-{id}" to prevent cross-table collisions.
	 *
	 * @since 0.17.3
	 * @var array<string, array|null>
	 */
	private static array $row_cache = [];

	/**
	 * Build cache key from table name and row ID.
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Row ID.
	 *
	 * @return string
	 */
	private function cache_key( int $id ): string {
		return $this->table_name . '-' . $id;
	}

	/**
	 * Get a single full row by primary key.
	 *
	 * Uses per-request static cache. Caches null for missing rows
	 * (negative caching). Subclasses can override to add fallback
	 * logic (e.g., postmeta fallback during migration).
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Row ID.
	 *
	 * @return array|null Row as associative array or null if not found.
	 */
	public function get_row( int $id ): ?array {
		$key = $this->cache_key( $id );

		if ( array_key_exists( $key, self::$row_cache ) ) {
			return self::$row_cache[ $key ];
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->table_name WHERE $this->primary_key = %d",
				$id
			),
			ARRAY_A
		);

		self::$row_cache[ $key ] = $row;

		return $row;
	}

	/**
	 * Check row cache without querying DB.
	 *
	 * Returns the cached row if available, null otherwise.
	 * Does NOT distinguish between "not cached" and "cached as null".
	 * Used by subclasses to fall back to full row data in accessors.
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Row ID.
	 *
	 * @return array|null Cached row or null.
	 */
	protected function get_cached_row( int $id ): ?array {
		return self::$row_cache[ $this->cache_key( $id ) ] ?? null;
	}

	/**
	 * Check if a row ID has been cached (including as null).
	 *
	 * Use before calling get_cached_row() when you need to distinguish
	 * "never cached" from "cached as null". Prevents re-querying the DB
	 * for known-missing IDs (entity existence probes, admin hooks that
	 * speculatively call get_row() for every post ID).
	 *
	 * @since 0.18.1
	 *
	 * @param int $id Row ID.
	 *
	 * @return bool True if the ID is in the cache (even as null).
	 */
	protected function has_cached_row( int $id ): bool {
		return array_key_exists( $this->cache_key( $id ), self::$row_cache );
	}

	/**
	 * Push a full row into the cache.
	 *
	 * Used by subclass warm methods (e.g., warm_clubs_full) to populate
	 * the base row cache from bulk fetch results.
	 *
	 * @since 0.17.3
	 *
	 * @param int        $id  Row ID.
	 * @param array|null $row Row as associative array or null.
	 */
	protected function cache_row( int $id, ?array $row ): void {
		self::$row_cache[ $this->cache_key( $id ) ] = $row;
	}

	/**
	 * Bulk-prime the row cache with null for IDs known to not exist in this table.
	 *
	 * Admin list pages (e.g. edit.php?post_type=anwp_match) trigger speculative
	 * get_row() probes via WP core hooks (meta lookups, thumbnail fallbacks, etc.)
	 * for every visible post ID, against entity tables where those IDs cannot
	 * exist. Calling this with the visible post IDs turns the resulting 50+ cache
	 * misses per request into a single cache-check per call.
	 *
	 * Safe because WordPress post IDs are globally unique across post_types:
	 * a match ID will NEVER appear in anwpfl_competitions/clubs/standings.
	 *
	 * Does not overwrite IDs already cached with real rows.
	 *
	 * @since 0.18.2
	 *
	 * @param int[] $ids Post IDs known to not exist in this entity's table.
	 */
	public function prime_missing_ids( array $ids ): void {
		foreach ( $ids as $id ) {
			$id = (int) $id;

			if ( $id <= 0 || $this->has_cached_row( $id ) ) {
				continue;
			}

			$this->cache_row( $id, null );
		}
	}

	/**
	 * Invalidate a single row in the cache.
	 *
	 * @since 0.17.3
	 *
	 * @param int $id Row ID.
	 */
	protected function invalidate_cache( int $id ): void {
		unset( self::$row_cache[ $this->cache_key( $id ) ] );
	}

	/**
	 * Bulk fetch rows by primary key.
	 *
	 * Low-level fetcher — does NOT populate $row_cache.
	 * Subclass decides which cache to populate and with which columns.
	 * No cache filtering — subclass filters before calling if needed.
	 *
	 * @since 0.17.3
	 *
	 * @param int[]  $ids     Array of row IDs.
	 * @param string $columns Column list for SELECT (default '*').
	 *
	 * @return array[] Array of associative arrays keyed by primary key.
	 */
	protected function fetch_rows( array $ids, string $columns = '*' ): array {
		$ids = array_unique( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			return [];
		}

		// Ensure PK is always in the SELECT (needed for keying results).
		if ( '*' !== $columns && false === stripos( $columns, $this->primary_key ) ) {
			$columns = $this->primary_key . ', ' . $columns;
		}

		global $wpdb;

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT $columns FROM $this->table_name WHERE $this->primary_key IN ($placeholders)",
				$ids
			),
			ARRAY_A
		);

		$keyed = [];
		$pk    = $this->primary_key;

		foreach ( $rows as $row ) {
			$keyed[ (int) $row[ $pk ] ] = $row;
		}

		return $keyed;
	}

	/**
	 * Reset row cache for this table, or all tables.
	 *
	 * @since 0.17.3
	 *
	 * @param bool $all Clear cache for all tables (default: this table only).
	 */
	public function reset_cache( bool $all = false ): void {
		if ( $all ) {
			self::$row_cache = [];
			return;
		}

		$prefix = $this->table_name . '-';

		foreach ( self::$row_cache as $key => $_ ) {
			if ( str_starts_with( $key, $prefix ) ) {
				unset( self::$row_cache[ $key ] );
			}
		}
	}

	/**
	 * Insert or update a row.
	 *
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE. Only columns listed in
	 * $update_columns are modified on conflict — prevents core saves
	 * from clobbering premium columns (and vice versa).
	 *
	 * $update_columns has no default — every caller must be explicit
	 * about which columns to update.
	 *
	 * @since 0.17.3
	 *
	 * @param int   $id             Row ID (primary key value).
	 * @param array $data           Column => value pairs for INSERT.
	 * @param array $update_columns Column names to include in ON DUPLICATE KEY UPDATE.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function upsert( int $id, array $data, array $update_columns ): bool {
		global $wpdb;

		if ( empty( $id ) || empty( $data ) || empty( $update_columns ) ) {
			return false;
		}

		// Ensure PK is in data.
		$data[ $this->primary_key ] = $id;

		// Whitelist against real table columns.
		$columns     = $this->get_columns();
		$data        = array_change_key_case( $data );
		$data        = array_intersect_key( $data, array_flip( $columns ) );
		$data_keys   = array_keys( $data );

		// Only update columns that exist in $data and in real table.
		$update_columns = array_intersect( $update_columns, $data_keys );
		$update_columns = array_intersect( $update_columns, $columns );

		if ( empty( $data ) || empty( $update_columns ) ) {
			return false;
		}

		// Build INSERT columns and placeholders.
		// Null values emit literal SQL NULL instead of a %s placeholder — %s with null
		// would produce '' via $wpdb->prepare, which for DATE DEFAULT NULL columns
		// becomes '0000-00-00' (or errors in strict mode) instead of real SQL NULL.
		$insert_columns = '`' . implode( '`, `', $data_keys ) . '`';
		$insert_places  = [];
		$insert_values  = [];

		foreach ( $data_keys as $col ) {
			if ( null === $data[ $col ] ) {
				$insert_places[] = 'NULL';
			} else {
				$insert_places[] = '%s';
				$insert_values[] = $data[ $col ];
			}
		}

		$insert_places = implode( ', ', $insert_places );

		// Build ON DUPLICATE KEY UPDATE clause (same null handling as INSERT).
		$update_parts  = [];
		$update_values = [];

		foreach ( $update_columns as $col ) {
			if ( null === $data[ $col ] ) {
				$update_parts[] = "`$col` = NULL";
			} else {
				$update_parts[]  = "`$col` = %s";
				$update_values[] = $data[ $col ];
			}
		}

		$update_clause = implode( ', ', $update_parts );
		$all_values    = array_merge( $insert_values, $update_values );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $this->table_name ($insert_columns) VALUES ($insert_places) ON DUPLICATE KEY UPDATE $update_clause",
				$all_values
			)
		);

		// Invalidate cache for this row.
		$this->invalidate_cache( $id );

		return false !== $result;
	}

	/**
	 * Returns an array of tables in the database.
	 *
	 * @return array
	 */
	public static function get_tables(): array {
		global $wpdb;

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( is_main_site() ) {
				$tables = $wpdb->get_col( 'SHOW TABLES' );
			} else {
				$blog_id = get_current_blog_id();
				$tables  = $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->base_prefix . absint( $blog_id ) . "\_%'" );
			}
		} else {
			$tables = $wpdb->get_col( 'SHOW TABLES' );
		}

		return $tables;
	}

	/**
	 * Returns an array containing the size of each database table.
	 *
	 * @return array
	 */
	public static function get_sizes(): array {
		global $wpdb;

		$sizes  = [];
		$tables = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );

		if ( is_array( $tables ) && ! empty( $tables ) ) {

			foreach ( $tables as $table ) {
				$size                    = round( $table['Data_length'] / 1024 / 1024, 2 );
				$sizes[ $table['Name'] ] = sprintf( '(%s MB)', $size );
			}
		}

		return $sizes;
	}

	/**
	 * Gets the columns in a table.
	 *
	 * @param string $table The table to check.
	 *
	 * @return array
	 */
	public function get_columns( string $table = '' ): array {

		global $wpdb;

		if ( empty( $table ) ) {
			$table = $this->table_name;
		}

		$columns = [];

		if ( false === $this->table_exists( $table ) ) {
			return $columns;
		}

		$fields = $wpdb->get_results( 'DESCRIBE ' . $table ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( is_array( $fields ) ) {
			foreach ( $fields as $column ) {
				$columns[] = $column->Field; //phpcs:ignore WordPress.NamingConventions
			}
		}

		return $columns;
	}

	/**
	 * Checks whether a table exists in DB.
	 *
	 * @param string $table
	 *
	 * @return bool
	 */
	public function table_exists( string $table ): bool {
		return in_array( $table, $this->get_tables(), true );
	}

	/**
	 * Update a row
	 *
	 * @param int    $row_id
	 * @param array  $data
	 * @param string $where
	 *
	 * @return  bool
	 */
	public function update( int $row_id, array $data = [], string $where = '' ): bool {

		global $wpdb;

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$columns = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, array_flip( $columns ) );

		if ( false === $wpdb->update( $this->table_name, $data, [ $where => $row_id ] ) ) {
			return false;
		}

		$this->invalidate_cache( $row_id );

		return true;
	}

	/**
	 * Insert a new row
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public function insert( array $data ) {
		global $wpdb;

		$columns = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, array_flip( $columns ) );

		return $wpdb->insert( $this->table_name, $data );
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @param int $row_id
	 *
	 * @return  bool
	 */
	public function delete( int $row_id = 0 ): bool {
		global $wpdb;

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) { //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return false;
		}

		$this->invalidate_cache( $row_id );

		return true;
	}
}
