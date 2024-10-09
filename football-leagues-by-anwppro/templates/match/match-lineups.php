<?php
/**
 * The Template for displaying Match >> Line Ups Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/match/match-lineups.php.
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.6.1
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = wp_parse_args(
	$data,
	[
		'match_id'        => '',
		'home_club'       => '',
		'away_club'       => '',
		'club_home_title' => '',
		'club_away_title' => '',
		'club_home_logo'  => '',
		'club_away_logo'  => '',
		'club_home_link'  => '',
		'club_away_link'  => '',
		'season_id'       => '',
		'events'          => [],
		'players'         => [],
		'home_line_up'    => '',
		'away_line_up'    => '',
		'home_subs'       => '',
		'away_subs'       => '',
		'coach_home'      => '',
		'coach_away'      => '',
		'captain_home'    => '',
		'captain_away'    => '',
		'custom_numbers'  => (object) [],
		'header'          => true,
	]
);

$events = anwp_football_leagues()->helper->parse_match_events_lineups( $data['events'] );

$home_line_up = $data['home_line_up'];
$away_line_up = $data['away_line_up'];
$home_subs    = $data['home_subs'];
$away_subs    = $data['away_subs'];

// Prepare squad
$home_squad = anwp_football_leagues()->club->tmpl_prepare_club_squad( $data['home_club'], $data['season_id'] );
$away_squad = anwp_football_leagues()->club->tmpl_prepare_club_squad( $data['away_club'], $data['season_id'] );

// Event icons
$event_icons = anwp_football_leagues()->data->get_event_icons();

$positions = anwp_football_leagues()->data->get_positions_l10n();

$positions_l10n = [
	'g' => anwp_football_leagues()->get_option_value( 'text_abbr_goalkeeper' ) ?: $positions['g'],
	'd' => anwp_football_leagues()->get_option_value( 'text_abbr_defender' ) ?: $positions['d'],
	'm' => anwp_football_leagues()->get_option_value( 'text_abbr_midfielder' ) ?: $positions['m'],
	'f' => anwp_football_leagues()->get_option_value( 'text_abbr_forward' ) ?: $positions['f'],
];

$show_event_minutes = 'hide' !== anwp_football_leagues()->customizer->get_value( 'match', 'lineups_event_minutes' );
$temp_players       = anwp_football_leagues()->match->get_temp_players( $data['match_id'] );
$temp_coaches       = get_post_meta( $data['match_id'], '_anwpfl_temp_coach', true );

if ( $home_line_up || $away_line_up ) :
	/**
	 * Trigger on before rendering match lineups.
	 *
	 * @param object $data Match data
	 *
	 * @since 0.7.5
	 */
	do_action( 'anwpfl/tmpl-match/lineups_before', $data );
	?>
	<div class="anwp-section match-lineups">

		<?php
		/*
		|--------------------------------------------------------------------
		| Block Header
		|--------------------------------------------------------------------
		*/
		if ( AnWP_Football_Leagues::string_to_bool( $data['header'] ) ) {
			anwp_football_leagues()->load_partial(
				[
					'text' => AnWPFL_Text::get_value( 'match__lineups__line_ups', __( 'Line Ups', 'anwp-football-leagues' ) ),
				],
				'general/header'
			);
		}

		/**
		* Trigger on before rendering match lineups.
		*
		* @param object $data Match data
		*
		* @since 0.7.5
		*/
		do_action( 'anwpfl/tmpl-match/lineups_after_header', $data );
		?>

		<div class="match-lineups__players">
			<?php
			anwp_football_leagues()->load_partial(
				[
					'club_id' => $data['home_club'],
					'class'   => 'match-lineups__home-club',
				],
				'club/club-title'
			);

			/*
			|--------------------------------------------------------------------------
			| Home Club Line Ups
			|--------------------------------------------------------------------------
			*/
			$home_line_up = $home_line_up ? explode( ',', $home_line_up ) : [];

			if ( ! empty( $home_line_up ) && is_array( $home_line_up ) ) :
				?>
				<div class="match-lineups__home-starting">
					<?php
					foreach ( $home_line_up as $player_id ) :
						$player      = $data['players'][ $player_id ] ?? '';
						$temp_player = false;

						if ( 'temp__' === mb_substr( $player_id, 0, 6 ) && isset( $temp_players[ $player_id ] ) ) {
							$temp_player = $temp_players[ $player_id ];
						}

						if ( empty( $player ) && empty( $temp_player ) ) {
							continue;
						}

						$is_captain = $data['captain_home'] && ( ( 'temp__' === mb_substr( $player_id, 0, 6 ) ? $data['captain_home'] === $player_id : absint( $data['captain_home'] ) === absint( $player_id ) ) );
						?>
						<div class="match__player-wrapper d-flex flex-wrap align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
							<div class="d-flex align-items-center">
								<div class="match__player-number anwp-bg-light anwp-leading-1-25 mr-2">
									<?php
									$player_number = '&nbsp;';

									if ( ! empty( $data['custom_numbers']->{$player_id} ) ) {
										$player_number = (int) $data['custom_numbers']->{$player_id};
									} elseif ( isset( $home_squad[ $player_id ] ) && $home_squad[ $player_id ]['number'] ) {
										$player_number = (int) $home_squad[ $player_id ]['number'];
									}

									echo esc_html( $player_number );
									?>
								</div>

								<?php if ( $temp_player && $temp_player->country ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $temp_player->country,
											],
											'general/flag'
										);
										?>
									</div>
								<?php elseif ( $player['nationality'] ?? '' ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $player['nationality'],
											],
											'general/flag'
										);
										?>
									</div>
								<?php endif; ?>

								<?php if ( $temp_player && $temp_player->position ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $temp_players[ $player_id ]->position ] ); ?>
									</div>
								<?php elseif ( ! empty( $home_squad[ $player_id ] ) && isset( $positions_l10n[ $home_squad[ $player_id ]['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $home_squad[ $player_id ]['position'] ] ); ?>
									</div>
								<?php elseif ( ! empty( $player['position'] ) && isset( $positions_l10n[ $player['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $player['position'] ] ); ?>
									</div>
								<?php endif; ?>

								<div class="match__player-name-wrapper ml-1">
									<?php if ( $temp_player ) : ?>
										<span class="match__player-name"><?php echo esc_html( $temp_player->name ); ?></span>
									<?php else : ?>
										<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( $player['link'] ); ?>"><?php echo esc_html( $player['short_name'] ); ?></a>
									<?php endif; ?>
								</div>

								<?php if ( $is_captain ) : ?>
									<div class="match__player-captain ml-1">
										<svg class="anwp-icon anwp-icon--s20 anwp-icon--octi">
											<use xlink:href="#icon-captain"></use>
										</svg>
									</div>
								<?php endif; ?>
							</div>

							<div class="d-flex align-items-center ml-auto pl-2">
								<?php
								if ( ! empty( $events[ $player_id ] ) ) :
									foreach ( $events[ $player_id ] as $evt ) :
										echo isset( $event_icons[ $evt['type'] ] ) ? $event_icons[ $evt['type'] ] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										if ( $show_event_minutes && absint( $evt['minute'] ) ) {
											echo '<span class="mr-1 anwp-text-xs anwp-opacity-70 anwp-fl-lineups-event-minutes">' . absint( $evt['minute'] ) . '\'</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			endif;

			/*
			|--------------------------------------------------------------------------
			| Home Club Substitutions
			|--------------------------------------------------------------------------
			*/
			$home_subs = $home_subs ? explode( ',', $home_subs ) : [];

			if ( ! empty( $home_subs ) && is_array( $home_subs ) ) :
				?>
				<div class="match-lineups__home-subs">
					<div class="anwp-fl-subheader anwp-text-uppercase anwp-text-base anwp-bg-light p-1 mb-1"><?php echo esc_html( AnWPFL_Text::get_value( 'match__lineups__substitutes', __( 'Substitutes', 'anwp-football-leagues' ) ) ); ?></div>
					<?php
					foreach ( $home_subs as $player_id ) :
						$player      = $data['players'][ $player_id ] ?? '';
						$temp_player = false;

						if ( 'temp__' === mb_substr( $player_id, 0, 6 ) && isset( $temp_players[ $player_id ] ) ) :
							$temp_player = $temp_players[ $player_id ];
						endif;

						if ( empty( $player ) && empty( $temp_player ) ) :
							continue;
						endif;
						?>
						<div class="match__player-wrapper d-flex flex-wrap align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
							<div class="d-flex align-items-center">
								<div class="match__player-number anwp-bg-light anwp-leading-1-25 mr-2">
									<?php
									$player_number = '&nbsp;';

									if ( ! empty( $data['custom_numbers']->{$player_id} ) ) {
										$player_number = (int) $data['custom_numbers']->{$player_id};
									} elseif ( isset( $home_squad[ $player_id ] ) && $home_squad[ $player_id ]['number'] ) {
										$player_number = (int) $home_squad[ $player_id ]['number'];
									}

									echo esc_html( $player_number );
									?>
								</div>

								<?php if ( $temp_player && $temp_player->country ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $temp_player->country,
											],
											'general/flag'
										);
										?>
									</div>
								<?php elseif ( $player['nationality'] ?? '' ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $player['nationality'],
											],
											'general/flag'
										);
										?>
									</div>
								<?php endif; ?>

								<?php if ( $temp_player && $temp_player->position ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $temp_player->position ] ); ?>
									</div>
								<?php elseif ( ! empty( $home_squad[ $player_id ] ) && isset( $positions_l10n[ $home_squad[ $player_id ]['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $home_squad[ $player_id ]['position'] ] ); ?>
									</div>
								<?php elseif ( ! empty( $player['position'] ) && isset( $positions_l10n[ $player['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $player['position'] ] ); ?>
									</div>
								<?php endif; ?>

								<div class="match__player-name-wrapper mr-auto ml-1">
									<?php if ( $temp_player ) : ?>
										<span class="match__player-name"><?php echo esc_html( $temp_player->name ); ?></span>
									<?php else : ?>
										<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( $player['link'] ); ?>"><?php echo esc_html( $player['short_name'] ); ?></a>
									<?php endif; ?>
								</div>
							</div>
							<div class="d-flex align-items-center ml-auto pl-2">
								<?php
								if ( ! empty( $events[ $player_id ] ) ) :
									foreach ( $events[ $player_id ] as $evt ) :
										echo isset( $event_icons[ $evt['type'] ] ) ? $event_icons[ $evt['type'] ] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										if ( $show_event_minutes && absint( $evt['minute'] ) ) {
											echo '<span class="mr-1 anwp-text-xs anwp-opacity-70 anwp-fl-lineups-event-minutes">' . absint( $evt['minute'] ) . '\'</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			endif;

			/*
			|--------------------------------------------------------------------------
			| Home Club Coach
			|--------------------------------------------------------------------------
			*/
			if ( ! empty( $data['coach_home'] ) ) :
				$coach_nationality = get_post_meta( $data['coach_home'], '_anwpfl_nationality', true );
				?>
				<div class="match-lineups__home-coach">
					<div class="anwp-fl-subheader anwp-text-uppercase anwp-text-base anwp-bg-light p-1 mb-1"><?php echo esc_html( AnWPFL_Text::get_value( 'match__lineups__coach', __( 'Coach', 'anwp-football-leagues' ) ) ); ?></div>
					<div class="match__player-wrapper d-flex align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
						<?php
						if ( 'temp' === $data['coach_home'] ) :
							$coach_data = (object) wp_parse_args(
								$temp_coaches->coachHome,
								[
									'country' => '',
									'name'    => '',
								]
							);

							if ( $coach_data->country ) :
								anwp_football_leagues()->load_partial(
									[
										'class'         => 'options__flag mr-1 d-flex align-items-center',
										'wrapper_class' => 'mr-1',
										'size'          => 16,
										'country_code'  => $coach_data->country,
									],
									'general/flag'
								);
							endif;

							echo '<span class="match__player-name-wrapper mr-auto ml-1">' . esc_html( $coach_data->name ) . '</span>';
						else :
							$coach_nationality = get_post_meta( $data['coach_home'], '_anwpfl_nationality', true );
							if ( ! empty( $coach_nationality ) && is_array( $coach_nationality ) ) :
								?>
								<div class="match__player-flag mr-2 anwp-leading-1">
									<?php
									foreach ( $coach_nationality as $country_code ) :
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $country_code,
											],
											'general/flag'
										);
									endforeach;
									?>
								</div>
							<?php endif; ?>
							<div class="match__player-name-wrapper mr-auto ml-1">
								<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( get_permalink( $data['coach_home'] ) ); ?>"><?php echo esc_html( get_the_title( $data['coach_home'] ) ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php
			anwp_football_leagues()->load_partial(
				[
					'club_id' => $data['away_club'],
					'class'   => 'match-lineups__away-club mt-3 mt-sm-0',
					'is_home' => false,
				],
				'club/club-title'
			);

			/*
			|--------------------------------------------------------------------------
			| Away Club Line Ups
			|--------------------------------------------------------------------------
			*/
			$away_line_up = $away_line_up ? explode( ',', $away_line_up ) : [];

			if ( ! empty( $away_line_up ) && is_array( $away_line_up ) ) :
				?>
				<div class="match-lineups__away-starting">
					<?php
					foreach ( $away_line_up as $player_id ) :

						$player      = $data['players'][ $player_id ] ?? '';
						$temp_player = false;

						if ( 'temp__' === mb_substr( $player_id, 0, 6 ) && isset( $temp_players[ $player_id ] ) ) :
							$temp_player = $temp_players[ $player_id ];
						endif;

						if ( empty( $player ) && empty( $temp_player ) ) :
							continue;
						endif;

						$is_captain = $data['captain_away'] && ( ( 'temp__' === mb_substr( $player_id, 0, 6 ) ? $data['captain_away'] === $player_id : absint( $data['captain_away'] ) === absint( $player_id ) ) );
						?>
						<div class="match__player-wrapper d-flex flex-wrap align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
							<div class="d-flex align-items-center">
								<div class="match__player-number anwp-bg-light anwp-leading-1-25 mr-2">
									<?php
									$player_number = '&nbsp;';

									if ( ! empty( $data['custom_numbers']->{$player_id} ) ) {
										$player_number = (int) $data['custom_numbers']->{$player_id};
									} elseif ( isset( $away_squad[ $player_id ] ) && $away_squad[ $player_id ]['number'] ) {
										$player_number = (int) $away_squad[ $player_id ]['number'];
									}

									echo esc_html( $player_number );
									?>
								</div>

								<?php if ( $temp_player && $temp_player->country ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $temp_player->country,
											],
											'general/flag'
										);
										?>
									</div>
								<?php elseif ( $player['nationality'] ?? '' ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $player['nationality'],
											],
											'general/flag'
										);
										?>
									</div>
								<?php endif; ?>

								<?php if ( $temp_player && $temp_player->position ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $temp_players[ $player_id ]->position ] ); ?>
									</div>
								<?php elseif ( ! empty( $away_squad[ $player_id ] ) && isset( $positions_l10n[ $away_squad[ $player_id ]['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $away_squad[ $player_id ]['position'] ] ); ?>
									</div>
								<?php elseif ( ! empty( $player['position'] ) && isset( $positions_l10n[ $player['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $player['position'] ] ); ?>
									</div>
								<?php endif; ?>

								<div class="match__player-name-wrapper ml-1">
									<?php if ( $temp_player ) : ?>
										<span class="match__player-name"><?php echo esc_html( $temp_player->name ); ?></span>
									<?php else : ?>
										<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( $player['link'] ); ?>"><?php echo esc_html( $player['short_name'] ); ?></a>
									<?php endif; ?>
								</div>

								<?php if ( $is_captain ) : ?>
									<div class="match__player-captain ml-1">
										<svg class="anwp-icon anwp-icon--s20 anwp-icon--octi">
											<use xlink:href="#icon-captain"></use>
										</svg>
									</div>
								<?php endif; ?>
							</div>
							<div class="d-flex align-items-center ml-auto pl-2">
								<?php
								if ( ! empty( $events[ $player_id ] ) ) :
									foreach ( $events[ $player_id ] as $evt ) :
										echo isset( $event_icons[ $evt['type'] ] ) ? $event_icons[ $evt['type'] ] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										if ( $show_event_minutes && absint( $evt['minute'] ) ) {
											echo '<span class="mr-1 anwp-text-xs anwp-opacity-70 anwp-fl-lineups-event-minutes">' . absint( $evt['minute'] ) . '\'</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			endif;

			/*
			|--------------------------------------------------------------------------
			| Away Club Substitutions
			|--------------------------------------------------------------------------
			*/
			$away_subs = $away_subs ? explode( ',', $away_subs ) : [];

			if ( ! empty( $away_subs ) && is_array( $away_subs ) ) :
				?>
				<div class="match-lineups__away-subs">
					<div class="anwp-fl-subheader anwp-text-uppercase anwp-text-base anwp-bg-light p-1 mb-1"><?php echo esc_html( AnWPFL_Text::get_value( 'match__lineups__substitutes', __( 'Substitutes', 'anwp-football-leagues' ) ) ); ?></div>

					<?php
					foreach ( $away_subs as $player_id ) :
						$player      = $data['players'][ $player_id ] ?? '';
						$temp_player = false;

						if ( 'temp__' === mb_substr( $player_id, 0, 6 ) && isset( $temp_players[ $player_id ] ) ) :
							$temp_player = $temp_players[ $player_id ];
						endif;

						if ( empty( $player ) && empty( $temp_player ) ) :
							continue;
						endif;
						?>
						<div class="match__player-wrapper d-flex flex-wrap align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
							<div class="d-flex align-items-center">
								<div class="match__player-number anwp-bg-light anwp-leading-1-25 mr-2">
									<?php
									$player_number = '&nbsp;';

									if ( ! empty( $data['custom_numbers']->{$player_id} ) ) {
										$player_number = (int) $data['custom_numbers']->{$player_id};
									} elseif ( isset( $away_squad[ $player_id ] ) && $away_squad[ $player_id ]['number'] ) {
										$player_number = (int) $away_squad[ $player_id ]['number'];
									}

									echo esc_html( $player_number );
									?>
								</div>

								<?php if ( $temp_player && $temp_player->country ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $temp_player->country,
											],
											'general/flag'
										);
										?>
									</div>
								<?php elseif ( $player['nationality'] ?? '' ) : ?>
									<div class="match__player-flag mr-2 anwp-leading-1">
										<?php
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $player['nationality'],
											],
											'general/flag'
										);
										?>
									</div>
								<?php endif; ?>

								<?php if ( $temp_player && $temp_player->position ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $temp_player->position ] ); ?>
									</div>
								<?php elseif ( ! empty( $away_squad[ $player_id ] ) && isset( $positions_l10n[ $away_squad[ $player_id ]['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $away_squad[ $player_id ]['position'] ] ); ?>
									</div>
								<?php elseif ( ! empty( $player['position'] ) && isset( $positions_l10n[ $player['position'] ] ) ) : ?>
									<div class="match__player-position mr-2 anwp-text-nowrap anwp-fl-border anwp-border-light anwp-leading-1-25 anwp-bg-light anwp-text-sm">
										<?php echo esc_html( $positions_l10n[ $player['position'] ] ); ?>
									</div>
								<?php endif; ?>

								<div class="match__player-name-wrapper mr-auto ml-1">
									<?php if ( $temp_player ) : ?>
										<span class="match__player-name"><?php echo esc_html( $temp_player->name ); ?></span>
									<?php else : ?>
										<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( $player['link'] ); ?>"><?php echo esc_html( $player['short_name'] ); ?></a>
									<?php endif; ?>
								</div>
							</div>
							<div class="d-flex align-items-center ml-auto pl-2">
								<?php
								if ( ! empty( $events[ $player_id ] ) ) :
									foreach ( $events[ $player_id ] as $evt ) :
										echo isset( $event_icons[ $evt['type'] ] ) ? $event_icons[ $evt['type'] ] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										if ( $show_event_minutes && absint( $evt['minute'] ) ) {
											echo '<span class="mr-1 anwp-text-xs anwp-opacity-70 anwp-fl-lineups-event-minutes">' . absint( $evt['minute'] ) . '\'</span>';
										}
									endforeach;
								endif;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
			endif;

			/*
			|--------------------------------------------------------------------------
			| Away Club Coach
			|--------------------------------------------------------------------------
			*/
			if ( ! empty( $data['coach_away'] ) ) :
				?>
				<div class="match-lineups__away-coach">
					<div class="anwp-fl-subheader anwp-text-uppercase anwp-text-base anwp-bg-light p-1 mb-1"><?php echo esc_html( AnWPFL_Text::get_value( 'match__lineups__coach', __( 'Coach', 'anwp-football-leagues' ) ) ); ?></div>

					<div class="match__player-wrapper d-flex align-items-center anwp-fl-border-bottom anwp-border-light anwp-leading-1 anwp-text-base py-1">
						<?php
						if ( 'temp' === $data['coach_away'] ) :
							$coach_data = (object) wp_parse_args(
								$temp_coaches->coachAway,
								[
									'country' => '',
									'name'    => '',
								]
							);

							if ( $coach_data->country ) :
								anwp_football_leagues()->load_partial(
									[
										'class'         => 'options__flag mr-1 d-flex align-items-center',
										'wrapper_class' => 'mr-1',
										'size'          => 16,
										'country_code'  => $coach_data->country,
									],
									'general/flag'
								);
							endif;

							echo '<span class="match__player-name-wrapper mr-auto ml-1">' . esc_html( $coach_data->name ) . '</span>';
						else :
							$coach_nationality = get_post_meta( $data['coach_away'], '_anwpfl_nationality', true );
							if ( ! empty( $coach_nationality ) && is_array( $coach_nationality ) ) :
								?>
								<div class="match__player-flag mr-2 anwp-leading-1">
									<?php
									foreach ( $coach_nationality as $country_code ) :
										anwp_football_leagues()->load_partial(
											[
												'class'        => 'options__flag',
												'size'         => 16,
												'country_code' => $country_code,
											],
											'general/flag'
										);
									endforeach;
									?>
								</div>
							<?php endif; ?>
							<div class="match__player-name-wrapper mr-auto ml-1">
								<a class="anwp-link-without-effects match__player-name" href="<?php echo esc_url( get_permalink( $data['coach_away'] ) ); ?>"><?php echo esc_html( get_the_title( $data['coach_away'] ) ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
endif;
