<?php
/**
 * Toolbox Cleanup subpage for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.18.0
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

/*
|--------------------------------------------------------------------------
| Check migration state and count remaining postmeta
|--------------------------------------------------------------------------
*/
global $wpdb;

// --- Club postmeta ---
$clubs_migrated = (bool) get_option( 'anwpfl_clubs_migrated' );
$clubs_cleaned  = (bool) get_option( 'anwpfl_clubs_postmeta_cleaned' );
$club_meta_count = 0;

if ( $clubs_migrated && ! $clubs_cleaned ) {
	$meta_keys    = AnWPFL_Upgrade::get_club_postmeta_keys();
	$placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$club_meta_count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.post_type = 'anwp_club'
		AND pm.meta_key IN ($placeholders)",
		...$meta_keys
	) );
}

// --- Standing postmeta ---
$standings_migrated    = (bool) get_option( 'anwpfl_standings_migrated' );
$standings_cleaned     = (bool) get_option( 'anwpfl_standings_postmeta_cleaned' );
$standing_meta_count   = 0;

if ( $standings_migrated && ! $standings_cleaned ) {
	$st_meta_keys    = AnWPFL_Upgrade::get_standing_postmeta_keys();
	$st_placeholders = implode( ',', array_fill( 0, count( $st_meta_keys ), '%s' ) );

	// Static keys + dynamic matchweek cache keys (_anwpfl_table_main_*).
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$standing_meta_count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.post_type = 'anwp_standing'
		AND ( pm.meta_key IN ($st_placeholders) OR pm.meta_key LIKE %s )",
		...array_merge( $st_meta_keys, [ '_anwpfl_table_main_%' ] )
	) );
}

// --- Competition postmeta ---
$competitions_migrated = (bool) get_option( 'anwpfl_competitions_migrated' );
$competitions_cleaned  = (bool) get_option( 'anwpfl_competitions_postmeta_cleaned' );
$competition_meta_count = 0;

if ( $competitions_migrated && ! $competitions_cleaned ) {
	$comp_meta_keys    = AnWPFL_Upgrade::get_competition_postmeta_keys();
	$comp_placeholders = implode( ',', array_fill( 0, count( $comp_meta_keys ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$competition_meta_count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.post_type = 'anwp_competition'
		AND pm.meta_key IN ($comp_placeholders)",
		...$comp_meta_keys
	) );
}

// --- Squad postmeta ---
$squad_migrated    = (bool) get_option( 'anwpfl_squad_migrated' );
$squad_cleaned     = (bool) get_option( 'anwpfl_squad_postmeta_cleaned' );
$squad_meta_count  = 0;

if ( $squad_migrated && ! $squad_cleaned ) {
	$sq_meta_keys    = AnWPFL_Upgrade::get_squad_postmeta_keys();
	$sq_placeholders = implode( ',', array_fill( 0, count( $sq_meta_keys ), '%s' ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$squad_meta_count = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
		FROM {$wpdb->postmeta} pm
		INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
		WHERE p.post_type = 'anwp_club'
		AND pm.meta_key IN ($sq_placeholders)",
		...$sq_meta_keys
	) );
}
?>
<style>
[popover].fl-confirm-popover {
	border: 1px solid #c3c4c7;
	border-radius: 4px;
	padding: 16px 20px;
	max-width: 340px;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	margin: 0;
	inset: unset;
}

[popover].fl-confirm-popover::backdrop {
	background: rgba(0, 0, 0, 0.15);
}

[popover].fl-confirm-popover p {
	margin: 0 0 14px;
	font-size: 13px;
	line-height: 1.5;
}

[popover].fl-confirm-popover .fl-confirm-actions {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
}
</style>

<div class="mb-2 pb-1">
	<h1 class="anwp-font-normal mb-0">Cleanup</h1>
</div>
<div class="mb-3 d-flex align-items-center">
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox' ) ); ?>">Database Updater</a>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&tab=toolkit' ) ); ?>">Toolkit</a>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<span class="text-muted">Cleanup</span>
	<small class="text-muted mx-2 d-inline-block">|</small>
	<a class="text-decoration-none" href="<?php echo esc_url( self_admin_url( 'admin.php?page=anwpfl-toolbox&tab=cache' ) ); ?>">Caching</a>
</div>

<hr class="mb-3">

<h3 class="mt-3 mb-2">Club Postmeta</h3>
<p class="anwp-text-sm anwp-text-gray-700 mt-0">
	Remove old club postmeta that has been migrated to the <code>anwpfl_clubs</code> custom table.
	Squad-related data is handled in the separate Squad Postmeta section below.
</p>

<?php if ( ! $clubs_migrated ) : ?>
	<div class="notice notice-info inline">
		<p>Club data has not been migrated yet. Run the migration first via Database Updater tab.</p>
	</div>
<?php elseif ( $clubs_cleaned ) : ?>
	<div class="notice notice-success inline">
		<p>Old club postmeta has been removed. Nothing to clean up.</p>
	</div>
<?php elseif ( 0 === $club_meta_count ) : ?>
	<div class="notice notice-success inline">
		<p>No club postmeta found. Nothing to clean up.</p>
	</div>
<?php else : ?>
	<table id="anwpfl-club-cleanup-table" class="wp-list-table widefat striped table-view-list w-auto mb-3">
		<tr>
			<td class="px-3 anwp-font-semibold">Postmeta rows to remove</td>
			<td class="px-2 anwp-text-right" id="anwpfl-club-cleanup-meta-count"><?php echo esc_html( number_format( $club_meta_count ) ); ?></td>
		</tr>
		<tr>
			<td class="px-3 anwp-font-semibold">Meta keys</td>
			<td class="px-2 anwp-text-right"><?php echo esc_html( count( AnWPFL_Upgrade::get_club_postmeta_keys() ) ); ?></td>
		</tr>
	</table>

	<div id="anwpfl-club-cleanup-progress" class="mb-3" style="display: none;">
		<div class="anwp-bg-gray-200" style="height: 8px; border-radius: 4px; max-width: 400px;">
			<div id="anwpfl-club-cleanup-bar" class="anwp-bg-blue-500" style="height: 8px; border-radius: 4px; width: 0; transition: width 0.3s ease;"></div>
		</div>
		<p class="mt-1 mb-0 anwp-text-xs anwp-text-gray-600">
			<span id="anwpfl-club-cleanup-status">0%</span> -
			<span id="anwpfl-club-cleanup-deleted">0</span> /
			<span id="anwpfl-club-cleanup-total"><?php echo esc_html( number_format( $club_meta_count ) ); ?></span> rows removed
		</p>
	</div>

	<div id="anwpfl-club-cleanup-done" class="notice notice-success inline mb-3" style="display: none;">
		<p>Club postmeta cleanup complete.</p>
	</div>

	<button
		type="button"
		id="anwpfl-club-cleanup-btn"
		class="button button-primary"
		popovertarget="anwpfl-club-confirm"
	>Remove old postmeta</button>

	<div id="anwpfl-club-confirm" popover class="fl-confirm-popover" data-fl-anchor="anwpfl-club-cleanup-btn">
		<p>Permanently delete <strong><?php echo esc_html( number_format( $club_meta_count ) ); ?></strong> club postmeta rows? The data is already in the custom table. This can't be undone.</p>
		<div class="fl-confirm-actions">
			<button type="button" class="button" popovertarget="anwpfl-club-confirm" popovertargetaction="hide">Cancel</button>
			<button type="button" class="button button-primary button-link-delete" id="anwpfl-club-cleanup-confirm">Delete</button>
		</div>
	</div>

	<script>
	(function() {
		var btn      = document.getElementById('anwpfl-club-cleanup-btn');
		var confirm  = document.getElementById('anwpfl-club-cleanup-confirm');
		var popover  = document.getElementById('anwpfl-club-confirm');
		var tbl      = document.getElementById('anwpfl-club-cleanup-table');
		var progress = document.getElementById('anwpfl-club-cleanup-progress');
		var bar      = document.getElementById('anwpfl-club-cleanup-bar');
		var status   = document.getElementById('anwpfl-club-cleanup-status');
		var deleted  = document.getElementById('anwpfl-club-cleanup-deleted');
		var done     = document.getElementById('anwpfl-club-cleanup-done');
		var total    = <?php echo (int) $club_meta_count; ?>;
		var removed  = 0;
		var config   = window._anwpToolbox || {};

		confirm.addEventListener('click', function() {
			popover.hidePopover();
			btn.disabled = true;
			btn.removeAttribute('popovertarget');
			btn.textContent = 'Removing...';
			progress.style.display = '';

			runBatch();
		});

		function runBatch() {
			fetch(config.rest_root + 'anwpfl/api-toolbox-updater/cleanup_club_postmeta/', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': config.rest_nonce,
					'Content-Type': 'application/json',
				},
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				removed = total - data.remaining;
				var pct = total ? Math.round((removed / total) * 100) : 100;

				bar.style.width = pct + '%';
				status.textContent = pct + '%';
				deleted.textContent = removed.toLocaleString();

				if (data.completed) {
					tbl.style.display = 'none';
					btn.style.display = 'none';
					progress.style.display = 'none';
					done.style.display = '';
				} else {
					runBatch();
				}
			})
			.catch(function(err) {
				btn.disabled = false;
				btn.textContent = 'Retry';
				alert('Error during cleanup: ' + err.message);
			});
		}
	})();
	</script>
<?php endif; ?>

<hr class="my-3">

<h3 class="mt-3 mb-2">Standing Postmeta</h3>
<p class="anwp-text-sm anwp-text-gray-700 mt-0">
	Remove old standing postmeta that has been migrated to the <code>anwpfl_standings</code> custom table.
	Includes dynamic matchweek cache entries (<code>_anwpfl_table_main_*</code>).
</p>

<?php if ( ! $standings_migrated ) : ?>
	<div class="notice notice-info inline">
		<p>Standing data has not been migrated yet. Run the migration first via Database Updater tab.</p>
	</div>
<?php elseif ( $standings_cleaned ) : ?>
	<div class="notice notice-success inline">
		<p>Old standing postmeta has been removed. Nothing to clean up.</p>
	</div>
<?php elseif ( 0 === $standing_meta_count ) : ?>
	<div class="notice notice-success inline">
		<p>No standing postmeta found. Nothing to clean up.</p>
	</div>
<?php else : ?>
	<table id="anwpfl-standing-cleanup-table" class="wp-list-table widefat striped table-view-list w-auto mb-3">
		<tr>
			<td class="px-3 anwp-font-semibold">Postmeta rows to remove</td>
			<td class="px-2 anwp-text-right" id="anwpfl-standing-cleanup-meta-count"><?php echo esc_html( number_format( $standing_meta_count ) ); ?></td>
		</tr>
		<tr>
			<td class="px-3 anwp-font-semibold">Static meta keys</td>
			<td class="px-2 anwp-text-right"><?php echo esc_html( count( AnWPFL_Upgrade::get_standing_postmeta_keys() ) ); ?></td>
		</tr>
	</table>

	<div id="anwpfl-standing-cleanup-progress" class="mb-3" style="display: none;">
		<div class="anwp-bg-gray-200" style="height: 8px; border-radius: 4px; max-width: 400px;">
			<div id="anwpfl-standing-cleanup-bar" class="anwp-bg-blue-500" style="height: 8px; border-radius: 4px; width: 0; transition: width 0.3s ease;"></div>
		</div>
		<p class="mt-1 mb-0 anwp-text-xs anwp-text-gray-600">
			<span id="anwpfl-standing-cleanup-status">0%</span> -
			<span id="anwpfl-standing-cleanup-deleted">0</span> /
			<span id="anwpfl-standing-cleanup-total"><?php echo esc_html( number_format( $standing_meta_count ) ); ?></span> rows removed
		</p>
	</div>

	<div id="anwpfl-standing-cleanup-done" class="notice notice-success inline mb-3" style="display: none;">
		<p>Standing postmeta cleanup complete.</p>
	</div>

	<button
		type="button"
		id="anwpfl-standing-cleanup-btn"
		class="button button-primary"
		popovertarget="anwpfl-standing-confirm"
	>Remove old postmeta</button>

	<div id="anwpfl-standing-confirm" popover class="fl-confirm-popover" data-fl-anchor="anwpfl-standing-cleanup-btn">
		<p>Permanently delete <strong><?php echo esc_html( number_format( $standing_meta_count ) ); ?></strong> standing postmeta rows? The data is already in the custom table. This can't be undone.</p>
		<div class="fl-confirm-actions">
			<button type="button" class="button" popovertarget="anwpfl-standing-confirm" popovertargetaction="hide">Cancel</button>
			<button type="button" class="button button-primary button-link-delete" id="anwpfl-standing-cleanup-confirm">Delete</button>
		</div>
	</div>

	<script>
	(function() {
		var btn      = document.getElementById('anwpfl-standing-cleanup-btn');
		var confirm  = document.getElementById('anwpfl-standing-cleanup-confirm');
		var popover  = document.getElementById('anwpfl-standing-confirm');
		var tbl      = document.getElementById('anwpfl-standing-cleanup-table');
		var progress = document.getElementById('anwpfl-standing-cleanup-progress');
		var bar      = document.getElementById('anwpfl-standing-cleanup-bar');
		var status   = document.getElementById('anwpfl-standing-cleanup-status');
		var deleted  = document.getElementById('anwpfl-standing-cleanup-deleted');
		var done     = document.getElementById('anwpfl-standing-cleanup-done');
		var total    = <?php echo (int) $standing_meta_count; ?>;
		var removed  = 0;
		var config   = window._anwpToolbox || {};

		confirm.addEventListener('click', function() {
			popover.hidePopover();
			btn.disabled = true;
			btn.removeAttribute('popovertarget');
			btn.textContent = 'Removing...';
			progress.style.display = '';

			runBatch();
		});

		function runBatch() {
			fetch(config.rest_root + 'anwpfl/api-toolbox-updater/cleanup_standing_postmeta/', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': config.rest_nonce,
					'Content-Type': 'application/json',
				},
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				removed = total - data.remaining;
				var pct = total ? Math.round((removed / total) * 100) : 100;

				bar.style.width = pct + '%';
				status.textContent = pct + '%';
				deleted.textContent = removed.toLocaleString();

				if (data.completed) {
					tbl.style.display = 'none';
					btn.style.display = 'none';
					progress.style.display = 'none';
					done.style.display = '';
				} else {
					runBatch();
				}
			})
			.catch(function(err) {
				btn.disabled = false;
				btn.textContent = 'Retry';
				alert('Error during cleanup: ' + err.message);
			});
		}
	})();
	</script>
<?php endif; ?>

<hr class="my-3">

<h3 class="mt-3 mb-2">Competition Postmeta</h3>
<p class="anwp-text-sm anwp-text-gray-700 mt-0">
	Remove old competition postmeta that has been migrated to the <code>anwpfl_competitions</code> custom table.
	CMB2-managed fields (layout, logos, custom content, order) and API wizard keys are not affected.
</p>

<?php if ( ! $competitions_migrated ) : ?>
	<div class="notice notice-info inline">
		<p>Competition data has not been migrated yet. Run the migration first via Database Updater tab.</p>
	</div>
<?php elseif ( $competitions_cleaned ) : ?>
	<div class="notice notice-success inline">
		<p>Old competition postmeta has been removed. Nothing to clean up.</p>
	</div>
<?php elseif ( 0 === $competition_meta_count ) : ?>
	<div class="notice notice-success inline">
		<p>No competition postmeta found. Nothing to clean up.</p>
	</div>
<?php else : ?>
	<table id="anwpfl-competition-cleanup-table" class="wp-list-table widefat striped table-view-list w-auto mb-3">
		<tr>
			<td class="px-3 anwp-font-semibold">Postmeta rows to remove</td>
			<td class="px-2 anwp-text-right" id="anwpfl-competition-cleanup-meta-count"><?php echo esc_html( number_format( $competition_meta_count ) ); ?></td>
		</tr>
		<tr>
			<td class="px-3 anwp-font-semibold">Meta keys</td>
			<td class="px-2 anwp-text-right"><?php echo esc_html( count( AnWPFL_Upgrade::get_competition_postmeta_keys() ) ); ?></td>
		</tr>
	</table>

	<div id="anwpfl-competition-cleanup-progress" class="mb-3" style="display: none;">
		<div class="anwp-bg-gray-200" style="height: 8px; border-radius: 4px; max-width: 400px;">
			<div id="anwpfl-competition-cleanup-bar" class="anwp-bg-blue-500" style="height: 8px; border-radius: 4px; width: 0; transition: width 0.3s ease;"></div>
		</div>
		<p class="mt-1 mb-0 anwp-text-xs anwp-text-gray-600">
			<span id="anwpfl-competition-cleanup-status">0%</span> -
			<span id="anwpfl-competition-cleanup-deleted">0</span> /
			<span id="anwpfl-competition-cleanup-total"><?php echo esc_html( number_format( $competition_meta_count ) ); ?></span> rows removed
		</p>
	</div>

	<div id="anwpfl-competition-cleanup-done" class="notice notice-success inline mb-3" style="display: none;">
		<p>Competition postmeta cleanup complete.</p>
	</div>

	<button
		type="button"
		id="anwpfl-competition-cleanup-btn"
		class="button button-primary"
		popovertarget="anwpfl-competition-confirm"
	>Remove old postmeta</button>

	<div id="anwpfl-competition-confirm" popover class="fl-confirm-popover" data-fl-anchor="anwpfl-competition-cleanup-btn">
		<p>Permanently delete <strong><?php echo esc_html( number_format( $competition_meta_count ) ); ?></strong> competition postmeta rows? The data is already in the custom table. This can't be undone.</p>
		<div class="fl-confirm-actions">
			<button type="button" class="button" popovertarget="anwpfl-competition-confirm" popovertargetaction="hide">Cancel</button>
			<button type="button" class="button button-primary button-link-delete" id="anwpfl-competition-cleanup-confirm">Delete</button>
		</div>
	</div>

	<script>
	(function() {
		var btn      = document.getElementById('anwpfl-competition-cleanup-btn');
		var confirm  = document.getElementById('anwpfl-competition-cleanup-confirm');
		var popover  = document.getElementById('anwpfl-competition-confirm');
		var tbl      = document.getElementById('anwpfl-competition-cleanup-table');
		var progress = document.getElementById('anwpfl-competition-cleanup-progress');
		var bar      = document.getElementById('anwpfl-competition-cleanup-bar');
		var status   = document.getElementById('anwpfl-competition-cleanup-status');
		var deleted  = document.getElementById('anwpfl-competition-cleanup-deleted');
		var done     = document.getElementById('anwpfl-competition-cleanup-done');
		var total    = <?php echo (int) $competition_meta_count; ?>;
		var removed  = 0;
		var config   = window._anwpToolbox || {};

		confirm.addEventListener('click', function() {
			popover.hidePopover();
			btn.disabled = true;
			btn.removeAttribute('popovertarget');
			btn.textContent = 'Removing...';
			progress.style.display = '';

			runBatch();
		});

		function runBatch() {
			fetch(config.rest_root + 'anwpfl/api-toolbox-updater/cleanup_competition_postmeta/', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': config.rest_nonce,
					'Content-Type': 'application/json',
				},
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				removed = total - data.remaining;
				var pct = total ? Math.round((removed / total) * 100) : 100;

				bar.style.width = pct + '%';
				status.textContent = pct + '%';
				deleted.textContent = removed.toLocaleString();

				if (data.completed) {
					tbl.style.display = 'none';
					btn.style.display = 'none';
					progress.style.display = 'none';
					done.style.display = '';
				} else {
					runBatch();
				}
			})
			.catch(function(err) {
				btn.disabled = false;
				btn.textContent = 'Retry';
				alert('Error during cleanup: ' + err.message);
			});
		}
	})();
	</script>
<?php endif; ?>

<hr class="my-3">

<h3 class="mt-3 mb-2">Squad Postmeta</h3>
<p class="anwp-text-sm anwp-text-gray-700 mt-0">
	Remove old squad postmeta (<code>_anwpfl_squad</code>, <code>_anwpfl_staff</code>, <code>_anwpfl_squad_display</code>) that has been migrated to the <code>anwpfl_clubs</code> custom table (<code>squad</code>, <code>squad_staff</code>, <code>squad_seasons</code>, <code>squad_staff_seasons</code>, <code>squad_group</code> columns).
</p>

<?php if ( ! $squad_migrated ) : ?>
	<div class="notice notice-info inline">
		<p>Squad data has not been migrated yet. Run the migration first via Database Updater tab.</p>
	</div>
<?php elseif ( $squad_cleaned ) : ?>
	<div class="notice notice-success inline">
		<p>Old squad postmeta has been removed. Nothing to clean up.</p>
	</div>
<?php elseif ( 0 === $squad_meta_count ) : ?>
	<div class="notice notice-success inline">
		<p>No squad postmeta found. Nothing to clean up.</p>
	</div>
<?php else : ?>
	<table id="anwpfl-squad-cleanup-table" class="wp-list-table widefat striped table-view-list w-auto mb-3">
		<tr>
			<td class="px-3 anwp-font-semibold">Postmeta rows to remove</td>
			<td class="px-2 anwp-text-right" id="anwpfl-squad-cleanup-meta-count"><?php echo esc_html( number_format( $squad_meta_count ) ); ?></td>
		</tr>
		<tr>
			<td class="px-3 anwp-font-semibold">Meta keys</td>
			<td class="px-2 anwp-text-right"><?php echo esc_html( count( AnWPFL_Upgrade::get_squad_postmeta_keys() ) ); ?></td>
		</tr>
	</table>

	<div id="anwpfl-squad-cleanup-progress" class="mb-3" style="display: none;">
		<div class="anwp-bg-gray-200" style="height: 8px; border-radius: 4px; max-width: 400px;">
			<div id="anwpfl-squad-cleanup-bar" class="anwp-bg-blue-500" style="height: 8px; border-radius: 4px; width: 0; transition: width 0.3s ease;"></div>
		</div>
		<p class="mt-1 mb-0 anwp-text-xs anwp-text-gray-600">
			<span id="anwpfl-squad-cleanup-status">0%</span> -
			<span id="anwpfl-squad-cleanup-deleted">0</span> /
			<span id="anwpfl-squad-cleanup-total"><?php echo esc_html( number_format( $squad_meta_count ) ); ?></span> rows removed
		</p>
	</div>

	<div id="anwpfl-squad-cleanup-done" class="notice notice-success inline mb-3" style="display: none;">
		<p>Squad postmeta cleanup complete.</p>
	</div>

	<button
		type="button"
		id="anwpfl-squad-cleanup-btn"
		class="button button-primary"
		popovertarget="anwpfl-squad-confirm"
	>Remove old postmeta</button>

	<div id="anwpfl-squad-confirm" popover class="fl-confirm-popover" data-fl-anchor="anwpfl-squad-cleanup-btn">
		<p>Permanently delete <strong><?php echo esc_html( number_format( $squad_meta_count ) ); ?></strong> squad postmeta rows? The data is already in the custom table. This can't be undone.</p>
		<div class="fl-confirm-actions">
			<button type="button" class="button" popovertarget="anwpfl-squad-confirm" popovertargetaction="hide">Cancel</button>
			<button type="button" class="button button-primary button-link-delete" id="anwpfl-squad-cleanup-confirm">Delete</button>
		</div>
	</div>

	<script>
	(function() {
		var btn      = document.getElementById('anwpfl-squad-cleanup-btn');
		var confirm  = document.getElementById('anwpfl-squad-cleanup-confirm');
		var popover  = document.getElementById('anwpfl-squad-confirm');
		var tbl      = document.getElementById('anwpfl-squad-cleanup-table');
		var progress = document.getElementById('anwpfl-squad-cleanup-progress');
		var bar      = document.getElementById('anwpfl-squad-cleanup-bar');
		var status   = document.getElementById('anwpfl-squad-cleanup-status');
		var deleted  = document.getElementById('anwpfl-squad-cleanup-deleted');
		var done     = document.getElementById('anwpfl-squad-cleanup-done');
		var total    = <?php echo (int) $squad_meta_count; ?>;
		var removed  = 0;
		var config   = window._anwpToolbox || {};

		confirm.addEventListener('click', function() {
			popover.hidePopover();
			btn.disabled = true;
			btn.removeAttribute('popovertarget');
			btn.textContent = 'Removing...';
			progress.style.display = '';

			runBatch();
		});

		function runBatch() {
			fetch(config.rest_root + 'anwpfl/api-toolbox-updater/cleanup_squad_postmeta/', {
				method: 'POST',
				headers: {
					'X-WP-Nonce': config.rest_nonce,
					'Content-Type': 'application/json',
				},
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				removed = total - data.remaining;
				var pct = total ? Math.round((removed / total) * 100) : 100;

				bar.style.width = pct + '%';
				status.textContent = pct + '%';
				deleted.textContent = removed.toLocaleString();

				if (data.completed) {
					tbl.style.display = 'none';
					btn.style.display = 'none';
					progress.style.display = 'none';
					done.style.display = '';
				} else {
					runBatch();
				}
			})
			.catch(function(err) {
				btn.disabled = false;
				btn.textContent = 'Retry';
				alert('Error during cleanup: ' + err.message);
			});
		}
	})();
	</script>
<?php endif; ?>

<script>
document.querySelectorAll('.fl-confirm-popover').forEach(function(pop) {
	pop.addEventListener('toggle', function(e) {
		if (e.newState !== 'open') return;
		var anchor = document.getElementById(pop.dataset.flAnchor);
		if (!anchor) return;
		var rect = anchor.getBoundingClientRect();
		pop.style.position = 'fixed';
		pop.style.top = (rect.bottom + 8) + 'px';
		pop.style.left = rect.left + 'px';
	});
});
</script>
