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

/**
 * Nửa "host[:port]" của một địa chỉ — đúng phần bị sai khi xem qua tunnel.
 *
 * So bằng host chứ không bằng cả chuỗi, vì content_url() chạy địa chỉ qua set_url_scheme()
 * trước: scheme đã bị đổi sang https, chỉ còn host là dấu vết của địa chỉ cũ.
 *
 * @param string $url Địa chỉ cần đọc.
 * @return string Rỗng nếu địa chỉ không có host.
 */
function annamleaf_url_origin( string $url ): string {
	$parts = wp_parse_url( $url );

	if ( empty( $parts['host'] ) ) {
		return '';
	}

	return $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
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

	/*
	 * Địa chỉ của wp-content phải sửa thêm một lần nữa, tách khỏi hai filter ở trên.
	 *
	 * wp-settings.php đóng băng WP_CONTENT_URL từ siteurl trong database:
	 *
	 *     496: wp_plugin_directory_constants();   // define WP_CONTENT_URL, đọc get_option('siteurl')
	 *     506: foreach ( wp_get_mu_plugins() ... ) // file này mới được nạp ở đây
	 *
	 * Mười dòng, nhưng đủ để hai filter option_* ở trên tới quá muộn — và hằng số thì không
	 * define lại được. Kết quả là một trang nửa đúng nửa sai: link, canonical, REST đều mang
	 * địa chỉ tunnel, còn style.css, site.js và mọi tấm ảnh của theme vẫn trỏ về
	 * localhost:8888. Trình duyệt người xem không tải được cái nào — đó chính là lúc CSS "vỡ".
	 *
	 * Không sửa được hằng số thì sửa lúc đọc: content_url(), plugins_url() và wp_upload_dir()
	 * đều chạy kết quả qua filter trước khi trả về.
	 */
	$annamleaf_rebase = static function ( $url ) use ( $annamleaf_site_url ) {
		if ( ! is_string( $url ) || '' === $url || ! defined( 'WP_CONTENT_URL' ) ) {
			return $url;
		}

		$annamleaf_stale = annamleaf_url_origin( (string) WP_CONTENT_URL );

		/*
		 * Chỉ đúng một địa chỉ là sai: cái WP_CONTENT_URL giữ lại lúc khởi động. Mọi địa chỉ
		 * khác giữ nguyên — CDN đặt cố ý, hay địa chỉ vốn đã đúng, sửa vào là hỏng thêm.
		 */
		if ( '' === $annamleaf_stale || annamleaf_url_origin( $url ) !== $annamleaf_stale ) {
			return $url;
		}

		$parts = wp_parse_url( $url );

		return $annamleaf_site_url
			. ( isset( $parts['path'] ) ? $parts['path'] : '' )
			. ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
	};

	add_filter( 'content_url', $annamleaf_rebase );
	add_filter( 'plugins_url', $annamleaf_rebase );

	/*
	 * wp_upload_dir() dựng baseurl thẳng từ WP_CONTENT_URL, nên ảnh trong Media Library vỡ y
	 * hệt ảnh của theme. Site đang dùng ảnh nằm trong theme nên chưa thấy — nhưng khách thêm
	 * một tấm ảnh qua wp-admin là gặp ngay.
	 */
	add_filter(
		'upload_dir',
		static function ( $uploads ) use ( $annamleaf_rebase ) {
			foreach ( array( 'url', 'baseurl' ) as $annamleaf_key ) {
				if ( ! empty( $uploads[ $annamleaf_key ] ) ) {
					$uploads[ $annamleaf_key ] = $annamleaf_rebase( $uploads[ $annamleaf_key ] );
				}
			}

			return $uploads;
		}
	);
}
