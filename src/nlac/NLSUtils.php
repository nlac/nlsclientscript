<?php
namespace nlac;

class NLSUtils {
	
	public static function addUrlParams($url, $params) {
		if (strpos($url, '?') !== false)
			return preg_replace_callback('@\\?([^#]*)@', function($m) use ($params) {
				if (!empty(@$m[1])) {
					parse_str($m[1], $oldParams);
					$params = array_merge($oldParams, $params);
				}
				return '?' . http_build_query($params);
			}, $url);

		return preg_replace('@(#[^#]*)?$@', '?' . http_build_query($params) . '$0', $url, 1);
	}
	
	public static function isAbsoluteUrl($url) {
		return preg_match('@^https?://@', $url);
	}
	
	public static function getFileMTime($path){
		// append doc root and remove trailing
		$path = $_SERVER['DOCUMENT_ROOT'].preg_replace('@[\?#].*$@', '', $path);
		return file_exists($path) ? filemtime($path) : 0;
	}

}
