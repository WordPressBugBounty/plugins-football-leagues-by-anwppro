<?php
/**
 * Dashboard page for AnWP Football Leagues
 *
 * @link       https://anwp.pro
 * @since      0.1.0
 * @since      0.17.0 Redesigned as Dashboard with stats and changelog.
 *
 * @package    AnWP_Football_Leagues
 * @subpackage AnWP_Football_Leagues/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Debug Mode - Show Banners for Testing
|--------------------------------------------------------------------------
|
| Uncomment ONE of the lines below to force-show banners for visual testing.
|
| Options:
|   true           - Show ALL banners at once
|   'welcome'      - Welcome Banner (normally: new users with 0 matches)
|   'api_import'   - API Import Banner (normally: premium + no API key)
|   'premium_promo'- Premium Promotion Banner (normally: free users only)
|   'support'      - Premium Support Ticket button (normally: premium only)
|
| Examples:
|   define( 'ANWPFL_DEBUG_BANNERS', true );           // All banners
|   define( 'ANWPFL_DEBUG_BANNERS', 'welcome' );      // Only welcome
|   define( 'ANWPFL_DEBUG_BANNERS', 'premium_promo' );// Only premium promo
|
| IMPORTANT: Re-comment after testing!
|
*/
// define( 'ANWPFL_DEBUG_BANNERS', true );

$debug_banners = defined( 'ANWPFL_DEBUG_BANNERS' ) ? ANWPFL_DEBUG_BANNERS : false;
$debug_all     = true === $debug_banners;
$debug_banner  = function( $name ) use ( $debug_banners, $debug_all ) {
	return $debug_all || $debug_banners === $name;
};

$data    = anwp_fl()->get_dashboard_data();
$counts  = $data['counts'];
$can_add = $data['can_add'];

// Entity definitions for Stats Grid
$entities = [
	'leagues'      => [
		'label' => __( 'Leagues', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-networking',
		'url'   => 'edit-tags.php?taxonomy=anwp_league&post_type=anwp_competition',
	],
	'seasons'      => [
		'label' => __( 'Seasons', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-calendar-alt',
		'url'   => 'edit-tags.php?taxonomy=anwp_season&post_type=anwp_competition',
	],
	'clubs'        => [
		'label' => __( 'Clubs', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-shield',
		'url'   => 'post-new.php?post_type=anwp_club',
	],
	'players'      => [
		'label' => __( 'Players', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-groups',
		'url'   => 'post-new.php?post_type=anwp_player',
	],
	'competitions' => [
		'label'   => __( 'Competitions', 'anwp-football-leagues' ),
		'icon'    => 'dashicons-awards',
		'url'     => 'post-new.php?post_type=anwp_competition',
		'tooltip' => __( 'Create League and Season first', 'anwp-football-leagues' ),
	],
	'matches'      => [
		'label'   => __( 'Matches', 'anwp-football-leagues' ),
		'icon'    => 'dashicons-tickets',
		'url'     => 'post-new.php?post_type=anwp_match',
		'tooltip' => __( 'Create Competition and at least 2 Clubs first', 'anwp-football-leagues' ),
	],
	'stadiums'     => [
		'label' => __( 'Stadiums', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-location',
		'url'   => 'post-new.php?post_type=anwp_stadium',
	],
	'staff'        => [
		'label' => __( 'Staff', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-businessman',
		'url'   => 'post-new.php?post_type=anwp_staff',
	],
	'referees'     => [
		'label' => __( 'Referees', 'anwp-football-leagues' ),
		'icon'  => 'dashicons-id',
		'url'   => 'post-new.php?post_type=anwp_referee',
	],
];

// Parse changelogs
$core_changelog = anwp_fl()->parse_changelog( anwp_fl()->path . 'changelog.txt', 3 );

$premium_changelog = [];
if ( function_exists( 'anwp_fl_pro' ) ) {
	$premium_changelog = anwp_fl()->parse_changelog( anwp_fl_pro()->path . 'changelog.txt', 3 );
}

// Tutorials & Articles with badges: 'pro', 'core', 'new', 'updated'
$tutorials = [
	[
		'title'  => __( 'User Timezone', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/pro-features/user-timezone/',
		'badges' => [ 'pro', 'new' ],
	],
	[
		'title'  => __( 'Club History (Historical Logos & Names)', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/pro-features/club-history-historical-logos-names/',
		'badges' => [ 'pro', 'new' ],
	],
	[
		'title'  => __( 'Entity Links', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/pro-features/entity-links/',
		'badges' => [ 'pro', 'new' ],
	],
	[
		'title'  => __( 'AI Writer', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/pro-features/ai-writer/',
		'badges' => [ 'pro', 'updated' ],
	],
	[
		'title'  => __( 'Tag Posts Shortcode', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/pro-features/tag-posts-shortcode/',
		'badges' => [ 'pro', 'updated' ],
	],
	[
		'title'  => __( 'Shortcode Builder', 'anwp-football-leagues' ),
		'url'    => 'https://anwp.pro/docs/football-leagues/display/shortcode-builder/',
		'badges' => [ 'core', 'updated' ],
	],
];
?>
<div class="anwp-b-wrap">
	<div class="anwp-p-4 anwp-w-max-1200">

		<!-- Header -->
		<h1 class="anwp-mb-4">
			<?php echo esc_html__( 'Football Leagues', 'anwp-football-leagues' ); ?> :: <?php echo esc_html__( 'Dashboard', 'anwp-football-leagues' ); ?>
			<span class="anwp-text-sm anwp-text-gray-500 anwp-ml-2">v<?php echo esc_html( AnWP_Football_Leagues::VERSION ); ?></span>
		</h1>

		<!-- Resources -->
		<div class="anwp-mb-4 anwp-p-3 anwp-bg-white anwp-border anwp-border-gray-300 anwp-rounded">
			<div class="anwp-d-flex anwp-flex-wrap anwp-gap-2">
				<a href="https://anwp.pro/docs/football-leagues/" class="button" target="_blank">📖 <?php echo esc_html__( 'Documentation', 'anwp-football-leagues' ); ?></a>
				<a href="https://anwppro.userecho.com/communities/1-football-leagues#module_9" class="button" target="_blank">📖 <?php echo esc_html__( 'Documentation (old)', 'anwp-football-leagues' ); ?></a>
				<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/70-start-guide" class="button" target="_blank">🚀 <?php echo esc_html__( 'Start Guide', 'anwp-football-leagues' ); ?></a>
				<a href="https://anwppro.userecho.com/communities/1-football-leagues" class="button" target="_blank">💬 <?php echo esc_html__( 'Community & Support Forum', 'anwp-football-leagues' ); ?></a>
				<?php if ( function_exists( 'anwp_fl_pro' ) || $debug_banner( 'support' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=anwpfl-support' ) ); ?>" class="button button-primary">⭐ <?php echo esc_html__( 'Premium Support Ticket', 'anwp-football-leagues' ); ?></a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Welcome Banner (new users only) -->
		<?php if ( $data['is_new_user'] || $debug_banner( 'welcome' ) ) : ?>
		<div class="anwp-mb-4 anwp-p-4 anwp-bg-blue-50 anwp-border anwp-border-blue-500 anwp-rounded" style="border-left: 4px solid #2271b1;">
			<h3 class="anwp-mt-0 anwp-mb-2 anwp-font-semibold">
				👋 <?php echo esc_html__( 'Welcome to Football Leagues!', 'anwp-football-leagues' ); ?>
			</h3>
			<p class="anwp-mb-2"><?php echo esc_html__( 'Get started by creating your first League, Season, and Competition.', 'anwp-football-leagues' ); ?></p>
			<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/70-start-guide" target="_blank" class="button button-primary">
				<?php echo esc_html__( 'Read the Start Guide', 'anwp-football-leagues' ); ?> →
			</a>
		</div>
		<?php endif; ?>

		<!-- Premium API Import Banner (dismissable) -->
		<?php if ( $data['is_premium_new'] || $debug_banner( 'api_import' ) ) : ?>
		<div id="anwpfl-api-import-banner" class="anwp-mb-4 anwp-p-4 anwp-bg-green-50 anwp-border anwp-border-green-500 anwp-rounded" style="border-left: 4px solid #00a32a; display: none;">
			<div class="anwp-d-flex anwp-justify-between anwp-items-start">
				<div>
					<h3 class="anwp-mt-0 anwp-mb-2 anwp-font-semibold">
						🚀 <?php echo esc_html__( 'Set up automatic data import', 'anwp-football-leagues' ); ?>
					</h3>
					<p class="anwp-mb-2"><?php echo esc_html__( 'Import matches, standings, and player data automatically from API-Football.', 'anwp-football-leagues' ); ?></p>
					<div class="anwp-d-flex anwp-flex-wrap anwp-gap-2">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=anwp-football-leagues-api' ) ); ?>" class="button button-primary">
							<?php echo esc_html__( 'Configure API Import', 'anwp-football-leagues' ); ?> →
						</a>
						<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/1665-import-data-from-api-footballcom" target="_blank" class="button">
							<?php echo esc_html__( 'View Tutorial', 'anwp-football-leagues' ); ?>
						</a>
					</div>
				</div>
				<button type="button" id="anwpfl-api-import-dismiss" class="anwp-bg-transparent anwp-border-0 anwp-cursor-pointer anwp-p-1" title="<?php echo esc_attr__( 'Dismiss', 'anwp-football-leagues' ); ?>">
					<span class="dashicons dashicons-no-alt anwp-dashicons-20 anwp-text-gray-500"></span>
				</button>
			</div>
		</div>
		<script>
		(function() {
			var banner = document.getElementById('anwpfl-api-import-banner');
			var dismissBtn = document.getElementById('anwpfl-api-import-dismiss');
			var storageKey = 'anwpfl_api_import_banner_dismissed';

			if (banner && !localStorage.getItem(storageKey)) {
				banner.style.display = 'block';
			}

			if (dismissBtn) {
				dismissBtn.addEventListener('click', function() {
					localStorage.setItem(storageKey, '1');
					banner.style.display = 'none';
				});
			}
		})();
		</script>
		<?php endif; ?>

		<!-- Premium Promotion Banner (free users only) -->
		<?php if ( ! function_exists( 'anwp_fl_pro' ) || $debug_banner( 'premium_promo' ) ) : ?>
		<div class="anwp-mb-4 anwp-p-4 anwp-bg-orange-50 anwp-border anwp-border-orange-400 anwp-rounded" style="border-left: 4px solid #ea580c;">
			<div class="anwp-d-flex anwp-flex-wrap anwp-items-start anwp-gap-4">
				<div class="anwp-grow" style="min-width: 280px;">
					<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-base anwp-font-semibold anwp-d-flex anwp-items-center">
						<span class="dashicons dashicons-star-filled anwp-dashicons-18 anwp-text-orange-600 anwp-mr-2"></span>
						<?php echo esc_html__( 'Unlock Premium Features', 'anwp-football-leagues' ); ?>
					</h3>
					<div class="anwp-d-grid anwp-grid-auto-fill anwp-grid-min-200 anwp-gap-2 anwp-mb-3">
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Automatic API Import', 'anwp-football-leagues' ); ?> *
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Live Scores & Updates', 'anwp-football-leagues' ); ?>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/ai-writer/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( 'AI Match Reports', 'anwp-football-leagues' ); ?></a>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Layout Builder', 'anwp-football-leagues' ); ?>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Tournament Brackets', 'anwp-football-leagues' ); ?>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Player Transfers', 'anwp-football-leagues' ); ?>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/shortcodes/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( '30+ Premium Shortcodes', 'anwp-football-leagues' ); ?></a>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<?php echo esc_html__( 'Charts & Statistics', 'anwp-football-leagues' ); ?>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/club-history-historical-logos-names/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( 'Club History', 'anwp-football-leagues' ); ?></a>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/entity-links/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( 'Entity Links', 'anwp-football-leagues' ); ?></a>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/user-timezone/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( 'User Timezone', 'anwp-football-leagues' ); ?></a>
						</div>
						<div class="anwp-d-flex anwp-items-center anwp-text-sm">
							<span class="dashicons dashicons-yes anwp-dashicons-16 anwp-text-orange-600 anwp-mr-1"></span>
							<a href="https://anwp.pro/docs/football-leagues/pro-features/manual-odds/" target="_blank" class="anwp-text-inherit anwp-text-decoration-none"><?php echo esc_html__( 'Manual Odds', 'anwp-football-leagues' ); ?></a>
						</div>
					</div>
					<p class="anwp-mt-0 anwp-mb-0 anwp-text-xs anwp-text-gray-600">
						<?php echo esc_html__( '* API Import requires a separate subscription to api-football.com', 'anwp-football-leagues' ); ?>
						<a href="https://anwppro.userecho.com/knowledge-bases/2/articles/1665-import-data-from-api-footballcom" target="_blank" class="anwp-text-gray-700"><?php echo esc_html__( 'Learn more', 'anwp-football-leagues' ); ?> →</a>
					</p>
				</div>
				<div class="anwp-shrink-0">
					<a href="https://anwp.pro/football-leagues-premium/" target="_blank" class="button button-primary anwp-d-flex anwp-items-center anwp-justify-center">
						<span class="dashicons dashicons-cart anwp-dashicons-18"></span>
						<span class="anwp-ml-2"><?php echo esc_html__( 'Get Premium', 'anwp-football-leagues' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Stats Grid -->
		<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-300 anwp-rounded">
			<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-base anwp-font-semibold"><?php echo esc_html__( 'Your Data', 'anwp-football-leagues' ); ?></h2>
			<div class="anwp-d-flex anwp-flex-wrap anwp-gap-3">
				<?php foreach ( $entities as $key => $entity ) :
					$disabled = isset( $can_add[ $key ] ) && ! $can_add[ $key ];
					$count    = $counts[ $key ] ?? 0;
				?>
				<div class="anwp-p-2 anwp-bg-gray-50 anwp-border anwp-border-gray-300 anwp-rounded anwp-text-center" style="min-width: 90px; flex: 1 1 90px; max-width: 110px;">
					<span class="dashicons <?php echo esc_attr( $entity['icon'] ); ?> anwp-text-gray-500" style="font-size: 20px; width: 20px; height: 20px;"></span>
					<div class="anwp-text-base anwp-font-bold anwp-text-gray-900"><?php echo esc_html( $count ); ?></div>
					<div class="anwp-text-xs anwp-text-gray-600 anwp-mb-1"><?php echo esc_html( $entity['label'] ); ?></div>
					<?php if ( $disabled ) : ?>
						<span class="button button-small disabled" style="pointer-events: none; opacity: 0.6;" title="<?php echo esc_attr( $entity['tooltip'] ?? '' ); ?>">+ <?php echo esc_html__( 'Add', 'anwp-football-leagues' ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( admin_url( $entity['url'] ) ); ?>" class="button button-small">+ <?php echo esc_html__( 'Add', 'anwp-football-leagues' ); ?></a>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Changelog Section -->
		<div class="anwp-mb-4 anwp-p-4 anwp-bg-white anwp-border anwp-border-gray-300 anwp-rounded">
			<h2 class="anwp-mt-0 anwp-mb-3 anwp-text-base anwp-font-semibold"><?php echo esc_html__( "What's New", 'anwp-football-leagues' ); ?></h2>
			<div class="anwp-d-grid anwp-grid-auto-fill anwp-grid-min-350 anwp-gap-4">

				<!-- Core Changelog -->
				<div>
					<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-sm anwp-font-semibold anwp-text-gray-700"><?php echo esc_html__( 'Core Plugin', 'anwp-football-leagues' ); ?></h3>
					<?php foreach ( $core_changelog as $version ) : ?>
						<div class="anwp-mb-3">
							<strong class="anwp-text-sm"><?php echo esc_html( $version['version'] ); ?></strong>
							<span class="anwp-text-gray-500 anwp-text-xs"><?php echo esc_html( $version['date'] ); ?></span>
							<ul class="anwp-mt-1 anwp-mb-0 anwp-pl-4" style="list-style: none;">
								<?php foreach ( $version['changes'] as $change ) : ?>
									<li class="anwp-text-xs anwp-mb-1"><?php echo esc_html( $change ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
					<a href="https://support.anwp.pro/knowledge-bases/11-fl-changelog/categories/28-basic-version/articles" target="_blank" class="anwp-text-sm"><?php echo esc_html__( 'Full Changelog', 'anwp-football-leagues' ); ?> →</a>
				</div>

				<!-- Premium Changelog -->
				<div>
					<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-sm anwp-font-semibold anwp-text-gray-700 anwp-d-flex anwp-items-center">
						<?php echo esc_html__( 'Premium', 'anwp-football-leagues' ); ?>
						<?php if ( ! function_exists( 'anwp_fl_pro' ) ) : ?>
							<span class="anwp-ml-2 anwp-px-1 anwp-py-0 anwp-bg-orange-600 anwp-text-white anwp-text-xxs anwp-rounded anwp-font-semibold">PRO</span>
						<?php endif; ?>
					</h3>
					<?php if ( ! empty( $premium_changelog ) ) : ?>
						<?php foreach ( $premium_changelog as $version ) : ?>
							<div class="anwp-mb-3">
								<strong class="anwp-text-sm"><?php echo esc_html( $version['version'] ); ?></strong>
								<span class="anwp-text-gray-500 anwp-text-xs"><?php echo esc_html( $version['date'] ); ?></span>
								<ul class="anwp-mt-1 anwp-mb-0 anwp-pl-4" style="list-style: none;">
									<?php foreach ( $version['changes'] as $change ) : ?>
										<li class="anwp-text-xs anwp-mb-1"><?php echo esc_html( $change ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endforeach; ?>
						<a href="https://support.anwp.pro/knowledge-bases/11-fl-changelog/categories/29-premium-addon/articles" target="_blank" class="anwp-text-sm"><?php echo esc_html__( 'Full Changelog', 'anwp-football-leagues' ); ?> →</a>
					<?php else : ?>
						<p class="anwp-mt-0 anwp-mb-3 anwp-text-sm anwp-text-gray-600">
							<?php echo esc_html__( 'Discover new features and improvements in the Premium version.', 'anwp-football-leagues' ); ?>
						</p>
						<a href="https://support.anwp.pro/knowledge-bases/11-fl-changelog/categories/29-premium-addon/articles" target="_blank" class="anwp-text-sm"><?php echo esc_html__( 'View Premium Changelog', 'anwp-football-leagues' ); ?> →</a>
					<?php endif; ?>
				</div>

				<!-- Tutorials & Articles -->
				<div>
					<h3 class="anwp-mt-0 anwp-mb-2 anwp-text-sm anwp-font-semibold anwp-text-gray-700"><?php echo esc_html__( 'Latest Tutorials', 'anwp-football-leagues' ); ?></h3>
					<ul class="anwp-mb-0 anwp-pl-0" style="list-style: none;">
						<?php foreach ( $tutorials as $tutorial ) : ?>
							<li class="anwp-mb-2 anwp-d-flex anwp-items-center anwp-flex-wrap anwp-gap-1">
								<a href="<?php echo esc_url( $tutorial['url'] ); ?>" target="_blank" class="anwp-text-sm">
									<?php echo esc_html( $tutorial['title'] ); ?>
								</a>
								<?php foreach ( $tutorial['badges'] as $badge ) : ?>
									<?php if ( 'pro' === $badge ) : ?>
										<span class="anwp-px-1 anwp-py-0 anwp-bg-orange-600 anwp-text-white anwp-text-xxs anwp-rounded anwp-font-semibold">PRO</span>
									<?php elseif ( 'new' === $badge ) : ?>
										<span class="anwp-px-1 anwp-py-0 anwp-bg-green-600 anwp-text-white anwp-text-xxs anwp-rounded anwp-font-semibold">NEW</span>
									<?php elseif ( 'updated' === $badge ) : ?>
										<span class="anwp-px-1 anwp-py-0 anwp-bg-blue-600 anwp-text-white anwp-text-xxs anwp-rounded anwp-font-semibold">UPD</span>
									<?php endif; ?>
								<?php endforeach; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<a href="https://anwp.pro/docs/football-leagues/" target="_blank" class="anwp-text-sm anwp-mt-2 anwp-d-inline-block"><?php echo esc_html__( 'All Documentation', 'anwp-football-leagues' ); ?> →</a>
				</div>

			</div>
		</div>

	</div>
</div>
