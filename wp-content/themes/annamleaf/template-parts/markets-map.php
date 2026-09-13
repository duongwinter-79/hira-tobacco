<?php
/**
 * Export markets: a world map with the destinations marked, and the same destinations written
 * out beside it.
 *
 * The numbers tie the two together. Eight labels crowded around Southeast Asia would overlap
 * into nonsense — Laos, Cambodia, Singapore and Hong Kong sit within a few degrees of each
 * other — so the map carries numbered dots and the list carries the names.
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
?>
<section class="sec markets">
	<div class="wrap">
		<?php
		annamleaf_section_head(
			__( 'Where the leaf goes', 'annamleaf' ),
			__( 'Markets we ship to', 'annamleaf' )
		);
		?>

		<div class="markets-grid">
			<?php if ( '' !== $annamleaf_world ) : ?>
				<div class="markets-map">
					<svg viewBox="0 0 360 140" role="img"
						aria-label="<?php esc_attr_e( 'World map with the export markets marked. The same markets are listed beside it.', 'annamleaf' ); ?>">
						<?php
						// The silhouette. Already escaped at build time and regenerated from a
						// fixed source, not user input.
						echo $annamleaf_world; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped

						$annamleaf_n = 0;

						foreach ( $annamleaf_markets as $annamleaf_market ) {
							++$annamleaf_n;

							if ( null === $annamleaf_market['lon'] ) {
								continue;
							}

							list( $annamleaf_x, $annamleaf_y ) = annamleaf_markets_point(
								(float) $annamleaf_market['lon'],
								(float) $annamleaf_market['lat']
							);

							// A quadratic bow, lifted with distance, so short hops inside Asia stay
							// tight and the run to London reads as a long one.
							$annamleaf_mx   = ( $annamleaf_ox + $annamleaf_x ) / 2;
							$annamleaf_my   = ( $annamleaf_oy + $annamleaf_y ) / 2;
							$annamleaf_span = sqrt(
								pow( $annamleaf_x - $annamleaf_ox, 2 ) + pow( $annamleaf_y - $annamleaf_oy, 2 )
							);

							printf(
								'<path class="markets-map__arc" d="M%1$s %2$s Q%3$s %4$s %5$s %6$s"/>',
								esc_attr( round( $annamleaf_ox, 1 ) ),
								esc_attr( round( $annamleaf_oy, 1 ) ),
								esc_attr( round( $annamleaf_mx, 1 ) ),
								esc_attr( round( $annamleaf_my - $annamleaf_span * 0.22, 1 ) ),
								esc_attr( round( $annamleaf_x, 1 ) ),
								esc_attr( round( $annamleaf_y, 1 ) )
							);

							printf(
								'<circle class="markets-map__dot" cx="%1$s" cy="%2$s" r="4.6"/>'
								. '<text class="markets-map__num" x="%1$s" y="%3$s" text-anchor="middle">%4$s</text>',
								esc_attr( round( $annamleaf_x, 1 ) ),
								esc_attr( round( $annamleaf_y, 1 ) ),
								esc_attr( round( $annamleaf_y + 2.1, 1 ) ),
								esc_html( (string) $annamleaf_n )
							);
						}

						printf(
							'<circle class="markets-map__origin" cx="%1$s" cy="%2$s" r="3.4"/>',
							esc_attr( round( $annamleaf_ox, 1 ) ),
							esc_attr( round( $annamleaf_oy, 1 ) )
						);
						?>
					</svg>
				</div>
			<?php endif; ?>

			<div class="markets-list">
				<ol>
					<?php
					$annamleaf_n = 0;

					foreach ( $annamleaf_markets as $annamleaf_market ) :
						++$annamleaf_n;
						?>
						<li><span class="markets-list__n"><?php echo esc_html( (string) $annamleaf_n ); ?></span><?php echo esc_html( $annamleaf_market['name'] ); ?></li>
					<?php endforeach; ?>
				</ol>
				<p class="markets-note">
					<?php esc_html_e( 'Shipped on the Incoterms you nominate. Ask for current availability and lead times when you enquire.', 'annamleaf' ); ?>
				</p>
			</div>
		</div>
	</div>
</section>
