<?php
/**
 * Title: Crop calendar
 * Slug: annamleaf/crop-calendar
 * Categories: annamleaf
 * Description: When the crop moves, as a table the client fills in with real months.
 *
 * @package AnnamLeaf
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'When the crop moves', 'annamleaf' ); ?></h2><!-- /wp:heading -->

<!-- wp:table -->
<figure class="wp-block-table"><table>
	<thead><tr>
		<th><?php esc_html_e( 'Stage', 'annamleaf' ); ?></th>
		<th><?php esc_html_e( 'Period', 'annamleaf' ); ?></th>
		<th><?php esc_html_e( 'Notes', 'annamleaf' ); ?></th>
	</tr></thead>
	<tbody>
		<tr><td><?php esc_html_e( 'Sowing', 'annamleaf' ); ?></td><td>—</td><td><?php esc_html_e( 'In our own nurseries', 'annamleaf' ); ?></td></tr>
		<tr><td><?php esc_html_e( 'Transplanting', 'annamleaf' ); ?></td><td>—</td><td><?php esc_html_e( 'Varies by region and weather', 'annamleaf' ); ?></td></tr>
		<tr><td><?php esc_html_e( 'Harvest and curing', 'annamleaf' ); ?></td><td>—</td><td><?php esc_html_e( 'Several picking passes', 'annamleaf' ); ?></td></tr>
		<tr><td><?php esc_html_e( 'Processing', 'annamleaf' ); ?></td><td>—</td><td><?php esc_html_e( 'Threshing line running', 'annamleaf' ); ?></td></tr>
		<tr><td><?php esc_html_e( 'Shipment', 'annamleaf' ); ?></td><td>—</td><td><?php esc_html_e( 'Ex port of loading', 'annamleaf' ); ?></td></tr>
	</tbody>
</table></figure>
<!-- /wp:table -->
