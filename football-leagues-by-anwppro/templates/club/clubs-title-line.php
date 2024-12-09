<?php
/**
 * The Template for displaying Clubs >> Title Line
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/club/clubs-title-line.php.
 *
 * @since         0.16.11
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @var object $data - Object with args.
 *
 * @version       0.16.11
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Parse template data
$data = wp_parse_args(
	$data,
	[
		'home_club' => '',
		'away_club' => '',
	]
);

if ( ! absint( $data['home_club'] ) || ! absint( $data['away_club'] ) ) {
	return;
}
?>
<div class="d-flex clubs-title-line">
	<div class="anwp-flex-1 anwp-min-width-0 anwp-text-truncate-multiline">
		<?php
		anwp_fl()->load_partial(
			[
				'club_id'     => $data['home_club'],
				'class_title' => 'anwp-text-truncate-multiline',
			],
			'club/club-title'
		);
		?>
	</div>
	<div class="anwp-flex-1 anwp-min-width-0">
		<?php
		anwp_fl()->load_partial(
			[
				'club_id'     => $data['away_club'],
				'is_home'     => false,
				'class_title' => 'anwp-text-truncate-multiline',
			],
			'club/club-title'
		);
		?>
	</div>
</div>
