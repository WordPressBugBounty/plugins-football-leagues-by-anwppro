<?php
/**
 * The Template for displaying Players.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-players--mini.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.5.1
 *
 * @version       0.16.0
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
		'penalty_goals'     => 0,
		'games_played'      => 0,
		'secondary_sorting' => '',
		'group_by_place'    => 0,
		'games_played_text' => '',
		'cache_version'     => 'v3',
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
		$player->name        = $players_data[ $player->player_id ]['name'] ?? '';
		$player->short_name  = $players_data[ $player->player_id ]['short_name'] ?? '';
		$player->link        = $players_data[ $player->player_id ]['link'] ?? '';
		$player->photo       = $players_data[ $player->player_id ]['photo'] ?? '';
		$player->nationality = $players_data[ $player->player_id ]['nationality'] ?? '';
	}

	// Save transient
	if ( ! empty( $players ) ) {
		anwp_fl()->cache->set( $cache_key, $players, 'anwp_match' );
	}
}

if ( empty( $players ) ) {
	return;
}

$default_photo = anwp_fl()->helper->get_default_player_photo();

// Stats name
$stats_name = 'scorers' === $data['type'] ? AnWPFL_Text::get_value( 'players__shortcode__goals', __( 'Goals', 'anwp-football-leagues' ) ) : AnWPFL_Text::get_value( 'players__shortcode__assists', __( 'Assists', 'anwp-football-leagues' ) )
?>
<div class="anwp-b-wrap players-shortcode-mini anwp-grid-table anwp-grid-table--aligned anwp-grid-table--bordered anwp-text-base anwp-border-light player-list--<?php echo esc_attr( $data['type'] ); ?> context--<?php echo esc_attr( $data['context'] ); ?>"
	style="--players-shortcode-cols: <?php echo absint( $data['games_played'] ? 2 : 1 ); ?>;">

	<div class="anwp-grid-table__th anwp-border-light players-shortcode__rank anwp-bg-light anwp-text-xs justify-content-center">
		<?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__rank_n', _x( '#', 'Rank', 'anwp-football-leagues' ) ) ); ?>
	</div>

	<div class="anwp-grid-table__th anwp-border-light players-shortcode__player anwp-bg-light anwp-text-xs justify-content-start">
		<?php echo esc_html( AnWPFL_Text::get_value( 'players__shortcode__player', __( 'Player', 'anwp-football-leagues' ) ) ); ?>
	</div>

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
				<img loading="lazy" width="35" height="35" class="players-shortcode__photo anwp-object-contain my-2 mr-2 anwp-w-35 anwp-h-35" src="<?php echo esc_url( $p->photo ? anwp_fl()->upload_dir . $p->photo : $default_photo ); ?>"
					alt="<?php echo esc_attr( $p->short_name ); ?>">
			<?php endif; ?>

			<div class="d-flex flex-column">
				<div class="d-flex flex-wrap">
					<a class="anwp-link anwp-link-without-effects" href="<?php echo esc_url( $p->link ); ?>"><?php echo esc_html( $p->short_name ); ?></a>
				</div>
				<div class="players-shortcode__club">
					<?php
					foreach ( $clubs as $ii => $club_id ) :
						$club_obj = anwp_fl()->club->get_club( $club_id );
						?>
						<span class="players-shortcode__club-title anwp-text-sm anwp-opacity-80"><?php echo esc_html( $club_obj->title ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<?php if ( $data['games_played'] ) : ?>
			<div class="anwp-grid-table__td players-shortcode__played justify-content-center">
				<?php echo absint( $p->played ); ?>
			</div>
		<?php endif; ?>

		<div class="anwp-grid-table__td players-shortcode__stat justify-content-center">
			<?php
			echo absint( $p->countable );

			if ( $data['penalty_goals'] && ! empty( $p->penalty ) ) {
				echo ' (' . absint( $p->penalty ) . ')';
			}
			?>
		</div>
	<?php endforeach; ?>
</div>
