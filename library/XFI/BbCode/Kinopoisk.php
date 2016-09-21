<?php

class XFI_BbCode_Kinopoisk
{
	public static function tagKp(array $tag, array $rendererStates, XenForo_BbCode_Formatter_Base $formatter)
	{
		preg_match('/\d{3,7}/', $tag['children'][0], $id);
		$id = filter_var($id[0], FILTER_SANITIZE_NUMBER_INT);

		if (is_numeric($id) && intval($id)) {
			return XFI_BbCode_Kinopoisk::getRating($id);
		} else {
			return $tag['children'][0];
		}
	}

	public static function buildEmbed($mediaKey, array $site, $siteId)
	{
		preg_match('/\d{3,7}/', $mediaKey, $id);
		$id = filter_var($id[0], FILTER_SANITIZE_STRING);

		if (is_numeric($id) && intval($id)) {
			return XFI_BbCode_Kinopoisk::getRating($id);
		} else {
			return $mediaKey;
		}
	}

	private static function getRating($id)
	{
		$options = XenForo_Application::getOptions();

		$kp_rating_dir = XenForo_Helper_File::getExternalDataPath() . '/kinopoisk/';
		$kp_render_dir = dirname(__FILE__) . '/KpSource/' . $options->xfiKpTheme . '/';
		$kp_cache_time = ($options->xfiKpCache) ? 86400 * $options->xfiKpCache : 0;
		$kp_sitename   = ($options->xfiKpSitename) ? $options->xfiKpSitename : '';

		$image = imagecreatefrompng($kp_render_dir . 'na_kp.png');
		$font = $kp_render_dir . 'font.ttf';

		if (!file_exists($kp_rating_dir . $options->xfiKpTheme . $id . '.png') || (time() - filemtime($kp_rating_dir . $options->xfiKpTheme . $id . '.png')) > $kp_cache_time) {
			$xml = simplexml_load_file('http://rating.kinopoisk.ru/' . $id . '.xml');
			$kp_rating = substr($xml->kp_rating, 0, 3);
			$kp_votes  = number_format(intval($xml->kp_rating['num_vote']));

			if ($xml->kp_rating != 0) {
				$image  = imagecreatefrompng($kp_render_dir . 'back_kp.png');
				$star   = imagecreatefrompng($kp_render_dir . 'star.png');
				$color  = imagecolorallocate($image, 190, 190, 190);
				$r_font = imagecreatefrompng($kp_render_dir . 'rating_font.png');
				$v_font = imagecreatefrompng($kp_render_dir . 'votes_font.png');
				$rating = explode('.', $kp_rating);

				switch (end($rating)) {
					case '0': $symbol = 0; break;
					case '1': $symbol = 10; break;
					case '2': $symbol = 20; break;
					case '3': $symbol = 30; break;
					case '4': $symbol = 40; break;
					case '5': $symbol = 50; break;
					case '6': $symbol = 60; break;
					case '7': $symbol = 70; break;
					case '8': $symbol = 80; break;
					case '9': $symbol = 90; break;
				}
				switch (reset($rating)) {
					case '0': $symbol2 = 0; break;
					case '1': $symbol2 = 10; break;
					case '2': $symbol2 = 20; break;
					case '3': $symbol2 = 30; break;
					case '4': $symbol2 = 40; break;
					case '5': $symbol2 = 50; break;
					case '6': $symbol2 = 60; break;
					case '7': $symbol2 = 70; break;
					case '8': $symbol2 = 80; break;
					case '9': $symbol2 = 90; break;
				}
				imagecopy($image, $r_font, 93, 4, $symbol, 0, 10, 10);
				imagecopy($image, $r_font, 88, 4, 100, 0, 10, 10);
				imagecopy($image, $r_font, 76, 4, $symbol2, 0, 10, 10);
				$symbol_count = strlen($kp_votes);
				for ($i = 0, $next = 105 - $symbol_count * 5; $i != $symbol_count; $i++, $next = $next + 5) {
					$symbol = substr($kp_votes, $i, 1);
					if ($symbol == ',') $symbol = 40; else $symbol = intval($symbol) * 4;
					imagecopy($image, $v_font, $next, 18, $symbol, 0, 4, 6);
				}
				imagettftext($image, 6, 0, 4, 45, $color, $font, $kp_sitename);
				for ($i = 0, $next = 0; $i != (int)$kp_rating; $i++, $next = $next + 12) {
					imagecopy($image, $star, $next, 27, 0, 0, 10, 10);
				}
				$half_rating = explode('.', $kp_rating);
				$half_rating = end($half_rating);
				imagecopy($image, $star, $next, 27, 0, 0, $half_rating, 11);
				imagepng($image, $kp_rating_dir . $options->xfiKpTheme . $id . '.png', 9);
				$image = imagecreatefrompng($kp_rating_dir . $options->xfiKpTheme . $id . '.png');
			}
		} else {
			$image = imagecreatefrompng($kp_rating_dir . $options->xfiKpTheme . $id . '.png');
		}

		ob_start();
		header("Content-type: image/png");
		imagepng($image);
		imagedestroy($image);

		if ($options->xfiKpUrl) {
			return '<a href="http://www.kinopoisk.ru/film/' . $id . '" target="_blank"><img src="data:image/png;base64,' . base64_encode(ob_get_clean()) . '"></a>';
		} else {
			return '<img src="data:image/png;base64,' . base64_encode(ob_get_clean()) . '">';
		}
	}
}