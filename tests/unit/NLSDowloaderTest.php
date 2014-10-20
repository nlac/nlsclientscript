<?php

namespace nlac;

use Codeception\Util\Debug;

class NLSDowloaderTest extends \Codeception\TestCase\Test {
   /**
	* @var \UnitTester
	*/
	protected $tester;

	protected function _before() {
		$_SERVER = array_merge($_SERVER, [
			'HTTP_HOST' => 'localhost',
			'SERVER_PORT'=> '80',
			'REQUEST_URI'=> '/test'
		]);
		
	}

	protected function _after() {
		
	}
	
	//Testing toAbsUrl() for a particulare base
	protected function _testToAbsUrl($d, $base) {
		$path='';$port='';
		extract(parse_url($base));
		$root = (@$scheme?$scheme.':':'') . '//' . $host . (@$port?':'.$port:'');
		//remove non-directory (file) part from the end of $path
		$path = preg_replace('@/([^/]*\.)+[^/]*$@', '', $path);
		$clearBase = $root . $path;
		
		Debug::debug("\nTesting NLSDownloader::toAbsUrl() with base\nbase=$base");
		Debug::debug('root=' . $root);
		Debug::debug('clearBase=' . $clearBase);
		Debug::debug(parse_url($base));
		
		//Absolute urls
		$rel = 'http://google.com';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($rel, $abs, 'Absolute URL mapped to itself');
		
		//Protocol-relative urls
		$rel = '//google.com/somefile.txt';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($scheme . ':' . $rel, $abs);
		
		//Queries
		$rel = '?x=1&y=2#somefragment';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($clearBase . $rel, $abs);
		
		//Fragments
		$rel = '#somefragment';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($clearBase . $rel, $abs);
		
		//Root-relative urls
		$rel = '/somepath2';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($root. $rel, $abs);
		
		$rel = '/somepath2/a/';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($root. $rel, $abs);
		
		$rel = '/somepath2/a.txt';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals($root. $rel, $abs);
		
		//Relative urls
		$rel = 'somepath2';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals(rtrim($clearBase,'/') . '/' . $rel, $abs);
		
		$rel = 'somepath2/a/./../a';
		$abs = $d->toAbsUrl($rel, $base);
		$this->assertEquals(rtrim($clearBase,'/') . '/somepath2/a', $abs);
		
		if (preg_match('@\.css$@',$base)) {
			$rel = '../img/bg.png';
			$abs = $d->toAbsUrl($rel, $base);
			$this->assertEquals($root . '/img/bg.png', $abs);
		}

	}
	
	//Tests
	public function testToAbsUrl() {
		$d = new NLSDownloader();
		$this->_testToAbsUrl($d, 'https://somedomain.com/somepath1/somepath2?x=1&y=2#frag');
		$this->_testToAbsUrl($d, 'https://somedomain.com/');
		$this->_testToAbsUrl($d, 'https://somedomain.com:80');
		$this->_testToAbsUrl($d, 'http://somedomain.com/somepath1/');
		$this->_testToAbsUrl($d, 'https://somedomain.com:81/somepath1/main.css');
	}

}