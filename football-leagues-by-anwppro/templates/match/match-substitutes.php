<?php
/**
 * The Template for displaying Match >> Substitutes Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/match/match-substitutes.php.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.6.1
 *
 * @version       0.16.12
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = wp_parse_args(
	$data,
	[
		'match_id'      => '',
		'home_club'     => '',
		'away_club'     => '',
		'parsed_events' => [],
		'players'       => [],
		'header'        => true,
	]
);

if ( empty( $data['parsed_events']['subs'] ) ) {
	return '';
}

/**
 * Hook: anwpfl/tmpl-match/substitutes_before
 *
 * @param object $data Match data
 *
 * @since 0.7.5
 */
do_action( 'anwpfl/tmpl-match/substitutes_before', $data );

$club_home_obj = anwp_football_leagues()->club->get_club( $data['home_club'] );
$club_away_obj = anwp_football_leagues()->club->get_club( $data['away_club'] );
$temp_players  = anwp_football_leagues()->match->get_temp_players( $data['match_id'] );
?>
<div class="match-substitutes anwp-section">

	<?php
	/*
	|--------------------------------------------------------------------
	| Block Header
	|--------------------------------------------------------------------
	*/
	if ( AnWP_Football_Leagues::string_to_bool( $data['header'] ) ) {
		anwp_football_leagues()->load_partial(
			[
				'text' => AnWPFL_Text::get_value( 'match__substitutes__substitutes', __( 'Substitutes', 'anwp-football-leagues' ) ),
			],
			'general/header'
		);
	}
	?>

	<?php foreach ( $data['parsed_events']['subs'] as $e_index => $e ) : ?>
		<div class="match__event-team-row match__event-row py-1 d-flex align-items-center flex-row-reverse flex-sm-row anwp-fl-border-bottom anwp-border-light <?php echo $e_index ? '' : 'anwp-fl-border-top'; ?>">
			<div class="anwp-flex-1 d-sm-flex flex-column flex-sm-row align-items-center <?php echo $e->club === (int) $data['away_club'] ? 'd-none' : 'flex-wrap'; ?>">

				<?php if ( $e->club === (int) $data['home_club'] ) : ?>

					<div class="d-flex anwp-flex-sm-1 mx-sm-0">
						<div class="match__event-icon--subs-wrapper mx-sm-2 anwp-flex-none anwp-leading-1">
							<div class="match__event-icon mt-1 mb-sm-2">
								<svg class="icon__subs-out">
									<use xlink:href="#icon-arrow-o-down"></use>
								</svg>
							</div>
						</div>

						<div class="match__event-content anwp-leading-1-25 anwp-text-base d-flex flex-row-reverse d-sm-block align-items-center">
							<div class="match__event-type anwp-text-xs anwp-opacity-70 anwp-leading-1 mx-2 mx-sm-0">
								<?php echo esc_html( AnWPFL_Text::get_value( 'match__substitutes__out', _x( 'Out', 'substitute event', 'anwp-football-leagues' ) ) ); ?>
							</div>
							<div class="match__event-player">
								<?php if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->playerOut, 0, 6 ) ) : ?>
									<span class="match__event-player-name"><?php echo esc_html( isset( $temp_players[ $e->playerOut ] ) ? $temp_players[ $e->playerOut ]->name : '' ); ?></span>
									<?php
								elseif ( $e->playerOut && ! empty( $data['players'][ $e->playerOut ] ) ) :
									$player = $data['players'][ $e->playerOut ];
									?>
									<a class="anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $player['link'] ); ?>">
										<?php echo esc_html( $player['short_name'] ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="d-flex anwp-flex-sm-1">
						<div class="match__event-icon--subs-wrapper mx-sm-2 anwp-flex-none anwp-leading-1">
							<div class="match__event-icon">
								<svg class="icon__subs-in">
									<use xlink:href="#icon-arrow-o-up"></use>
								</svg>
							</div>
						</div>

						<div class="match__event-content anwp-leading-1-25 anwp-text-base d-flex flex-row-reverse d-sm-block align-items-center">
							<div class="match__event-type anwp-text-xs anwp-opacity-70 anwp-leading-1 mx-2 mx-sm-0">
								<?php echo esc_html( AnWPFL_Text::get_value( 'match__substitutes__in', _x( 'In', 'substitute event', 'anwp-football-leagues' ) ) ); ?>
							</div>
							<div class="match__event-player">
								<?php if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->player, 0, 6 ) ) : ?>
									<span class="match__event-player-name"><?php echo esc_html( isset( $temp_players[ $e->player ] ) ? $temp_players[ $e->player ]->name : '' ); ?></span>
									<?php
								elseif ( $e->player && ! empty( $data['players'][ $e->player ] ) ) :
									$player = $data['players'][ $e->player ];
									?>
									<a class="anwp-link anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $player['link'] ); ?>">
										<?php echo esc_html( $player['short_name'] ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>

				<?php endif; ?>
			</div>

			<?php if ( $e->club === (int) $data['away_club'] ) : ?>
				<?php if ( $club_away_obj->logo ) : ?>
					<img loading="lazy" width="30" height="30" class="anwp-flex-none anwp-object-contain d-sm-none anwp-w-30 anwp-h-30" src="<?php echo esc_url( $club_away_obj->logo ); ?>" alt="<?php echo esc_attr( $club_away_obj->title ); ?>">
				<?php endif; ?>
			<?php endif; ?>

			<div class="anwp-flex-none">
				<div class="match__event-minute d-flex flex-column anwp-bg-light">
					<div class="anwp-text-lg anwp-leading-1-25 <?php echo esc_attr( intval( $e->minute ) ? '' : 'anwp-hidden' ); ?>"><?php echo (int) $e->minute; ?>'</div>
					<?php if ( (int) $e->minuteAdd ) : ?>
						<div class="match__event-minute-add anwp-text-xs anwp-leading-1 anwp-text-center">+<?php echo (int) $e->minuteAdd; ?></div>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $e->club === (int) $data['home_club'] ) : ?>
				<?php if ( $club_home_obj->logo ) : ?>
					<img loading="lazy" width="30" height="30" class="anwp-flex-none anwp-object-contain d-sm-none anwp-w-30 anwp-h-30" src="<?php echo esc_url( $club_home_obj->logo ); ?>" alt="<?php echo esc_attr( $club_home_obj->title ); ?>">
				<?php endif; ?>
			<?php endif; ?>

			<div class="match__event-team-row match__event-row anwp-flex-1 align-items-end align-items-sm-center flex-column flex-sm-row <?php echo $e->club === (int) $data['home_club'] ? 'd-none d-sm-flex' : 'd-flex'; ?>">

				<?php if ( $e->club === (int) $data['away_club'] ) : ?>
					<div class="d-flex flex-row-reverse flex-sm-row anwp-flex-sm-1 mr-sm-3">
						<div class="match__event-icon--subs-wrapper ml-1 ml-sm-0 anwp-flex-none anwp-leading-1">
							<div class="match__event-icon mt-1 mb-sm-2">
								<svg class="icon__subs-out">
									<use xlink:href="#icon-arrow-o-down"></use>
								</svg>
							</div>
						</div>

						<div class="match__event-content anwp-leading-1-25 anwp-text-base d-flex d-sm-block align-content-center align-items-center">
							<div class="match__event-type anwp-text-xs anwp-opacity-70 anwp-leading-1 mr-1 mr-sm-0">
								<?php echo esc_html( AnWPFL_Text::get_value( 'match__substitutes__out', _x( 'Out', 'substitute event', 'anwp-football-leagues' ) ) ); ?>
							</div>
							<div class="match__event-player">
								<?php if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->playerOut, 0, 6 ) ) : ?>
									<span class="match__event-player-name anwp-leading-1"><?php echo esc_html( isset( $temp_players[ $e->playerOut ] ) ? $temp_players[ $e->playerOut ]->name : '' ); ?></span>
									<?php
								elseif ( $e->playerOut && ! empty( $data['players'][ $e->playerOut ] ) ) :
									$player = $data['players'][ $e->playerOut ];
									?>
									<a class="anwp-link-without-effects match__event-player-name anwp-leading-1" href="<?php echo esc_url( $player['link'] ); ?>">
										<?php echo esc_html( $player['short_name'] ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<div class="d-flex flex-row-reverse flex-sm-row anwp-flex-sm-1">
						<div class="match__event-icon--subs-wrapper ml-1 ml-sm-0 anwp-flex-none anwp-leading-1">
							<div class="match__event-icon">
								<svg class="icon__subs-in">
									<use xlink:href="#icon-arrow-o-up"></use>
								</svg>
							</div>
						</div>

						<div class="match__event-content anwp-leading-1-25 anwp-text-base d-flex d-sm-block align-content-center align-items-center">
							<div class="match__event-type anwp-text-xs anwp-opacity-70 anwp-leading-1 mr-1 mr-sm-0">
								<?php echo esc_html( AnWPFL_Text::get_value( 'match__substitutes__in', _x( 'In', 'substitute event', 'anwp-football-leagues' ) ) ); ?>
							</div>
							<div class="match__event-player">
								<?php if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->player, 0, 6 ) ) : ?>
									<span class="match__event-player-name anwp-leading-1"><?php echo esc_html( isset( $temp_players[ $e->player ] ) ? $temp_players[ $e->player ]->name : '' ); ?></span>
									<?php
								elseif ( $e->player && ! empty( $data['players'][ $e->player ] ) ) :
									$player = $data['players'][ $e->player ];
									?>
									<a class="anwp-link-without-effects match__event-player-name anwp-leading-1" href="<?php echo esc_url( $player['link'] ); ?>">
										<?php echo esc_html( $player['short_name'] ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
