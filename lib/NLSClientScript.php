<?php
if (!class_exists('NLSCssMerge',false)) {
	require_once __DIR__ . '/NLSCssMerge.php';
}
if (!class_exists('JSMin',false)) {
	require_once __DIR__ . '/JSMin.grove.min.php';
}

/** 
 * NLSClientScript v7.0
 * 
 * a Yii CClientScript extension for 
 * - preventing multiple loading of javascript files
 * - merging, caching registered javascript and css files
 * 
 * The extension is based on the great idea of Eirik Hoem, see
 * http://www.eirikhoem.net/blog/2011/08/29/yii-framework-preventing-duplicate-jscss-includes-for-ajax-requests/ 
 */

/**
 * @author nlac
 */

class NLSClientScript extends CClientScript {

/**
 * Public properties
**/

/**
 * @param string $includePattern
 * a javascript regex eg. '/\/scripts/' - if set, only the matched URLs will be filtered, defaults to null
 * (can be set to string 'null' also to ignore it)
**/
	public $includePattern = null;

/**
 * @param string $excludePattern
 * a javascript regex eg. '/\/raw/' - if set, the matched URLs won't be filtered, defaults to null
 * (can be set to string 'null' also to ignore it)
**/
	public $excludePattern = null;

/**
 * @param boolean $mergeJs
 * merge or not the registered script files, defaults to false
**/
	public $mergeJs = false;

/**
 * Merge/compress js on every request - for debug purposes only  
 */
	public $forceMergeJs = false;

/**
 * @param boolean $compressMergedJs
 * minify or not the merged js file, defaults to false
**/
	public $compressMergedJs = false;

/**
 * @param boolean $mergeCss
 * merge or not the registered css files, defaults to false
**/
	public $mergeCss = false;

/**
 * Merge/compress css on every request - for debug purposes only  
 */
	public $forceMergeCss = false;

/**
 * @param boolean $compressMergedCss
 * minify or not the merged css file, defaults to false
**/
	public $compressMergedCss = false;

/**
 * @param int $mergeAbove
 * only merges if there are more than mergeAbove file registered to be included at a position
 **/
	public $mergeAbove = 1;

/**
 * @param string $mergeJsExcludePattern
 * regex for php. the matched URLs won't be filtered
 **/
	public $mergeJsExcludePattern = null;

/**
 * @param string $mergeJsIncludePattern
 * regex for php. the matched URLs will be filtered
 **/
	public $mergeJsIncludePattern = null;

/**
 * @param string $mergeCssExcludePattern
 * regex for php. the matched URLs won't be filtered
 **/
	public $mergeCssExcludePattern = null;

/**
 * @param string $mergeCssIncludePattern
 * regex for php. the matched URLs will be filtered
 **/
	public $mergeCssIncludePattern = null;

/**
 * @param boolean $mergeIfXhr
 * if true then js files will be merged even if the request rendering the view is ajax
 * (if $mergeJs and $mergeAbove conds are satisfied)
 * defaults to false - no js merging if the view is requested by ajax
 **/
	public $mergeIfXhr = false;
	
/**
 * @param string $resMap2Request
 * code of a js function, prepares a get url by adding the script url hashes already in the dom
 * (has effect only if mergeIfXhr is true)
 */
	public $resMap2Request = 'function(url){if (!url.match(/\?/))url += "?";return url + "&nlsc_map=" + $.nlsc.smap();};';

/**
 * @param string $serverBaseUrl - removed
 * used to transform relative urls to absolute (for CURL)
 * you may define the url of the DOCROOT on the server (defaults to a composed value from the $_SERVER members) 
 **/
	//public $serverBaseUrl = '';

/**
 * @param string $appVersion
 * Optional, version of the application.
 * If set to not empty, will be appended to the merged js/css urls (helps to handle cached resources).
 **/
	public $appVersion = '';

/**
 * @param int $curlTimeOut
 * see http://php.net/manual/en/function.curl-setopt.php
 **/
	public $curlTimeOut = 15;

/**
 * @param int $curlConnectionTimeOut
 * see http://php.net/manual/en/function.curl-setopt.php
 **/
	public $curlConnectionTimeOut = 15;


/**
 * Protected members
 */

/**
 * @param object $ch
 * CURL resuurce
 */
	protected $ch = null;
	
	/**
	 * 
	 */
	protected $downloader = null;
	
	/**
	 * 
	 */
	protected $cssMerger = null;
	
	
	
	public function init() {
		parent::init();
		
		//we need jquery
		$this->registerCoreScript('jquery');
		
		//setup downloader
		$serverBase = Yii::app()->getRequest()->getHostInfo();
		
		$this->downloader = new NLSDownloader(array(
			'serverBaseUrl' => $serverBase,
			'appBaseUrl' => $serverBase . Yii::app()->getRequest()->getBaseUrl(),
			'curlConnectionTimeOut' => $this->curlConnectionTimeOut,
			'curlTimeOut' => $this->curlTimeOut
		));
		
		//setup css merger
		$this->cssMerger = new NLSCssMerge(array(
			'minify' => $this->compressMergedCss,
			'closeCurl' => false
		), $this->downloader);

	}

	protected function hashedName($name, $ext = 'js') {
		return 'nls' . crc32($name) . ( ($ext=='js'&&$this->compressMergedJs)||($ext=='css'&&$this->compressMergedCss) ? '-min':'') . '.' . $ext .
			($this->appVersion ? ('?' . $this->appVersion) : '');
	}
	
/**
 * Simple string hash, implemented also in the js part
 */
	protected function h($s) {
		$h = 0; $len = strlen($s);
		for ($i = 0; $i < $len; $i++) {
			$h = (($h<<5)-$h)+ord($s[$i]);
			$h &= 1073741823;
		}
		return $h;
	}

	protected function _mergeJs($pos) {
		$smap = null;

		if (Yii::app()->request->isAjaxRequest) {
			//do not merge for ajax requests
			if (!$this->mergeIfXhr)
				return;

			if ($smap = @$_REQUEST['nlsc_map'])
				$smap = @json_decode($smap);
		}
		
		if ($this->mergeJs && !empty($this->scriptFiles[$pos]) && count($this->scriptFiles[$pos]) > $this->mergeAbove) {
			$finalScriptFiles = array();
			$name = "/** Content:\r\n";
			$scriptFiles = array();
			
			//from yii 1.1.14 $scriptFile can be an array
			foreach($this->scriptFiles[$pos] as $src=>$scriptFile) {

				$absUrl = $this->downloader->toAbsUrl($src);
				
				if ($this->mergeJsExcludePattern && preg_match($this->mergeJsExcludePattern, $absUrl)) {
					$finalScriptFiles[$src] = $scriptFile;
					continue;
				}

				if ($this->mergeJsIncludePattern && !preg_match($this->mergeJsIncludePattern, $absUrl)) {
					$finalScriptFiles[$src] = $scriptFile;
					continue;					
				}

				$h = $this->h($absUrl);
				if ($smap && in_array($h, $smap))
					continue;
				
				//storing hash
				$scriptFiles[$absUrl] = $h;
				
				$name .= $src . "\r\n";
			}

			if (count($scriptFiles) <= $this->mergeAbove)
				return;
			
			$name .= "*/\r\n";
			$hashedName = $this->hashedName($name,'js');
			$path = Yii::app()->assetManager->basePath . '/' . $hashedName;
			$path = preg_replace('#\\?.*$#','',$path);
			$url = Yii::app()->assetManager->baseUrl . '/'. $hashedName;

			if ($this->forceMergeJs || !file_exists($path)) {

				$merged = '';
				$nlsCode = ';if (!$.nlsc) $.nlsc={resMap:{}};' . "\r\n";
				
				foreach($scriptFiles as $absUrl=>$h) {
					$ret = $this->downloader->get($absUrl);
					$merged .= ($ret . ";\r\n");
					$nlsCode .= '$.nlsc.resMap["' . $absUrl . '"]={h:"' . $h . '",d:1};' . "\r\n";
				}

				$this->downloader->close();

				if ($this->compressMergedJs)
					$merged = JSMin::minify($merged);
	
				file_put_contents($path, $name . $merged . $nlsCode);
			}
			
			$finalScriptFiles[$url] = $url;
			$this->scriptFiles[$pos] = $finalScriptFiles;
		}
	}


	protected function _mergeCss() {

		if ($this->mergeCss && !empty($this->cssFiles)) {
			
			$newCssFiles = array();
			$names = array();
			$files = array();
			foreach($this->cssFiles as $url=>$media) {
				
				$absUrl = $this->downloader->toAbsUrl($url);
				
				if ($this->mergeCssExcludePattern && preg_match($this->mergeCssExcludePattern, $absUrl)) {
					$newCssFiles[$url] = $media;
					continue;
				}
					
				if ($this->mergeCssIncludePattern && !preg_match($this->mergeCssIncludePattern, $absUrl)) {
					$newCssFiles[$url] = $media;
					continue;
				}

				if (!isset($names[$media]))
					$names[$media] = "/** Content:\r\n";
				$names[$media] .= ($url . "\r\n");

				if (!isset($files[$media]))
					$files[$media] = array();
				$files[$media][$absUrl] = $media;
			}

			//merging css files by "media"
			foreach($names as $media=>$name) {
				
				if (count($files[$media]) <= $this->mergeAbove) {
					$newCssFiles = array_merge($newCssFiles, $files[$media]);
					continue;
				}

				$name .= "*/\r\n";
				$hashedName = $this->hashedName($name,'css');
				$path = Yii::app()->assetManager->basePath . '/' . $hashedName;
				$path = preg_replace('#\\?.*$#','',$path);
				$url = Yii::app()->assetManager->baseUrl . '/'. $hashedName;

				if ($this->forceMergeCss || !file_exists($path)) {

					$merged = '';
					foreach($files[$media] as $absUrl=>$media) {
						
						$css = "/* $absUrl */\r\n" . $this->cssMerger->process($absUrl);
						
						$merged .= ($css . "\r\n");
					}
					
					$this->downloader->close();
					
					file_put_contents($path, $name . $merged);
				}//if
				
				$newCssFiles[$url] = $media;
			}//media
			
			$this->cssFiles = $newCssFiles;
		}
	}


	//If someone needs to access these, can be useful
	public function getScriptFiles() {
		return $this->scriptFiles;
	}
	public function getCssFiles() {
		return $this->cssFiles;
	}

	




	public function renderHead(&$output) {

		$this->_putnlscode();
		
		//merging
		if ($this->mergeJs) {
			$this->_mergeJs(self::POS_HEAD);
		}
		if ($this->mergeCss) {
			$this->_mergeCss();
		}

		parent::renderHead($output);
	}

	public function renderBodyBegin(&$output) {
		
		//merging
		if ($this->mergeJs)
			$this->_mergeJs(self::POS_BEGIN);

		parent::renderBodyBegin($output);
	}

	public function renderBodyEnd(&$output) {
		
		//merging
		if ($this->mergeJs)
			$this->_mergeJs(self::POS_END);

		parent::renderBodyEnd($output);
	}

	protected function _putnlscode() {

		if (Yii::app()->request->isAjaxRequest)
			return;

		//preparing vars for js generation
		if (!$this->excludePattern)
			$this->excludePattern = 'null';
		if (!$this->includePattern)
			$this->includePattern = 'null';
		$this->mergeIfXhr = ($this->mergeIfXhr ? 1 : 0);

		//js code
		$js = file_get_contents(__DIR__ . '/nlsc.min.js');
		$js = preg_replace_callback('/_(excludePattern|includePattern|mergeIfXhr|resMap2Request)_/', function($m) {
			return trim($this->$m[1],';');
		}, $js);

		$this->registerScript('fixDuplicateResources', $js, CClientScript::POS_HEAD);
	}
}