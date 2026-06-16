<?php
/**
 * The Template for displaying Club >> Subteams Section.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/club/club-subteams.php.
 *
 * @var object $data - Object with args.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.15.0
 *
 * @version       0.18.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Parse template data
$data = (object) wp_parse_args(
	$data,
	[
		'club_id' => '',
	]
);

$club_row       = anwp_fl()->club->get_row( (int) $data->club_id );
$subteam_status = $club_row['subteams'] ?? '';
$root_team_id   = 'root' === $subteam_status ? $data->club_id : ( $club_row['root_team'] ?? 0 );

if ( ! absint( $root_team_id ) ) {
	return;
}

$root_row        = (int) $root_team_id === (int) $data->club_id ? $club_row : anwp_fl()->club->get_row( (int) $root_team_id );
$root_details    = json_decode( $root_row['club_details'] ?? '{}', true );
$subteam_list    = $root_details['subteam_list'] ?? [];
$root_team_title = $root_details['root_team_title'] ?? '';

if ( empty( $subteam_list ) ) {
	return;
}
?>
<div class="club-subteams anwp-bg-light px-3 pb-3 d-flex flex-wrap">
	<div class="m-1 club-subteams__item anwp-fl-btn d-flex align-items-center position-relative py-0 <?php echo 'root' === $subteam_status ? 'club-subteams__item--active anwp-cursor-default' : ''; ?>">
		<?php echo esc_html( $root_team_title ); ?>
		<?php if ( 'root' !== $subteam_status ) : ?>
			<a href="<?php echo esc_url( get_permalink( $root_team_id ) ); ?>" class="anwp-link-without-effects text-decoration-none anwp-link-cover" aria-label="<?php echo esc_attr( $root_team_title ); ?>"></a>
		<?php endif; ?>
	</div>

	<?php
	foreach ( $subteam_list as $subteam_item ) :
		// Subteam IDs ship in two shapes: legacy 'subteam' key (postmeta era,
		// pre-0.18.0) and new 'club_id' key (Vue editor + sanitize_subteam_list
		// since 0.17.3). Read both so site-migrated and legacy data render
		// without "Undefined array key" warnings.
		$subteam_id = absint( $subteam_item['club_id'] ?? ( $subteam_item['subteam'] ?? 0 ) );
		?>
		<div class="m-1 club-subteams__item anwp-fl-btn d-flex align-items-center position-relative py-0 <?php echo absint( $data->club_id ) === $subteam_id ? 'club-subteams__item--active anwp-cursor-default' : ''; ?>">
			<?php echo esc_html( $subteam_item['title'] ?? '' ); ?>
			<?php if ( absint( $data->club_id ) !== $subteam_id ) : ?>
				<a href="<?php echo esc_url( get_permalink( $subteam_id ) ); ?>" class="anwp-link-without-effects text-decoration-none anwp-link-cover" aria-label="<?php echo esc_attr( $subteam_item['title'] ?? '' ); ?>"></a>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
