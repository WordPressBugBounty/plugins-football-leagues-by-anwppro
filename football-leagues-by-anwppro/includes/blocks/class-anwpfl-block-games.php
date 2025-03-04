<?php
/**
 * AnWP Football Leagues :: Block > Games
 *
 * @package Football_Leagues
 */

class AnWPFL_Block_Games {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register blocks.
	 */
	public function register_blocks() {
		register_block_type(
			AnWP_Football_Leagues::dir( 'gutenberg/blocks/games' ),
			[
				'title'           => 'FL Games',
				'render_callback' => [ $this, 'server_side_render' ],
			]
		);
	}

	/**
	 * Register blocks.
	 *
	 * @param array $attr the block attributes
	 */
	public function server_side_render( array $attr ): string {

		$attr = wp_parse_args(
			$attr,
			[
				'competition_id'        => '',
				'stage_id'              => '',
				'show_secondary'        => 1,
				'season_id'             => '',
				'league_id'             => '',
				'group_id'              => '',
				'type'                  => '',
				'limit'                 => 0,
				'date_from'             => '',
				'date_to'               => '',
				'stadium_id'            => '',
				'filter_by'             => '',
				'filter_values'         => '',
				'filter_by_clubs'       => '',
				'filter_by_matchweeks'  => '',
				'days_offset'           => '',
				'days_offset_to'        => '',
				'sort_by_date'          => '',
				'sort_by_matchweek'     => '',
				'club_links'            => 1,
				'priority'              => '',
				'class'                 => '',
				'group_by'              => '',
				'group_by_header_style' => '',
				'show_club_logos'       => 1,
				'show_match_datetime'   => 1,
				'competition_logo'      => 1,
				'exclude_ids'           => '',
				'include_ids'           => '',
				'outcome_id'            => '',
				'no_data_text'          => '',
				'home_club'             => '',
				'away_club'             => '',
				'layout'                => '',
				'header_class'          => '',
				'show_load_more'        => false,
			]
		);

		if ( AnWP_Football_Leagues::is_editing_block_on_backend() ) {
			$attr['class'] .= ' anwp-fl-disable-link ';
		}

		return anwp_fl()->template->shortcode_loader( 'matches', $attr );
	}
}

return new AnWPFL_Block_Games();
