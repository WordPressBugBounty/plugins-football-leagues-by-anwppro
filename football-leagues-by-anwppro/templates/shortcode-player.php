<?php
/**
 * The Template for displaying Player.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/shortcode-player.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.8.0
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = wp_parse_args(
	$data,
	[
		'player_id'         => '',
		'options_text'      => '',
		'context'           => 'shortcode',
		'profile_link'      => 'yes',
		'profile_link_text' => 'profile',
		'show_club'         => 0,
	]
);

if ( empty( $data['player_id'] ) ) {
	return;
}

$player = anwp_fl()->player->get_player_data( $data['player_id'] );

if ( empty( $player ) || absint( $player['player_id'] ) !== absint( $data['player_id'] ) ) {
	return;
}
?>
<div class="anwp-b-wrap">
	<div class="player-block anwp-border anwp-border-light context--<?php echo esc_attr( $data['context'] ); ?>">
		<div class="d-flex align-items-center p-2 anwp-bg-light player-block__header">
			<?php if ( $player['photo'] ) : ?>
				<img loading="lazy" class="anwp-w-80 anwp-h-80 anwp-object-contain m-0"
						src="<?php echo esc_url( anwp_fl()->upload_dir . $player['photo'] ); ?>" alt="<?php echo esc_attr( $player['name'] ); ?>">
			<?php endif; ?>
			<div class="flex-grow-1">
				<div class="player-block__name px-3 anwp-text-base anwp-leading-1-25 anwp-font-medium pl-3"><?php echo esc_html( $player['short_name'] ); ?></div>

				<?php if ( ! empty( $player['nationalities'] ?? '' ) || anwp_fl()->player->get_position_l10n( $player['position'] ) ) : ?>
					<div class="player-block__extra d-flex align-items-center mt-2 pl-3 anwp-text-sm">
						<?php
						foreach ( $player['nationalities'] as $country_code ) :
							anwp_fl()->load_partial(
								[
									'class'         => 'options__flag mr-3',
									'wrapper_class' => 'mr-2',
									'size'         => 32,
									'country_code' => $country_code,
								],
								'general/flag'
							);
						endforeach;
						?>
						<span><?php echo esc_html( anwp_fl()->player->get_position_l10n( $player['position'] ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="player-block__options">

			<?php if ( AnWP_Football_Leagues::string_to_bool( $data['show_club'] ) && $player['team_id'] ) : ?>
				<div class="player-block__option d-flex align-items-center anwp-border-top anwp-border-light py-1">
					<div class="player-block__option-label flex-grow-1 anwp-text-sm"><?php echo esc_html( AnWPFL_Text::get_value( 'player__shortcode__club', __( 'Club', 'anwp-football-leagues' ) ) ); ?></div>
					<div class="player-block__option-value player-block__option-value--wide px-1 d-flex align-items-center flex-wrap">
						<?php
						$club_title = anwp_fl()->club->get_club_title_by_id( $player['team_id'] );
						$club_logo  = anwp_fl()->club->get_club_logo_by_id( $player['team_id'] );
						$club_link  = anwp_fl()->club->get_club_link_by_id( $player['team_id'] );
						?>
						<a class="player-block__club" href="<?php echo esc_url( $club_link ); ?>">
							<?php echo esc_html( $club_title ); ?>
						</a>
						<?php if ( $club_logo ) : ?>
							<img loading="lazy" width="30" height="30" class="ml-2 anwp-object-contain anwp-w-30 anwp-h-30" src="<?php echo esc_attr( $club_logo ); ?>" alt="<?php echo esc_attr( $club_title ); ?>">
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( trim( $data['options_text'] ) ) : ?>

				<?php
				$player_options = explode( '|', $data['options_text'] );

				foreach ( $player_options as $player_option ) :

					if ( ! trim( $player_option ) ) {
						continue;
					}

					if ( false === mb_strpos( $player_option, ':' ) ) {
						continue;
					}

					list( $label, $value ) = explode( ':', $player_option );
					?>
					<div class="player-block__option d-flex align-items-center anwp-border-top anwp-border-light py-2">
						<div class="player-block__option-label flex-grow-1 anwp-text-sm"><?php echo esc_html( trim( $label ) ); ?></div>
						<div class="player-block__option-value border-left anwp-text-sm"><?php echo esc_html( trim( $value ) ); ?></div>
					</div>
				<?php endforeach; ?>

			<?php endif; ?>
		</div>

		<?php if ( AnWP_Football_Leagues::string_to_bool( $data['profile_link'] ) ) : ?>
			<div class="player-block__profile-link anwp-border-top anwp-border-light p-2">
				<div class="position-relative anwp-fl-btn-outline anwp-text-sm w-100 player-block__profile-link-btn">
					<?php echo esc_html( $data['profile_link_text'] ); ?>
					<a href="<?php echo esc_url( get_permalink( $player['player_id'] ) ); ?>" class="anwp-link-cover anwp-link-without-effects" aria-label="<?php echo esc_attr( $data['profile_link_text'] ); ?>"></a>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
