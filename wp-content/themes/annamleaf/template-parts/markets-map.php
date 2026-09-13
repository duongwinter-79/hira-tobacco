<?php
/**
 * Export markets: the shipping routes drawn on the part of the world they cross.
 *
 * THE CROP IS THE DESIGN. Every market lies between London and Jakarta, so a whole-globe map
 * spent half its width on the Americas and then crushed Vietnam, Laos, Cambodia, Singapore and
 * Hong Kong into a thumbnail-sized huddle where their markers overlapped. The silhouette is
 * still the whole world; the viewBox shows the quarter of it this company trades across, which
 * is the quarter worth 900 pixels.
 *
 * Names sit on the map, not numbers keyed to a list. Matching "5" to a legend is work a reader
 * should not have to do. The four crowded markets get their names pushed clear and joined back
 * by a hairline, which is what the offsets in annamleaf_market_label_offset() are for.
 *
 * The written list survives below. It is what a screen reader reads and what a phone shows,
 * since the map is hidden there — a map this wide on a 375px screen is a smear.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;

$annamleaf_markets = annamleaf_markets();

if ( ! $annamleaf_markets ) {
	return;
}

$annamleaf_world = annamleaf_markets_world();
$annamleaf_from  = annamleaf_markets_origin();
list( $annamleaf_ox, $annamleaf_oy ) = annamleaf_markets_point( $annamleaf_from[0], $annamleaf_from[1] );

/*
 * The window on to the silhouette, in its own projected units (x = lon + 180, y = 84 - lat):
 * longitude -20 to 150, latitude 64 down to -10. London sits 18 units inside the left edge and
 * Jakarta 37 inside the right, so nothing is pinned to a border.
 */
$annamleaf_view = '160 20 170 74';
?>
<section class="sec markets">
	<div class="wrap">
		<?php
		annamleaf_section_head(
			__( 'Where the leaf goes', 'annamleaf' ),
			__( 'Markets we ship to', 'annamleaf' )
		);
		?>

		<?php if ( '' !== $annamleaf_world ) : ?>
			<figure class="markets-map">
				<svg viewBox="<?php echo esc_attr( $annamleaf_view ); ?>" role="img"
					aria-label="<?php esc_attr_e( 'Map of the shipping routes from Vietnam to each export market. Every market is also listed below.', 'annamleaf' ); ?>">
					<?php
					// The silhouette, built by tools/build-markets-map.mjs from a fixed public
					// domain source. Not user input, and escaped at build time.
					echo $annamleaf_world; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped

					$annamleaf_i = 0;

					foreach ( $annamleaf_markets as $annamleaf_market ) {
						if ( null === $annamleaf_market['lon'] ) {
							continue;
						}

						++$annamleaf_i;

						list( $annamleaf_x, $annamleaf_y ) = annamleaf_markets_point(
							(float) $annamleaf_market['lon'],
							(float) $annamleaf_market['lat']
						);

						list( $annamleaf_dx, $annamleaf_dy ) = annamleaf_market_label_offset( $annamleaf_market['name'] );

						$annamleaf_lx = $annamleaf_x + $annamleaf_dx;
						$annamleaf_ly = $annamleaf_y + $annamleaf_dy;

						if ( $annamleaf_dx < 0 ) {
							$annamleaf_anchor = 'end';
						} elseif ( $annamleaf_dx > 0 ) {
							$annamleaf_anchor = 'start';
						} else {
							$annamleaf_anchor = 'middle';
						}

						// A quadratic bow, lifted with distance, so a hop to Vientiane stays tight
						// and the run to London reads as the long haul it is.
						$annamleaf_mx   = ( $annamleaf_ox + $annamleaf_x ) / 2;
						$annamleaf_my   = ( $annamleaf_oy + $annamleaf_y ) / 2;
						$annamleaf_span = sqrt(
							pow( $annamleaf_x - $annamleaf_ox, 2 ) + pow( $annamleaf_y - $annamleaf_oy, 2 )
						);

						printf(
							'<g class="markets-map__leg" style="--i:%1$d">',
							esc_attr( (string) $annamleaf_i )
						);

						printf(
							'<path class="markets-map__arc" pathLength="1" d="M%1$s %2$s Q%3$s %4$s %5$s %6$s"/>',
							esc_attr( round( $annamleaf_ox, 1 ) ),
							esc_attr( round( $annamleaf_oy, 1 ) ),
							esc_attr( round( $annamleaf_mx, 1 ) ),
							esc_attr( round( $annamleaf_my - $annamleaf_span * 0.26, 1 ) ),
							esc_attr( round( $annamleaf_x, 1 ) ),
							esc_attr( round( $annamleaf_y, 1 ) )
						);

						// The hairline back to the name, only where the name had to be pushed off
						// its dot to escape the huddle.
						if ( abs( $annamleaf_dx ) > 4 || abs( $annamleaf_dy ) > 4 ) {
							printf(
								'<line class="markets-map__tie" x1="%1$s" y1="%2$s" x2="%3$s" y2="%4$s"/>',
								esc_attr( round( $annamleaf_x, 1 ) ),
								esc_attr( round( $annamleaf_y, 1 ) ),
								esc_attr( round( $annamleaf_lx - ( $annamleaf_dx > 0 ? 1.4 : ( $annamleaf_dx < 0 ? -1.4 : 0 ) ), 1 ) ),
								esc_attr( round( $annamleaf_ly - 1.1, 1 ) )
							);
						}

						printf(
							'<circle class="markets-map__dot" cx="%1$s" cy="%2$s" r="1.9"/>',
							esc_attr( round( $annamleaf_x, 1 ) ),
							esc_attr( round( $annamleaf_y, 1 ) )
						);

						printf(
							'<text class="markets-map__name" x="%1$s" y="%2$s" text-anchor="%3$s">%4$s</text></g>',
							esc_attr( round( $annamleaf_lx, 1 ) ),
							esc_attr( round( $annamleaf_ly, 1 ) ),
							esc_attr( $annamleaf_anchor ),
							esc_html( $annamleaf_market['name'] )
						);
					}

					// The origin last, so it sits above every arc that leaves it.
					printf(
						'<g class="markets-map__home"><circle class="markets-map__halo" cx="%1$s" cy="%2$s" r="4.4"/>'
						. '<circle class="markets-map__origin" cx="%1$s" cy="%2$s" r="2.1"/>'
						. '<text class="markets-map__from" x="%3$s" y="%4$s">%5$s</text></g>',
						esc_attr( round( $annamleaf_ox, 1 ) ),
						esc_attr( round( $annamleaf_oy, 1 ) ),
						esc_attr( round( $annamleaf_ox + 6.0, 1 ) ),
						esc_attr( round( $annamleaf_oy + 4.6, 1 ) ),
						esc_html( annamleaf_company_name() )
					);
					?>
				</svg>
			</figure>
		<?php endif; ?>

		<?php // No trading note here. The page's opening paragraph already says the leaf ships
		// on the Incoterms the buyer nominates, and saying it twice on one screen is filler. ?>
		<ul class="markets-list">
			<?php foreach ( $annamleaf_markets as $annamleaf_market ) : ?>
				<li><?php echo esc_html( $annamleaf_market['name'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
