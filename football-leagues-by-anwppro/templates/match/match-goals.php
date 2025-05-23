<?php
/**
 * The Template for displaying Match >> Goals Section.
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/match/match-goals.php.
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.6.1
 *
 * @version       0.16.0
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = wp_parse_args(
	$data,
	[
		'match_id'        => '',
		'kickoff'         => '',
		'match_date'      => '',
		'match_time'      => '',
		'home_club'       => '',
		'away_club'       => '',
		'club_home_title' => '',
		'club_away_title' => '',
		'club_home_logo'  => '',
		'club_away_logo'  => '',
		'finished'        => '',
		'home_goals'      => '',
		'away_goals'      => '',
		'match_week'      => '',
		'stadium_id'      => '',
		'competition_id'  => '',
		'main_stage_id'   => '',
		'stage_title'     => '',
		'parsed_events'   => [],
		'players'         => [],
		'header'          => true,
	]
);

if ( empty( $data['parsed_events']['goals'] ) ) {
	return '';
}

/**
 * Hook: anwpfl/tmpl-match/goals_before
 *
 * @param object $data Match data
 *
 * @since 0.7.5
 */
do_action( 'anwpfl/tmpl-match/goals_before', $data );

$temp_players = anwp_football_leagues()->match->get_temp_players( $data['match_id'] );
?>
<div class="match-goals anwp-section">

	<?php
	/*
	|--------------------------------------------------------------------
	| Block Header
	|--------------------------------------------------------------------
	*/
	if ( AnWP_Football_Leagues::string_to_bool( $data['header'] ) ) {
		anwp_football_leagues()->load_partial(
			[
				'text' => AnWPFL_Text::get_value( 'match__goals__goals', __( 'Goals', 'anwp-football-leagues' ) ),
			],
			'general/header'
		);
	}
	?>

	<?php foreach ( $data['parsed_events']['goals'] as $e_index => $e ) : ?>
		<div class="match__event-row py-1 d-flex align-items-center flex-row-reverse flex-sm-row anwp-fl-border-bottom anwp-border-light <?php echo $e_index ? '' : 'anwp-fl-border-top'; ?>">
			<div class="match__event-team-row anwp-flex-1 align-items-center <?php echo $e->club === (int) $data['away_club'] ? 'd-none d-sm-flex' : 'd-flex'; ?>">

				<?php if ( $e->club === (int) $data['home_club'] ) : ?>
					<div class="match__event-icon mx-2 anwp-flex-none anwp-leading-1">
						<svg class="icon__ball <?php echo esc_attr( 'yes' === $e->ownGoal ? 'icon__ball--own' : '' ); ?>">
							<use xlink:href="#<?php echo esc_attr( 'yes' === $e->fromPenalty ? 'icon-ball_penalty' : 'icon-ball' ); ?>"></use>
						</svg>
					</div>

					<div class="match__event-content anwp-leading-1-25 anwp-text-base">
						<div class="match__event-type anwp-text-sm anwp-opacity-80 anwp-leading-1">
							<?php
							if ( 'yes' === $e->ownGoal ) {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal_own', _x( 'Goal (own)', 'match event', 'anwp-football-leagues' ) ) );
							} elseif ( 'yes' === $e->fromPenalty ) {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal_penalty', _x( 'Goal (from penalty)', 'match event', 'anwp-football-leagues' ) ) );
							} else {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal', _x( 'Goal', 'match event', 'anwp-football-leagues' ) ) );
							}
							?>
						</div>
						<div class="match__event-player">
							<?php
							if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->player, 0, 6 ) ) :
								?>
								<span class="match__event-player-name">
									<?php echo esc_html( isset( $temp_players[ $e->player ] ) ? $temp_players[ $e->player ]->name : '' ); ?>
								</span>
								<?php
							elseif ( ! empty( $e->player ) && ! empty( $data['players'][ $e->player ] ) ) :
								$player = $data['players'][ $e->player ];
								?>
								<a class="anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $player['link'] ); ?>">
									<?php echo esc_html( $player['short_name'] ); ?>
								</a>
								<?php
							endif;

							if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->assistant, 0, 6 ) ) :
								?>
								<span class="mx-1 anwp-text-nowrap">
									(<span class="anwp-text-sm anwp-opacity-80 match__goals-assistant"><?php echo esc_html( AnWPFL_Text::get_value( 'match__goals__assistant', __( 'Assistant', 'anwp-football-leagues' ) ) ); ?></span>:
									<span class="match__event-player-name"><?php echo esc_html( isset( $temp_players[ $e->assistant ] ) ? $temp_players[ $e->assistant ]->name : '' ); ?></span>)
								</span>
								<?php
							elseif ( ! empty( $e->assistant ) && ! empty( $data['players'][ $e->assistant ] ) ) :
								$assistant = $data['players'][ $e->assistant ];
								?>
								<span class="mx-1 anwp-text-nowrap">
									(<span class="anwp-text-sm anwp-opacity-80 match__goals-assistant"><?php echo esc_html( AnWPFL_Text::get_value( 'match__goals__assistant', __( 'Assistant', 'anwp-football-leagues' ) ) ); ?></span>:
									<a class="anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $assistant['link'] ); ?>"><?php echo esc_html( $assistant['short_name'] ); ?></a>)
								</span>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div class="anwp-flex-none">
				<div class="match__event-minute d-flex flex-column anwp-bg-light">
					<div class="anwp-text-lg anwp-leading-1-25 <?php echo esc_attr( intval( $e->minute ) ? '' : 'anwp-hidden' ); ?>"><?php echo (int) $e->minute; ?>'</div>
					<?php if ( (int) $e->minuteAdd ) : ?>
						<div class="match__event-minute-add anwp-text-xs anwp-leading-1 anwp-text-center">+<?php echo (int) $e->minuteAdd; ?></div>
					<?php endif; ?>
				</div>
			</div>
			<div class="match__event-team-row anwp-flex-1 align-items-center flex-row-reverse flex-sm-row <?php echo $e->club === (int) $data['home_club'] ? 'd-none d-sm-flex' : 'd-flex'; ?>">
				<?php if ( $e->club === (int) $data['away_club'] ) : ?>
					<div class="match__event-icon mx-2 anwp-flex-none anwp-leading-1">
						<svg class="icon__ball <?php echo esc_attr( 'yes' === $e->ownGoal ? 'icon__ball--own' : '' ); ?>">
							<use xlink:href="#<?php echo esc_attr( 'yes' === $e->fromPenalty ? 'icon-ball_penalty' : 'icon-ball' ); ?>"></use>
						</svg>
					</div>

					<div class="match__event-content anwp-leading-1-25 anwp-text-base ml-auto ml-sm-0">
						<div class="match__event-type anwp-text-sm anwp-opacity-80 anwp-leading-1">
							<?php
							if ( 'yes' === $e->ownGoal ) {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal_own', _x( 'Goal (own)', 'match event', 'anwp-football-leagues' ) ) );
							} elseif ( 'yes' === $e->fromPenalty ) {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal_penalty', _x( 'Goal (from penalty)', 'match event', 'anwp-football-leagues' ) ) );
							} else {
								echo esc_html( AnWPFL_Text::get_value( 'match__goals__goal', _x( 'Goal', 'match event', 'anwp-football-leagues' ) ) );
							}
							?>
						</div>
						<div class="match__event-player">
							<?php
							if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->player, 0, 6 ) ) :
								?>
								<span class="match__event-player-name">
									<?php echo esc_html( isset( $temp_players[ $e->player ] ) ? $temp_players[ $e->player ]->name : '' ); ?>
								</span>
								<?php
							elseif ( $e->player && ! empty( $data['players'][ $e->player ] ) ) :
								$player = $data['players'][ $e->player ];
								?>
								<a class="anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $player['link'] ); ?>">
									<?php echo esc_html( $player['short_name'] ); ?>
								</a>
								<?php
							endif;

							if ( ! empty( $temp_players ) && 'temp__' === mb_substr( $e->assistant, 0, 6 ) ) :
								?>
								<span class="mx-1 anwp-text-nowrap">
									(<span class="anwp-text-sm anwp-opacity-80 match__goals-assistant"><?php echo esc_html( AnWPFL_Text::get_value( 'match__goals__assistant', __( 'Assistant', 'anwp-football-leagues' ) ) ); ?></span>:
									<span class="match__event-player-name"><?php echo esc_html( isset( $temp_players[ $e->assistant ] ) ? $temp_players[ $e->assistant ]->name : '' ); ?></span>)
								</span>
								<?php
							elseif ( ! empty( $e->assistant ) && ! empty( $data['players'][ $e->assistant ] ) ) :
								$assistant = $data['players'][ $e->assistant ];
								?>
								<span class="mx-1 anwp-text-nowrap">
									(<span class="anwp-text-sm anwp-opacity-80 match__goals-assistant"><?php echo esc_html( AnWPFL_Text::get_value( 'match__goals__assistant', __( 'Assistant', 'anwp-football-leagues' ) ) ); ?>:</span>
									<a class="anwp-link anwp-link-without-effects match__event-player-name" href="<?php echo esc_url( $assistant['link'] ); ?>"><?php echo esc_html( $assistant['short_name'] ); ?></a>)
								</span>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
