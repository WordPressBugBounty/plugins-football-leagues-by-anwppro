<?php
/**
 * The Template for displaying Players.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-players.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.5.1
 *
 * @version       0.16.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = wp_parse_args(
	$data,
	[
		'competition_id'    => '',
		'join_secondary'    => 0,
		'season_id'         => '',
		'league_id'         => '',
		'club_id'           => '',
		'type'              => 'scorers',
		'limit'             => 0,
		'soft_limit'        => 'yes',
		'context'           => 'shortcode',
		'show_photo'        => 'yes',
		'compact'           => false,
		'penalty_goals'     => 0,
		'games_played'      => 0,
		'secondary_sorting' => '',
		'group_by_place'    => 0,
		'games_played_text' => '',
		'cache_version'     => 'v5',
	]
);

$data['penalty_goals']  = AnWP_Football_Leagues::string_to_bool( $data['penalty_goals'] );
$data['games_played']   = AnWP_Football_Leagues::string_to_bool( $data['games_played'] );
$data['group_by_place'] = AnWP_Football_Leagues::string_to_bool( $data['group_by_place'] );

// Try to get from cache
$cache_key = 'FL-SHORTCODE_players__' . md5( maybe_serialize( $data ) );

if ( anwp_fl()->cache->get( $cache_key, 'anwp_match' ) ) {
	$players = anwp_fl()->cache->get( $cache_key, 'anwp_match' );
} else {
	// Load data in default way
	$players      = anwp_fl()->player->tmpl_get_players_by_type( $data );
	$players_data = anwp_fl()->player->get_players_by_ids( wp_list_pluck( $players, 'player_id' ) );

	foreach ( $players as $player ) {
		$player->name          = $players_data[ $player->player_id ]['name'] ?? '';
		$player->short_name    = $players_data[ $player->player_id ]['short_name'] ?? '';
		$player->link          = $players_data[ $player->player_id ]['link'] ?? '';
		$player->photo         = $players_data[ $player->player_id ]['photo'] ?? '';
		$player->nationalities = $players_data[ $player->player_id ]['nationalities'] ?? '';
	}

	// Save transient
	if ( ! empty( $players ) ) {
		anwp_fl()->cache->set( $cache_key, $players, 'anwp_match' );
	}
}

if ( empty( $players ) ) {
	return;
}

// Compact layout
$compact_layout = AnWP_Football_Leagues::string_to_bool( $data['compact'] );

// Stats name
$stats_name = 'scorers' === $data['type'] ? AnWPFL_Text::get_value( 'players__shortcode__goals', __( 'Goals', 'anwp-football-leagues' ) ) : AnWPFL_Text::get_value( 'players__shortcode__assists', __( 'Assists', 'anwp-football-leagues' ) );

$col_span      = ( $compact_layout ? 1 : 2 ) + ( $data['games_played'] ? 1 : 0 );
$col_span_sm   = 1 + ( $data['games_played'] ? 1 : 0 );
$default_photo = anwp_fl()->helper->get_default_player_photo();
?>
<div class="anwp-b-wrap players-shortcode anwp-grid-table anwp-grid-table--aligned anwp-grid-table--bordered anwp-text-base anwp-border-light player-list--<?php echo esc_attr( $data['type'] ); ?> context--<?php echo esc_attr( $data['context'] ); ?> layout-compact--<?php echo (int) $compact_layout; ?>"
	style="--players-shortcode-cols: <?php echo absint( $col_span ); ?>; --players-shortcode-sm-cols: <?php echo absint( $col_span_sm ); ?>">

	<div class="anwp-grid-table__th anwp-border-light players-shortcode__rank anwp-bg-light anwp-text-xs justify-content-center">
		<span class="anwp-grid-table__sm-none"><?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__rank', __( 'Rank', 'anwp-football-leagues' ) ) ); ?></span><span class="d-none anwp-grid-table__sm-block">#</span>
	</div>

	<div class="anwp-grid-table__th anwp-border-light players-shortcode__player anwp-bg-light anwp-text-xs justify-content-start">
		<?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__player', __( 'Player', 'anwp-football-leagues' ) ) ); ?>
	</div>

	<?php if ( ! $compact_layout ) : ?>
		<div class="anwp-grid-table__th anwp-border-light players-shortcode__club anwp-bg-light anwp-text-xs justify-content-start anwp-grid-table__sm-none">
			<?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__club', __( 'Club', 'anwp-football-leagues' ) ) ); ?>
		</div>
		<div class="anwp-grid-table__th anwp-border-light players-shortcode__nationality anwp-bg-light anwp-text-xs justify-content-center anwp-grid-table__sm-none">
			<?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__nationality', __( 'Nationality', 'anwp-football-leagues' ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $data['games_played'] ) : ?>
		<div class="anwp-grid-table__th anwp-border-light players-shortcode__played anwp-bg-light anwp-text-xs justify-content-center">
			<?php echo esc_html( $data['games_played_text'] ); ?>
		</div>
	<?php endif; ?>

	<div class="anwp-grid-table__th anwp-border-light players-shortcode__stat anwp-bg-light anwp-text-xs justify-content-center">
		<?php echo esc_html( $stats_name ); ?>
	</div>

	<?php
	$group_by_place = - 1;

	foreach ( $players as $index => $p ) :
		$clubs = explode( ',', $p->clubs );
		?>

		<div class="anwp-grid-table__td players-shortcode__rank justify-content-center">
			<?php
			if ( $data['group_by_place'] ) {
				echo absint( $p->countable ) !== $group_by_place ? intval( $index + 1 ) : '';
				$group_by_place = absint( $p->countable );
			} else {
				echo intval( $index + 1 );
			}
			?>
		</div>

		<div class="anwp-grid-table__td players-shortcode__player d-flex align-items-center justify-content-start">

			<?php if ( AnWP_Football_Leagues::string_to_bool( $data['show_photo'] ) ) : ?>
				<img loading="lazy" width="50" height="50" class="players-shortcode__photo anwp-object-contain my-2 mr-2 anwp-w-50 anwp-h-50" src="<?php echo esc_url( $p->photo ? anwp_fl()->upload_dir . $p->photo : $default_photo ); ?>"
					alt="<?php echo esc_attr( $p->name ); ?>">
			<?php endif; ?>

			<div class="d-flex flex-column">
				<div class="d-flex flex-wrap">
					<a class="anwp-link anwp-link-without-effects" href="<?php echo esc_url( $p->link ); ?>"><?php echo esc_html( $p->short_name ); ?></a>
					<?php
					if ( $p->nationalities ?? '' ) :
						foreach ( $p->nationalities as $country_code ) :
							anwp_fl()->load_partial(
								[
									'class'         => 'options__flag mx-2 py-n1 ' . ( $compact_layout ? '' : 'd-none anwp-grid-table__sm-block' ),
									'wrapper_class' => 'mx-2 py-n1 ' . ( $compact_layout ? '' : 'd-none anwp-grid-table__sm-block' ),
									'size'          => 16,
									'country_code'  => $country_code,
								],
								'general/flag'
							);
						endforeach;
					endif;
					?>
				</div>
				<div class="players-shortcode__club flex-column align-items-start justify-content-center <?php echo esc_attr( $compact_layout ? '' : 'd-none anwp-grid-table__sm-block mt-1' ); ?>">
					<?php
					foreach ( $clubs as $ii => $club_id ) :
						$club_obj = anwp_fl()->club->get_club( $club_id );
						?>
						<div class="d-flex align-items-center">
							<?php if ( $club_obj->logo ) : ?>
								<img loading="lazy" width="20" height="20" class="players-shortcode__club-logo anwp-object-contain mr-1 anwp-w-20 anwp-h-20" src="<?php echo esc_url( $club_obj->logo ); ?>"
									alt="<?php echo esc_attr( $club_obj->title ); ?>">
							<?php endif; ?>
							<span class="players-shortcode__club-title anwp-text-sm anwp-opacity-80"><?php echo esc_html( $club_obj->title ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php if ( ! $compact_layout ) : ?>
			<div class="anwp-grid-table__td d-flex flex-column align-items-start justify-content-center players-shortcode__club anwp-text-sm anwp-grid-table__sm-none">
				<?php
				foreach ( $clubs as $ii => $club_id ) :
					$club_obj = anwp_fl()->club->get_club( $club_id );
					?>
					<div class="d-flex align-items-center">
						<?php if ( $club_obj->logo ) : ?>
							<img loading="lazy" width="30" height="30" class="players-shortcode__club-logo anwp-object-contain mr-2 anwp-w-30 anwp-h-30" src="<?php echo esc_url( $club_obj->logo ); ?>"
								alt="<?php echo esc_attr( $club_obj->title ); ?>">
						<?php endif; ?>
						<span class="players-shortcode__club-title anwp-text-wrap"><?php echo esc_html( $club_obj->title ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="anwp-grid-table__td players-shortcode__nationality justify-content-center anwp-grid-table__sm-none align-items-center">
				<?php
				if ( $p->nationalities ?? '' ) :
					foreach ( $p->nationalities as $country_code ) :
						anwp_fl()->load_partial(
							[
								'class'        => 'options__flag',
								'size'         => 32,
								'country_code' => $country_code,
							],
							'general/flag'
						);
					endforeach;
				endif;
				?>
			</div>
		<?php endif; ?>

		<?php if ( $data['games_played'] ) : ?>
			<div class="anwp-grid-table__td players-shortcode__played justify-content-center">
				<?php echo absint( $p->played ); ?>
			</div>
		<?php endif; ?>

		<div class="anwp-grid-table__td players-shortcode__stat justify-content-center">
			<?php
			echo absint( $p->countable );

			if ( $data['penalty_goals'] && ( $p->penalty ?? 0 ) ) {
				echo ' (' . absint( $p->penalty ) . ')';
			}
			?>
		</div>
	<?php endforeach; ?>
</div>
