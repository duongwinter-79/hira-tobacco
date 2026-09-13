<?php
/**
 * Export markets: the list, and the map drawn from it.
 *
 * The markets are edited in Company profile → Export markets, one per line, so the client can
 * add Japan next year without a developer. A line is just the market's name; the coordinates
 * come from the gazetteer below. A name the gazetteer does not know still appears in the
 * written list — it simply gets no dot on the map, which is better than refusing to show it.
 *
 * The map is decoration and says so to assistive technology. The written list beside it is the
 * content: it is what a screen reader reads, what a search engine indexes, and what a buyer on
 * a phone sees, because a world map at 375px wide is not readable by anyone.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

/**
 * The projection, which must match tools/build-markets-map.mjs:
 *
 *     x = lon + 180        lon -180..180  ->  x 0..360
 *     y = 84 - lat         lat   84..-56  ->  y 0..140
 *
 * @param float $lon Longitude.
 * @param float $lat Latitude.
 * @return array{0: float, 1: float}
 */
function annamleaf_markets_point( float $lon, float $lat ): array {
	return array( $lon + 180.0, 84.0 - $lat );
}

/**
 * Where each market sits, so a name typed in wp-admin lands somewhere sensible.
 *
 * These are trade locations rather than strict centroids where the two differ. Russia is
 * Moscow, not the middle of Siberia; a dot in the empty Arctic would say nothing true about
 * where the leaf goes.
 *
 * Keys are lowercased for matching. Add a row and the name starts working.
 *
 * @return array<string, array{0: float, 1: float}>
 */
function annamleaf_market_places(): array {
	return array(
		'vietnam'              => array( 106.0, 16.5 ),
		'indonesia'            => array( 113.0, -1.5 ),
		'laos'                 => array( 103.5, 18.5 ),
		'cambodia'             => array( 104.9, 12.5 ),
		'singapore'            => array( 103.8, 1.3 ),
		'hong kong'            => array( 114.2, 22.3 ),
		'united kingdom'       => array( -2.0, 53.5 ),
		'uk'                   => array( -2.0, 53.5 ),
		'russia'               => array( 37.6, 55.8 ),
		'united arab emirates' => array( 54.5, 24.3 ),
		'uae'                  => array( 54.5, 24.3 ),
		// Plausible next markets, so adding one is typing a name and nothing else.
		'china'                => array( 108.0, 34.0 ),
		'japan'                => array( 138.0, 36.5 ),
		'south korea'          => array( 127.5, 36.5 ),
		'taiwan'               => array( 121.0, 23.7 ),
		'philippines'          => array( 122.0, 12.0 ),
		'malaysia'             => array( 102.0, 4.0 ),
		'thailand'             => array( 101.0, 15.0 ),
		'myanmar'              => array( 96.0, 21.0 ),
		'india'                => array( 78.0, 22.0 ),
		'bangladesh'           => array( 90.3, 23.8 ),
		'turkey'               => array( 35.0, 39.0 ),
		'egypt'                => array( 30.0, 27.0 ),
		'germany'              => array( 10.0, 51.0 ),
		'netherlands'          => array( 5.3, 52.1 ),
		'belgium'              => array( 4.5, 50.8 ),
		'poland'               => array( 19.4, 52.0 ),
		'united states'        => array( -98.0, 39.0 ),
		'usa'                  => array( -98.0, 39.0 ),
		'brazil'               => array( -51.0, -12.0 ),
	);
}

/**
 * The market list as entered, parsed.
 *
 * One per line. A line may carry its own coordinates after a pipe — "Japan | 138, 36" — for
 * anywhere the gazetteer does not cover.
 *
 * @return array<int, array{name: string, lon: ?float, lat: ?float}>
 */
function annamleaf_markets(): array {
	$raw = function_exists( 'annamleaf_option' ) ? (string) annamleaf_option( 'export_markets' ) : '';

	if ( '' === trim( $raw ) ) {
		return array();
	}

	$places  = annamleaf_market_places();
	$markets = array();

	foreach ( preg_split( '/\R/', $raw ) as $line ) {
		$line = trim( (string) $line );

		if ( '' === $line ) {
			continue;
		}

		$lon  = null;
		$lat  = null;
		$name = $line;

		if ( false !== strpos( $line, '|' ) ) {
			list( $name, $coords ) = array_map( 'trim', explode( '|', $line, 2 ) );
			$pair                  = array_map( 'trim', explode( ',', $coords ) );

			if ( 2 === count( $pair ) && is_numeric( $pair[0] ) && is_numeric( $pair[1] ) ) {
				$lon = (float) $pair[0];
				$lat = (float) $pair[1];
			}
		}

		if ( null === $lon ) {
			$key = strtolower( $name );

			if ( isset( $places[ $key ] ) ) {
				$lon = $places[ $key ][0];
				$lat = $places[ $key ][1];
			}
		}

		$markets[] = array( 'name' => $name, 'lon' => $lon, 'lat' => $lat );
	}

	return $markets;
}

/**
 * Where the leaf ships from. Origin of every arc on the map.
 *
 * @return array{0: float, 1: float}
 */
function annamleaf_markets_origin(): array {
	$places = annamleaf_market_places();

	return $places['vietnam'];
}

/**
 * The world silhouette, as the path element inside assets/markets-map.svg.
 *
 * Built by tools/build-markets-map.mjs from Natural Earth 110m, which is public domain. The
 * file is a valid standalone SVG so it can be opened and checked; only its path is wanted here,
 * because the markers have to sit in the same coordinate space.
 *
 * @return string
 */
function annamleaf_markets_world(): string {
	static $path = null;

	if ( null !== $path ) {
		return $path;
	}

	$path = '';
	$file = get_template_directory() . '/assets/markets-map.svg';

	if ( ! is_readable( $file ) ) {
		return $path;
	}

	$svg = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- local theme asset.

	if ( preg_match( '/<path\b[^>]*\/>/', $svg, $match ) ) {
		$path = $match[0];
	}

	return $path;
}
