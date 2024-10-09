<?php
/**
 * Tools Export page for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.12.0
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'anwp-football-leagues' ) );
}
?>

<h1 class="my-3">Export CSV files</h1>

<h3>Export Players</h3>
<div class="d-inline-block">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=anwp-settings-tools&tab=csv-export&anwp_export=players' ) ); ?>" class="button button-secondary anwp-w-300 py-2 my-2 text-center">Export Players</a>
</div>

<hr>

<h3>Export Games</h3>
<p class="my-2 anwp-text-xs">* competition required</p>
<div class="d-flex flex-column">
	<select id="anwp-import-games-competition">
		<option value="">- select competition -</option>
		<?php foreach ( anwp_fl()->competition->get_competitions() as $competition ) : ?>
			<option value="<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->title ); ?></option>
		<?php endforeach; ?>
	</select>

	<div class="d-inline-block">
		<a id="anwp-import-games" href="#" class="button button-secondary anwp-w-300 py-2 my-2 text-center">Export Games</a>
	</div>
	<a id="anwp-import-games-download" href="#" data-href="<?php echo esc_url( admin_url( 'admin.php?page=anwp-settings-tools&tab=csv-export&anwp_export=games&competition_id=' ) ); ?>" class="d-none anwp-opacity-0"></a>
</div>

<br>

<script>
	(function( $ ) {
		$( function() {
			const $gamesBtn           = $( '#anwp-import-games' );
			const $gamesDownloadBtn   = $( '#anwp-import-games-download' );
			const $gamesCompetitionId = $( '#anwp-import-games-competition' );

			$gamesBtn.on( 'click', function( e ) {
				e.preventDefault();

				if ( ! $gamesCompetitionId.val() ) {
					alert( 'Competition ID not set' );
					return false;
				}

				$gamesDownloadBtn.attr( 'href', $gamesDownloadBtn.data( 'href' ) + Number( $gamesCompetitionId.val() ) );
				$gamesDownloadBtn[0].click();
			} );

		} );
	}( jQuery ) );
</script>
