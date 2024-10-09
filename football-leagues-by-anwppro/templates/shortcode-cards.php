<?php
/**
 * The Template for displaying Cards of players or teams.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-cards.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.7.4
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = wp_parse_args(
	$data,
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

// Try to get from cache
$cache_key = 'FL-SHORTCODE_cards__' . md5( maybe_serialize( $data ) );

if ( anwp_fl()->cache->get( $cache_key, 'anwp_match' ) ) {
	$items = anwp_fl()->cache->get( $cache_key, 'anwp_match' );
} else {
	// Load data in default way
	$items = anwp_fl()->player->tmpl_get_players_cards( $data );

	if ( 'players' === $data['type'] ) {
		$players_data = anwp_fl()->player->get_players_by_ids( wp_list_pluck( $items, 'player_id' ) );

		foreach ( $items as $player ) {
			$player->name       = $players_data[ $player->player_id ]['name'] ?? '';
			$player->short_name = $players_data[ $player->player_id ]['short_name'] ?? '';
			$player->link       = $players_data[ $player->player_id ]['link'] ?? '';
			$player->photo      = $players_data[ $player->player_id ]['photo'] ?? '';
		}
	}

	// Save transient
	if ( ! empty( $items ) ) {
		anwp_fl()->cache->set( $cache_key, $items, 'anwp_match' );
	}
}

if ( empty( $items ) ) {
	return;
}

$hide_points   = AnWP_Football_Leagues::string_to_bool( $data['hide_points'] );
$col_span      = $hide_points ? 5 : 6;
$default_photo = anwp_fl()->helper->get_default_player_photo();
?>
<div class="anwp-b-wrap cards-shortcode context--<?php echo esc_attr( $data['context'] ); ?> anwp-grid-table anwp-grid-table--aligned anwp-grid-table--bordered anwp-text-base anwp-border-light"
	style="--cards-shortcode-cols: <?php echo absint( $col_span ) - 2; ?>">

	<div class="anwp-grid-table__th anwp-border-light cards-shortcode__rank justify-content-center anwp-bg-light">
		<?php echo esc_html( AnWPFL_Text::get_value( 'cards__shortcode__n', _x( '#', 'Rank', 'anwp-football-leagues' ) ) ); ?>
	</div>

	<div class="anwp-grid-table__th anwp-border-light cards-shortcode__clubs-players anwp-bg-light anwp-text-xs">
		<?php echo esc_html( 'clubs' === $data['type'] ? AnWPFL_Text::get_value( 'cards__shortcode__clubs', __( 'Clubs', 'anwp-football-leagues' ) ) : AnWPFL_Text::get_value( 'cards__shortcode__player', __( 'Player', 'anwp-football-leagues' ) ) ); ?>
	</div>

	<div class="anwp-grid-table__th anwp-border-light cards-shortcode__cards_y justify-content-center anwp-bg-light">
		<svg class="icon__card">
			<use xlink:href="#icon-card_y"></use>
		</svg>
	</div>

	<div class="anwp-grid-table__th anwp-border-light cards-shortcode__card_yr justify-content-center anwp-bg-light">
		<svg class="icon__card">
			<use xlink:href="#icon-card_yr"></use>
		</svg>
	</div>

	<div class="anwp-grid-table__th anwp-border-light cards-shortcode__card_r justify-content-center anwp-bg-light">
		<svg class="icon__card">
			<use xlink:href="#icon-card_r"></use>
		</svg>
	</div>

	<?php if ( ! $hide_points ) : ?>
		<div class="anwp-grid-table__th anwp-border-light cards-shortcode__pts justify-content-center anwp-bg-light">
			<?php echo esc_html( AnWPFL_Text::get_value( 'cards__shortcode__pts', _x( 'Pts', 'points', 'anwp-football-leagues' ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $items as $index => $p ) : ?>
		<div class="anwp-grid-table__td cards-shortcode__rank justify-content-center">
			<?php echo intval( $index + 1 ); ?>
		</div>

		<div class="anwp-grid-table__td cards-shortcode__clubs-players anwp-overflow-x-hidden">
			<?php if ( 'players' === $data['type'] ) : ?>
				<div class="d-flex align-items-center position-relative">
					<?php if ( AnWP_Football_Leagues::string_to_bool( $data['show_photo'] ) ) : ?>
						<img loading="lazy" width="50" height="50" class="anwp-object-contain mr-2 anwp-w-50 anwp-h-50"
							src="<?php echo esc_url( $p->photo ? anwp_fl()->upload_dir . $p->photo : $default_photo ); ?>" alt="<?php echo esc_attr( $p->name ); ?>">
					<?php endif; ?>
					<div class="cards-shortcode__player-wrapper">
						<div class="cards-shortcode__player-name my-1"><?php echo esc_html( $p->short_name ); ?></div>

						<?php if ( ! empty( $p->clubs ) ) : ?>
							<div class="cards-shortcode__clubs d-flex flex-wrap align-items-center">
								<?php
								foreach ( explode( ',', $p->clubs ) as $ii => $club ) :
									$club_obj = anwp_fl()->club->get_club( $club );

									if ( $club_obj->logo ) :
										?>
										<img loading="lazy" width="25" height="25" class="anwp-object-contain mr-2 anwp-w-25 anwp-h-25"
											src="<?php echo esc_url( $club_obj->logo ); ?>"
											alt="<?php echo esc_attr( $club_obj->title ); ?>">
									<?php endif; ?>

									<div class="cards-shortcode__player-club anwp-text-sm anwp-opacity-80 anwp-leading-1 mr-3">
										<?php echo esc_html( $club_obj->title ); ?>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<a class="anwp-link-cover anwp-link-without-effects" title="<?php echo esc_attr( $p->name ); ?>" href="<?php echo esc_url( $p->link ); ?>"></a>
				</div>
			<?php elseif ( 'clubs' === $data['type'] ) : ?>
				<div class="d-flex align-items-center position-relative">

					<?php if ( AnWP_Football_Leagues::string_to_bool( $data['show_photo'] ) ) : ?>
						<img loading="lazy" width="25" height="25" class="anwp-object-contain mr-2 anwp-w-25 anwp-h-25"
							src="<?php echo esc_url( anwp_fl()->club->get_club_logo_by_id( $p->club_id ) ); ?>"
							alt="<?php echo esc_attr( anwp_fl()->club->get_club_title_by_id( $p->club_id ) ); ?>">
					<?php endif; ?>
					<div class="cards-shortcode__club-name">
						<?php echo esc_html( anwp_fl()->club->get_club_title_by_id( $p->club_id ) ); ?>
					</div>

					<a class="anwp-link-cover anwp-link-without-effects" title="<?php echo esc_attr( anwp_fl()->club->get_club_title_by_id( $p->club_id ) ); ?>" href="<?php echo esc_url( anwp_fl()->club->get_club_link_by_id( $p->club_id ) ); ?>"></a>
				</div>
			<?php endif; ?>
		</div>

		<div class="anwp-grid-table__td cards-shortcode__cards_y justify-content-center">
			<?php echo (int) $p->cards_y; ?>
		</div>

		<div class="anwp-grid-table__td cards-shortcode__cards_yr justify-content-center">
			<?php echo (int) $p->cards_yr; ?>
		</div>

		<div class="anwp-grid-table__td cards-shortcode__cards_r justify-content-center">
			<?php echo (int) $p->cards_r; ?>
		</div>

		<?php if ( ! $hide_points ) : ?>
			<div class="anwp-grid-table__td cards-shortcode__countable justify-content-center">
				<?php echo (int) $p->countable; ?>
			</div>
		<?php endif; ?>

	<?php endforeach; ?>

</div>
