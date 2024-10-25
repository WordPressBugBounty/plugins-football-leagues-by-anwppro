<?php
/**
 * The Template for displaying Player >> Birthday Card.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/player/player-birthday-card.php.
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.10.19
 *
 * @version       0.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Parse template data
$data = wp_parse_args(
	$data,
	[
		'ID'            => '',
		'current_club'  => '',
		'date_of_birth' => '',
		'post_title'    => '',
		'post_type'     => '',
		'photo'         => '',
		'position'      => '',
	]
);

if ( ! anwp_fl()->helper->validate_date( $data['date_of_birth'], 'Y-m-d' ) ) {
	return;
}

$default_photo  = anwp_fl()->helper->get_default_player_photo();
$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $data['date_of_birth'] );
$diff_date_obj  = DateTime::createFromFormat( 'Y-m-d', date( 'Y' ) . '-' . date( 'm-d', strtotime( $data['date_of_birth'] ) ) );
$age            = $birth_date_obj->diff( $diff_date_obj )->y;
?>
<div class="player__birthday-card player-birthday-card anwp-fl-border anwp-border-light d-flex align-items-start position-relative">
	<div class="player-birthday-card__photo-wrapper anwp-flex-none">
		<img loading="lazy" width="80" height="80" class="player-birthday-card__photo anwp-object-contain m-2 anwp-w-80 anwp-h-80" src="<?php echo esc_url( $data['photo'] ?: $default_photo ); ?>" alt="<?php echo esc_attr( $data['player_name'] ); ?>">
	</div>
	<div class="d-flex flex-column flex-grow-1 player-birthday-card__meta py-2 pl-1">
		<div class="player-birthday-card__name mb-1 anwp-text-lg anwp-leading-1-5 mb-2"><?php echo esc_html( $data['player_name'] ); ?></div>

		<?php if ( $data['position'] ) : ?>
			<div class="player-birthday-card__position anwp-text-sm anwp-leading-1-5"><?php echo esc_html( $data['position'] ); ?></div>
		<?php endif; ?>
		<?php
		if ( absint( $data['current_club'] ) ) :

			$club_title = anwp_fl()->club->get_club_abbr_by_id( $data['current_club'] );
			$club_logo  = anwp_fl()->club->get_club_logo_by_id( $data['current_club'] );
			?>
			<div class="player-birthday-card__club-wrapper d-flex align-items-center anwp-leading-1-5 mt-1 anwp-text-sm">
				<?php if ( $club_logo ) : ?>
					<img loading="lazy" width="20" height="20" class="mr-1 anwp-object-contain anwp-w-20 anwp-h-20" src="<?php echo esc_attr( $club_logo ); ?>" alt="<?php echo esc_attr( $club_title ); ?>">
				<?php endif; ?>
				<?php echo esc_html( $club_title ); ?>
			</div>
		<?php endif; ?>

		<div class="player-birthday-card__date-wrapper d-flex align-items-end">
			<div class="player-birthday-card__date d-flex align-items-center">
				<svg class="anwp-icon anwp-icon--octi mr-1">
					<use xlink:href="#icon-calendar"></use>
				</svg>
				<span class="player-birthday-card__date-text anwp-text-sm"><?php echo esc_html( date_i18n( 'M d', get_date_from_gmt( $data['date_of_birth'], 'U' ) ) ); ?></span>
			</div>
			<div class="player-birthday-card__years ml-auto anwp-text-xs">
				<?php echo esc_html( AnWPFL_Text::get_value( 'player__birthday__years', __( 'years', 'anwp-football-leagues' ) ) ); ?>
			</div>
			<div class="player-birthday-card__age px-1 anwp-text-2xl anwp-leading-1"><?php echo absint( $age ); ?></div>
		</div>
	</div>
</div>
