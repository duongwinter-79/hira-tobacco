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

$annamleaf_site_url = (string) getenv( 'SITE_URL' );

/*
 * SITE_URL=auto — lấy địa chỉ từ chính request đang tới.
 *
 * Link trycloudflare.com đổi mỗi lần mở tunnel. Nếu phải gõ lại địa chỉ vào SITE_URL rồi
 * dựng lại container mỗi lần, chỉ cần quên một bước là WordPress ép mọi request về địa chỉ
 * cũ đã chết, và trình duyệt báo NXDOMAIN. Với 'auto' thì tunnel nào cũng chạy, không phải
 * khởi động lại gì.
 *
 * Chỉ dùng cho môi trường xem tạm trên máy: nó tin vào Host header của request. Trên hosting
 * thật thì đặt địa chỉ cố định, hoặc bỏ hẳn biến này đi.
 */
if ( 'auto' === $annamleaf_site_url ) {
	$annamleaf_host = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : '';

	if ( preg_match( '/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $annamleaf_host ) ) {
		$annamleaf_scheme   = ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) ? 'https' : 'http';
		$annamleaf_site_url = $annamleaf_scheme . '://' . $annamleaf_host;
	} else {
		$annamleaf_site_url = '';
	}
}

if ( '' !== $annamleaf_site_url ) {
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
