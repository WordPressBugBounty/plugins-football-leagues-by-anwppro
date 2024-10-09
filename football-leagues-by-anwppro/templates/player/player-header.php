<?php
/**
 * The Template for displaying Player >> Header Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/player/player-header.php.
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.8.3
 *
 * @version       0.16.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Parse template data
$player = wp_parse_args(
	$data,
	[
		'club_id'           => '',
		'club_link'         => '',
		'club_title'        => '',
		'country_of_birth'  => '',
		'date_of_birth'     => '',
		'date_of_death'     => '',
		'full_name'         => '',
		'height'            => '',
		'name'              => '',
		'national_team'     => '',
		'nationality'       => '',
		'nationality_extra' => '',
		'photo'             => '',
		'place_of_birth'    => '',
		'player_id'         => '',
		'position'          => '',
		'short_name'        => '',
		'show_selector'     => true,
		'team_id'           => '',
		'weight'            => '',
	]
);

// Socials
$player['twitter']   = get_post_meta( $player['player_id'], '_anwpfl_twitter', true );
$player['youtube']   = get_post_meta( $player['player_id'], '_anwpfl_youtube', true );
$player['facebook']  = get_post_meta( $player['player_id'], '_anwpfl_facebook', true );
$player['instagram'] = get_post_meta( $player['player_id'], '_anwpfl_instagram', true );
$player['vk']        = get_post_meta( $player['player_id'], '_anwpfl_vk', true );
$player['linkedin']  = get_post_meta( $player['player_id'], '_anwpfl_linkedin', true );
$player['tiktok']    = get_post_meta( $player['player_id'], '_anwpfl_tiktok', true );

// Position
$player_position = anwp_fl()->player->get_position_l10n( $player['position'] );

/**
 * Hook: anwpfl/tmpl-player/before_header
 *
 * @since 0.8.3
 *
 * @param object $player
 * @param object $player
 */
do_action( 'anwpfl/tmpl-player/before_header', $player );

$render_main_photo_caption = 'hide' !== anwp_fl()->customizer->get_value( 'player', 'player_render_main_photo_caption' );

/**
 * Rendering player main photo caption.
 *
 * @param string $render_main_photo_caption
 * @param int    $player - >player_id
 *
 * @since 0.7.5
 *
 */
$render_main_photo_caption = apply_filters( 'anwpfl/tmpl-player/render_main_photo_caption', $render_main_photo_caption, $player['player_id'] );

$has_date_of_death = $player['date_of_death'] && '0000-00-00' !== $player['date_of_death'];

/*
|--------------------------------------------------------------------
| Get Player Team ID
|--------------------------------------------------------------------
*/
if ( 'hide' === anwp_fl()->customizer->get_value( 'player', 'current_team' ) ) {
	$player['club_id'] = '';
} elseif ( 'last' === anwp_fl()->customizer->get_value( 'player', 'current_team' ) ) {
	$player['club_id'] = anwp_fl()->player->get_player_last_team( $player['player_id'] );
}
?>
<div class="player-header anwp-section d-sm-flex anwp-bg-light p-3">

	<?php
	if ( $player['photo'] && $render_main_photo_caption ) :
		$photo_id   = AnWPFL_Helper::get_image_id_by_url( anwp_fl()->upload_dir . $player['photo'] );
		$caption    = wp_get_attachment_caption( $photo_id );
		$photo_html = wp_get_attachment_image( $photo_id, 'medium', false, [ 'class' => 'anwp-object-contain anwp-w-120 anwp-h-120 player-header__photo player-header__photo-w-caption' ] );
		?>
		<div class="player-header__photo-wrapper player-header__photo-wrapper-caption anwp-flex-sm-none anwp-text-center mr-sm-4 mb-3 mb-sm-0">
			<?php if ( ! empty( trim( $photo_html ) ) ) : ?>
				<?php echo $photo_html; //phpcs:ignore ?>
			<?php else : ?>
				<img class="anwp-object-contain anwp-w-120 anwp-h-120 player-header__photo" loading="lazy" alt="<?php echo esc_attr( $player['full_name'] ); ?>"
						width="120" height="120"
						src="<?php echo esc_url( wp_upload_dir()['baseurl'] . $player['photo'] ); ?>" />
			<?php endif; ?>

			<?php if ( $render_main_photo_caption && $caption ) : ?>
				<div class="mt-1 player-header__photo-caption anwp-text-sm anwp-opacity-80"><?php echo esc_html( $caption ); ?></div>
			<?php endif; ?>
		</div>
	<?php elseif ( $player['photo'] ) : ?>
		<div class="player-header__photo-wrapper anwp-flex-sm-none anwp-text-center mr-sm-4 mb-3 mb-sm-0">
			<img class="anwp-object-contain anwp-w-120 anwp-h-120 player-header__photo" loading="lazy" alt="<?php echo esc_attr( $player['full_name'] ); ?>"
					width="120" height="120"
					src="<?php echo esc_url( wp_upload_dir()['baseurl'] . $player['photo'] ); ?>" />
		</div>
	<?php endif; ?>

	<div class="anwp-flex-auto player-header__inner">
		<div class="anwp-grid-table player-header__options anwp-text-base anwp-border-light">

			<?php if ( $player['full_name'] ) : ?>
				<div class="player-header__option__full_name player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__full_name', __( 'Full Name', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__full_name player-header__option-value">
					<?php echo esc_html( $player['full_name'] ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $player['position'] ) : ?>
				<div class="player-header__option__position player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__position', __( 'Position', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__position player-header__option-value">
					<?php echo esc_html( $player_position ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $player['national_team'] && anwp_fl()->club->get_club_title_by_id( $player['national_team'] ) && anwp_fl()->club->is_national_team( $player['national_team'] ) ) : ?>
				<?php $club_logo = anwp_fl()->club->get_club_logo_by_id( $player['national_team'] ); ?>
				<div class="player-header__option__national_team player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__national_team', __( 'National Team', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__national_team player-header__option-value">
					<div class="d-flex align-items-center">
						<?php if ( $club_logo ) : ?>
							<img loading="lazy" width="30" height="30" class="mr-2 anwp-object-contain anwp-w-30 anwp-h-30" src="<?php echo esc_attr( $club_logo ); ?>" alt="club logo">
						<?php endif; ?>
						<a class="anwp-leading-1-25" href="<?php echo esc_url( anwp_fl()->club->get_club_link_by_id( $player['national_team'] ) ); ?>"><?php echo esc_html( anwp_fl()->club->get_club_title_by_id( $player['national_team'] ) ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php
			if ( $player['club_id'] && anwp_fl()->club->get_club_title_by_id( $player['club_id'] ) ) :
				$club_logo = anwp_fl()->club->get_club_logo_by_id( $player['club_id'] );
				?>
				<div class="player-header__option__club_id player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__current_club', __( 'Current Club', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__club_id player-header__option-value">
					<div class="d-flex align-items-center">
						<?php if ( $club_logo ) : ?>
							<img loading="lazy" width="30" height="30" class="mr-2 anwp-object-contain anwp-w-30 anwp-h-30" src="<?php echo esc_attr( $club_logo ); ?>" alt="club logo">
						<?php endif; ?>
						<a class="anwp-leading-1-25" href="<?php echo esc_url( anwp_fl()->club->get_club_link_by_id( $player['club_id'] ) ); ?>"><?php echo esc_html( anwp_fl()->club->get_club_title_by_id( $player['club_id'] ) ); ?></a>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $player['nationalities'] ) && is_array( $player['nationalities'] ) ) : ?>
				<div class="player-header__option__nationality player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__nationality', __( 'Nationality', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__nationality player-header__option-value">
					<?php
					foreach ( $player['nationalities'] as $country_code ) :
						anwp_fl()->load_partial(
							[
								'class'        => 'options__flag',
								'size'         => 32,
								'width'        => 25,
								'country_code' => $country_code,
							],
							'general/flag'
						);
					endforeach;
					?>
				</div>
			<?php endif; ?>

			<?php if ( $player['place_of_birth'] ) : ?>
				<div class="player-header__option__place_of_birth player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__place_of_birth', __( 'Place Of Birth', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__place_of_birth player-header__option-value">
					<div class="d-flex align-items-center">
						<?php
						echo esc_html( $player['place_of_birth'] );
						if ( $player['country_of_birth'] ) :
							anwp_fl()->load_partial(
								[
									'class'         => 'options__flag ml-2',
									'wrapper_class' => 'ml-2',
									'size'          => 32,
									'width'         => 25,
									'country_code'  => $player['country_of_birth'],
								],
								'general/flag'
							);
						endif;
						?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $player['date_of_birth'] && '0000-00-00' !== $player['date_of_birth'] ) : ?>
				<div class="player-header__option__birth_date player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__date_of_birth', __( 'Date Of Birth', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__birth_date player-header__option-value">
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $player['date_of_birth'] ) ) ); ?>
				</div>
				<?php
				if ( ! $has_date_of_death ) :
					$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $player['date_of_birth'] );
					$interval       = $birth_date_obj ? $birth_date_obj->diff( new DateTime() )->y : '-';
					?>
					<div class="player-header__option__age player-header__option-title anwp-text-sm">
						<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__age', __( 'Age', 'anwp-football-leagues' ) ) ); ?>
					</div>
					<div class="player-header__option__age player-header__option-value">
						<?php echo esc_html( $interval ); ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			if ( $has_date_of_death ) :
				$death_age = '';

				if ( $player['date_of_birth'] ) {
					$birth_date_obj = DateTime::createFromFormat( 'Y-m-d', $player['date_of_birth'] );
					$death_age      = $birth_date_obj ? $birth_date_obj->diff( DateTime::createFromFormat( 'Y-m-d', $player['date_of_death'] ) )->y : '-';
				}
				?>
				<div class="player-header__option__death_date player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__date_of_death', __( 'Date Of Death', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__death_date player-header__option-value">
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $player['date_of_death'] ) ) ); ?>
					<?php echo intval( $death_age ) ? esc_html( ' (' . intval( $death_age ) . ')' ) : ''; ?>
				</div>
			<?php endif; ?>

			<?php if ( $player['weight'] ) : ?>
				<div class="player-header__option__weight player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__weight_kg', __( 'Weight (kg)', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__weight player-header__option-value">
					<?php echo esc_html( $player['weight'] ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $player['height'] ) : ?>
				<div class="player-header__option__height player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'player__header__height_cm', __( 'Height (cm)', 'anwp-football-leagues' ) ) ); ?>
				</div>
				<div class="player-header__option__height player-header__option-value">
					<?php echo esc_html( $player['height'] ); ?>
				</div>
			<?php endif; ?>

			<?php
			// Rendering custom fields
			for ( $ii = 1; $ii <= 3; $ii ++ ) :

				$custom_title = get_post_meta( $player['player_id'], '_anwpfl_custom_title_' . $ii, true );
				$custom_value = get_post_meta( $player['player_id'], '_anwpfl_custom_value_' . $ii, true );

				if ( $custom_title && $custom_value ) :
					?>
					<div class="player-header__option__<?php echo esc_attr( $custom_title ); ?> player-header__option-title anwp-text-sm">
						<?php echo esc_html( $custom_title ); ?>
					</div>
					<div class="player-header__option__<?php echo esc_attr( $custom_title ); ?> player-header__option-value">
						<?php echo do_shortcode( esc_html( $custom_value ) ); ?>
					</div>
					<?php
				endif;
			endfor;

			// Rendering dynamic custom fields - @since v0.10.17
			$custom_fields = get_post_meta( $player['player_id'], '_anwpfl_custom_fields', true );

			if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $field_title => $field_text ) {
					if ( empty( $field_text ) ) {
						continue;
					}
					?>
					<div class="player-header__option__<?php echo esc_attr( $field_title ); ?> player-header__option-title anwp-text-sm">
						<?php echo esc_html( $field_title ); ?>
					</div>
					<div class="player-header__option__<?php echo esc_attr( $field_title ); ?> player-header__option-value">
						<?php echo do_shortcode( esc_html( $field_text ) ); ?>
					</div>
					<?php
				}
			}
			?>

			<?php if ( $player['twitter'] || $player['facebook'] || $player['youtube'] || $player['instagram'] || $player['vk'] || $player['linkedin'] || $player['tiktok'] ) : ?>
				<div class="player-header__option__social player-header__option-title anwp-text-sm">
					<?php echo esc_html( AnWPFL_Text::get_value( 'club__header__social', __( 'Social', 'anwp-football-leagues' ) ) ); ?>
				</div>

				<div class="player-header__option-value d-flex flex-wrap align-items-center py-2">
					<?php if ( $player['twitter'] ) : ?>
						<a href="<?php echo esc_url( $player['twitter'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s24 anwp-icon--social">
								<use xlink:href="#icon-twitter"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['youtube'] ) : ?>
						<a href="<?php echo esc_url( $player['youtube'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-youtube"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['facebook'] ) : ?>
						<a href="<?php echo esc_url( $player['facebook'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-facebook"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['instagram'] ) : ?>
						<a href="<?php echo esc_url( $player['instagram'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-instagram"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['linkedin'] ) : ?>
						<a href="<?php echo esc_url( $player['linkedin'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-linkedin"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['tiktok'] ) : ?>
						<a href="<?php echo esc_url( $player['tiktok'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-tiktok"></use>
							</svg>
						</a>
					<?php endif; ?>
					<?php if ( $player['vk'] ) : ?>
						<a href="<?php echo esc_url( $player['vk'] ); ?>" class="anwp-link-without-effects mr-2 mb-0 anwp-leading-1 d-inline-block" target="_blank">
							<svg class="anwp-icon anwp-icon--s30 anwp-icon--social">
								<use xlink:href="#icon-vk"></use>
							</svg>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
if ( $player['show_selector'] ) {
	anwp_fl()->load_partial(
		[
			'selector_context' => 'player',
			'selector_id'      => $player['player_id'],
			'season_id'        => $player['current_season_id'],
		],
		'general/season-selector'
	);
}
