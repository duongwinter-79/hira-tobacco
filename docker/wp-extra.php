<?php
/**
 * Thêm vào wp-config.php của container, qua WORDPRESS_CONFIG_EXTRA.
 *
 * Để ở file riêng thay vì nhét thẳng vào docker-compose.yml, vì trong YAML của Compose thì
 * `$` phải viết thành `$$`, mà `$$_SERVER` lại là cú pháp PHP hợp lệ (variable variable) —
 * nên viết sai vẫn qua được `php -l` rồi hỏng lúc chạy. File riêng thì không có chuyện đó.
 *
 * @package AnnamLeafDocker
 */

// Địa chỉ site nhìn từ bên ngoài, khi chạy sau một tunnel hoặc reverse proxy.
$annamleaf_site_url = getenv( 'SITE_URL' );

if ( is_string( $annamleaf_site_url ) && '' !== $annamleaf_site_url ) {
	define( 'WP_HOME', $annamleaf_site_url );
	define( 'WP_SITEURL', $annamleaf_site_url );
}

// Tunnel nói HTTPS với người xem nhưng HTTP với container. Không có đoạn này thì WordPress
// tưởng mình đang chạy HTTP, sinh ra vòng lặp chuyển hướng và cảnh báo nội dung hỗn hợp.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}
