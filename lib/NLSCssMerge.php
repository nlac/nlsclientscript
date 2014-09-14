<?php
if (!class_exists('NLSDownloader',false)) {
	require_once __DIR__ . '/NLSDownloader.php';
}

class NLSCssMerge {
	
	protected $options = array(
		'minify' => false,
		'closeCurl' => true
	);
	
	protected $downloader = array();
	
	public function __construct($options = array(), $downloader = array()) {
		
		$this->downloader = $downloader instanceof NLSDownloader ? $downloader : 
			new NLSDownloader(array_merge($this->downloader,$downloader));

		$this->options = array_merge($this->options, $options);
	}
	
	public function getDownloader() {
		return $this->downloader;
	}
	
	protected function replaceUrls($content, $baseUrl) {
		
		$content = preg_replace_callback('/(url\s*\(\s*[\'"]?\s*)([^\)\'"]+)/i', function($m) use ($baseUrl) {
			return $m[1] . $this->downloader->toAbsUrl($m[2], $baseUrl);
		}, $content);
		
		return $content;
	}

	protected function replaceImports($content, $baseUrl, $level) {
		$content = preg_replace_callback('/^\s*@import[\'"\s]*([^\)\'"]+)[\'";\s]*/m', function($m) use ($baseUrl, $level) {
			
			return $this->process($m[1], $baseUrl, null, $level+1);

		}, $content);
		
		return $content;
	}

	//Simple css minifier script
	//code based on: http://www.lateralcode.com/css-minifier/
	protected static function minify($css) {
		$css = preg_replace( '#/\*.*?\*/#s', '', $css);
		$css = preg_replace('/\s+/', ' ', $css);
		return trim(
			str_replace(
				array('; ', ': ', ' {', '{ ', ', ', '} ', ';}'), 
				array(';',  ':',  '{',  '{',  ',',  '}',  '}' ), 
				$css
			)
		);
	}
	
	/**
	 * 
	 */
	public function process($cssUrl, $baseUrl = null, $cssContent = null, $level = 0) {

		if (!$cssUrl && $cssContent===null)
			throw new Exception('Either the content or the url of the css must be given');

		if (!$baseUrl)
			$baseUrl = $this->downloader->options['appBaseUrl'];

		if ($cssContent === null) {
			$cssUrl = $this->downloader->toAbsUrl($cssUrl, $baseUrl);			
			$cssContent = $this->downloader->get($cssUrl);
		}

		$cssContent = $this->replaceUrls($cssContent, $cssUrl);
		$cssContent = $this->replaceImports($cssContent, $cssUrl, $level);

		if ($this->options['closeCurl'] && $level==0) {
			$this->downloader->close();
		}

		if ($this->options['minify'])
			$cssContent = self::minify($cssContent);

		return $cssContent;
	}
	
	public static function processUrl($cssUrl, $options = array(), $downloader = array()) {
		$merger = new self($options, $downloader);
		return $merger->process($cssUrl);
	}

	public static function processContent($content, $cssUrl = null, $options = array(), $downloader = array()) {
		$merger = new self($options, $downloader);
		return $merger->process($cssUrl, null, $content);
	}

}