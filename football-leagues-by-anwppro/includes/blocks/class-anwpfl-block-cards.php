<?php
/**
 * AnWP Football Leagues :: Block > Cards
 */

class AnWPFL_Block_Cards {

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
			AnWP_Football_Leagues::dir( 'gutenberg/blocks/cards' ),
			[
				'title'           => 'Cards',
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
				'competition_id' => '',
				'join_secondary' => 0,
				'season_id'      => '',
				'league_id'      => '',
				'club_id'        => '',
				'type'           => 'players',
				'limit'          => 0,
				'soft_limit'     => 'yes',
				'context'        => 'shortcode',
				'hide_points'    => 0,
				'show_photo'     => 'yes',
				'points_r'       => '5',
				'points_yr'      => '2',
				'hide_zero'      => 0,
				'sort_by_point'  => '',
			]
		);

		return anwp_fl()->template->shortcode_loader( 'cards', $attr );
	}
}

return new AnWPFL_Block_Cards();
