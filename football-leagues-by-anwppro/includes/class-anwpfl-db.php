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

		return true;
	}
}
