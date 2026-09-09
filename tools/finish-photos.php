<?php
/**
 * Crop a generated candidate into the theme.
 *
 * tools/generate-images.mjs writes raw model output to tools/generated/. This takes the
 * one you picked, crops it to the size the shot list specifies, saves it at JPEG quality
 * 80 under the 900 KB budget, and records in credits.json that the picture is synthetic.
 *
 * That last part is not decoration. plates.php prints the credit under every bundled
 * photograph, so an AI placeholder says so on the page, and nobody has to remember which
 * of these is a real photograph of the client's own operation. None of them are, yet.
 *
 *     php tools/finish-photos.php home 2      tools/generated/home-2.png -> the theme
 *     php tools/finish-photos.php stage-4     candidate 1 is the default
 *     php tools/finish-photos.php --all       candidate 1 of everything generated
 *
 * Page header frames (page-*) have no slot in the theme — WordPress only takes those as a
 * Featured image — so they land in tools/generated/final/ for you to upload by hand.
 *
 * @package AnnamLeaf
 */

const MAX_BYTES = 900 * 1024;
const QUALITY = 80;

$root = dirname( __DIR__ );
$in   = $root . '/tools/generated';
$out  = $root . '/wp-content/themes/annamleaf/assets/photos';
$pages = $in . '/final';

/**
 * Finished pixel size per frame, from the shot list.
 *
 * @param string $slot Frame name.
 * @return array{0: int, 1: int}|null
 */
function annamleaf_target( string $slot ): ?array {
	if ( 'home' === $slot ) {
		return array( 2400, 1350 );
	}

	if ( preg_match( '/^(stage-[1-7]|region)$/', $slot ) ) {
		return array( 1600, 1067 );
	}

	if ( preg_match( '/^leaf-[1-9]$/', $slot ) ) {
		return array( 1600, 1200 );
	}

	if ( str_starts_with( $slot, 'page-' ) ) {
		return array( 2400, 1350 );
	}

	return null;
}

/**
 * Scale to cover the target then crop from the centre, so nothing is squashed.
 *
 * @param GdImage $src Source image.
 * @param int     $tw  Target width.
 * @param int     $th  Target height.
 * @return GdImage
 */
function annamleaf_cover( GdImage $src, int $tw, int $th ): GdImage {
	$sw    = imagesx( $src );
	$sh    = imagesy( $src );
	$scale = max( $tw / $sw, $th / $sh );
	$cw    = (int) round( $tw / $scale );
	$ch    = (int) round( $th / $scale );
	$sx    = (int) round( ( $sw - $cw ) / 2 );
	$sy    = (int) round( ( $sh - $ch ) / 2 );

	$dst = imagecreatetruecolor( $tw, $th );
	imagecopyresampled( $dst, $src, 0, 0, $sx, $sy, $tw, $th, $cw, $ch );

	return $dst;
}

/**
 * Write JPEG, stepping the quality down until it fits the budget.
 *
 * @param GdImage $image Image to write.
 * @param string  $file  Destination path.
 * @return int Bytes written.
 */
function annamleaf_write_jpeg( GdImage $image, string $file ): int {
	for ( $quality = QUALITY; $quality >= 60; $quality -= 5 ) {
		ob_start();
		imagejpeg( $image, null, $quality );
		$bytes = ob_get_clean();

		if ( strlen( $bytes ) <= MAX_BYTES || 60 === $quality ) {
			file_put_contents( $file, $bytes );

			return strlen( $bytes );
		}
	}

	return 0;
}

/**
 * Find the candidate file for a frame, whatever extension the model returned.
 *
 * @param string $dir  Directory to look in.
 * @param string $slot Frame name.
 * @param int    $n    Candidate number.
 * @return string|null
 */
function annamleaf_candidate( string $dir, string $slot, int $n ): ?string {
	foreach ( array( 'png', 'jpg', 'jpeg', 'webp' ) as $ext ) {
		$file = "$dir/$slot-$n.$ext";

		if ( is_readable( $file ) ) {
			return $file;
		}
	}

	return null;
}

$args = array_slice( $argv, 1 );

if ( ! $args ) {
	fwrite( STDERR, "Usage: php tools/finish-photos.php SLOT [N]\n       php tools/finish-photos.php --all\n" );
	exit( 1 );
}

if ( ! is_dir( $in ) ) {
	fwrite( STDERR, "Nothing in tools/generated/ — run node tools/generate-images.mjs first.\n" );
	exit( 1 );
}

$jobs = array();

if ( '--all' === $args[0] ) {
	foreach ( glob( "$in/*-1.{png,jpg,jpeg,webp}", GLOB_BRACE ) as $file ) {
		$jobs[] = array( preg_replace( '/-1$/', '', pathinfo( $file, PATHINFO_FILENAME ) ), 1 );
	}

	if ( ! $jobs ) {
		fwrite( STDERR, "No candidates in tools/generated/.\n" );
		exit( 1 );
	}
} else {
	$jobs[] = array( $args[0], isset( $args[1] ) ? (int) $args[1] : 1 );
}

$credits_file = "$out/credits.json";
$credits      = is_readable( $credits_file )
	? json_decode( (string) file_get_contents( $credits_file ), true )
	: array();
$credits      = is_array( $credits ) ? $credits : array();
$model        = getenv( 'ANNAMLEAF_IMAGE_MODEL' ) ?: 'Gemini (Nano Banana)';
$done         = 0;

foreach ( $jobs as list( $slot, $n ) ) {
	$target = annamleaf_target( $slot );

	if ( ! $target ) {
		fwrite( STDERR, "  $slot — not a frame the theme knows. Run node tools/generate-images.mjs --list\n" );
		continue;
	}

	$file = annamleaf_candidate( $in, $slot, $n );

	if ( ! $file ) {
		fwrite( STDERR, "  $slot — no candidate $n in tools/generated/\n" );
		continue;
	}

	$src = @imagecreatefromstring( (string) file_get_contents( $file ) );

	if ( ! $src ) {
		fwrite( STDERR, "  $slot — could not read " . basename( $file ) . "\n" );
		continue;
	}

	$is_page = str_starts_with( $slot, 'page-' );
	$dir     = $is_page ? $pages : $out;

	if ( ! is_dir( $dir ) ) {
		mkdir( $dir, 0755, true );
	}

	$dst   = annamleaf_cover( $src, $target[0], $target[1] );
	$path  = "$dir/$slot.jpg";
	$bytes = annamleaf_write_jpeg( $dst, $path );

	imagedestroy( $src );
	imagedestroy( $dst );

	if ( ! $is_page ) {
		$credits[ $slot ] = array(
			'credit' => "AI-generated placeholder · $model",
			'source' => 'docs/ai-image-prompts.md',
			'title'  => "Synthetic image for the $slot frame",
			'query'  => '',
		);
	}

	$done++;
	printf(
		"  %-14s %dx%d  %d KB  %s\n",
		$slot,
		$target[0],
		$target[1],
		(int) round( $bytes / 1024 ),
		$is_page ? 'tools/generated/final/ (upload as Featured image)' : 'theme'
	);
}

if ( $done ) {
	ksort( $credits );

	// credits.json is tab-indented, the way fetch-photos.mjs writes it. Keep the two tools
	// producing byte-identical formatting so the file does not churn between runs.
	$json = json_encode( $credits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$json = preg_replace_callback(
		'/^ +/m',
		static fn( array $m ): string => str_repeat( "\t", (int) ( strlen( $m[0] ) / 4 ) ),
		(string) $json
	);

	file_put_contents( $credits_file, $json . "\n" );
	echo "\n$done finished. credits.json updated — the AI provenance shows under each picture.\n";
}
