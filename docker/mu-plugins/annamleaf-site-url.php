<?php
/**
 * Plugin Name: Annam Leaf — địa chỉ site khi chạy sau tunnel
 * Description: Cho WordPress biết địa chỉ công khai khi container chạy sau Cloudflare Tunnel hoặc reverse proxy. Chỉ dùng cho môi trường chạy thử trên máy.
 *
 * Đây là mu-plugin (must-use): WordPress nạp nó ở mọi lần chạy, không cần kích hoạt.
 *
 * Trước đây phần này nằm trong WORDPRESS_CONFIG_EXTRA của docker-compose.yml. Cách đó chỉ
 * chạy trên bản cài MỚI: image chỉ dùng biến đó lúc tự sinh wp-config.php lần đầu, nên một
 * install đã có sẵn không bao giờ nhận được thay đổi — site vẫn trả về link localhost và
 * CSS vỡ khi xem qua tunnel.
 *
 * @package AnnamLeafDocker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tunnel nói HTTPS với người xem nhưng HTTP với container. Không có đoạn này thì WordPress
 * tưởng mình đang chạy HTTP, sinh ra vòng lặp chuyển hướng và cảnh báo nội dung hỗn hợp.
 */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

$annamleaf_site_url = getenv( 'SITE_URL' );

if ( is_string( $annamleaf_site_url ) && '' !== $annamleaf_site_url ) {
	$annamleaf_site_url = untrailingslashit( $annamleaf_site_url );

	// Lọc option thay vì define hằng số: không ghi gì vào database, bỏ biến môi trường đi là
	// site tự quay về localhost, không phải dọn dẹp.
	add_filter(
		'option_home',
		static function () use ( $annamleaf_site_url ) {
			return $annamleaf_site_url;
		}
	);

	add_filter(
		'option_siteurl',
		static function () use ( $annamleaf_site_url ) {
			return $annamleaf_site_url;
		}
	);
}
