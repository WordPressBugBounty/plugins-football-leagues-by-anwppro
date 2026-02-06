<?php
/**
 * The Template for displaying flag.
 *
 * This template can be overridden by copying it to yourtheme/anwp-football-leagues/general/flag.php.
 *
 * @var object $data - Object with widget data.
 *
 * @author        Andrei Strekozov <anwp.pro>
 * @package       AnWP-Football-Leagues/Templates
 * @since         0.14.14
 *
 * @version       0.16.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$data = wp_parse_args(
	$data,
	[
		'country_code'  => '',
		'wrapper_class' => '',
		'size'          => 16,
		'width'         => 0,
	]
);

if ( empty( $data['country_code'] ) ) {
	return;
}

/*
|--------------------------------------------------------------------
| Load Legacy flags
|--------------------------------------------------------------------
*/
if ( 'legacy' === anwp_football_leagues()->customizer->get_value( 'general', 'flags' ) ) {
	anwp_football_leagues()->load_partial( $data, 'general/flag--legacy' );

	return;
}

static $flags_url = null;

if ( null === $flags_url ) {
	$flags_url = apply_filters( 'anwpfl/media/modify_url_svg_sprite', AnWP_Football_Leagues::url( 'public/img/flags-v3.svg' ) );
}

/*
|--------------------------------------------------------------------
| Size
|--------------------------------------------------------------------
*/
if ( absint( $data['width'] ) > 10 ) {
	$flag_size = absint( $data['width'] );
} else {
	$flag_size = 32 === absint( $data['size'] ) ? 25 : 18;
}

/*
|--------------------------------------------------------------------
| Parsed country code
|--------------------------------------------------------------------
*/
$code_parsed = mb_strtolower( str_replace( '_', '-', $data['country_code'] ) );

if ( in_array( $data['country_code'], [ '__Africa', '__Asia', '__NC_America', '__Oceania', '__South_America' ], true ) ) {
	$code_parsed = '--world';
} elseif ( ! in_array( $code_parsed, anwp_football_leagues()->data->get_available_circle_flags(), true ) ) {
	$code_parsed = 'xx';
}

/*
|--------------------------------------------------------------------
| Render Flag
|--------------------------------------------------------------------
*/
if ( '___' === mb_substr( $data['country_code'], 0, 3 ) ) :

	$custom_country = anwp_football_leagues()->data->get_custom_county_data( $data['country_code'] );

	if ( $custom_country ) :
		$flag_size = $flag_size < 20 && $flag_size > 15 ? 20 : $flag_size;

		?>
		<img class="anwp-object-contain fl-flag--rounded anwp-rounded-none anwp-w-<?php echo absint( $flag_size ); ?> anwp-h-<?php echo absint( $flag_size ); ?> <?php echo esc_attr( $data['wrapper_class'] ); ?>"
				data-toggle="anwp-tooltip"
				data-tippy-content="<?php echo esc_attr( $custom_country['title'] ); ?>"
				src="<?php echo esc_url( $custom_country['image'] ); ?>" alt="<?php echo esc_attr( $custom_country['title'] ); ?>">
		<?php
	endif;
else :
	?>
	<svg class="fl-flag--rounded <?php echo esc_attr( $data['wrapper_class'] ); ?>"
			data-toggle="anwp-tooltip" data-tippy-content="<?php echo esc_attr( anwp_football_leagues()->data->get_value_by_key( $data['country_code'], 'country' ) ); ?>"
			width="<?php echo absint( $flag_size ); ?>" height="<?php echo absint( $flag_size ); ?>">
		<use href="<?php echo esc_url( $flags_url ); ?>#fl-flag--<?php echo esc_attr( $code_parsed ); ?>"></use>
	</svg>
	<?php
endif;
