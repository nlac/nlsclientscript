<?php

namespace nlac;

use Codeception\Util\Debug;

class NLSUtilsTest extends \Codeception\TestCase\Test {
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

	//Tests
	public function testAddUrlParams() {
		$p = array('x'=>'456');

		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?x=123', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?x=123&y=111#fff', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456&y=111#fff');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?y=111', $p),
			'http://domain.com:80/p1/p2/file.ext?y=111&x=456');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?y=111&z=2', $p),
			'http://domain.com:80/p1/p2/file.ext?y=111&z=2&x=456');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext#', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456#');
		$this->assertEquals(NLSUtils::addUrlParams('http://domain.com:80/p1/p2/file.ext?#fff', $p),
			'http://domain.com:80/p1/p2/file.ext?x=456#fff');
		$this->assertEquals(NLSUtils::addUrlParams('?', $p),
			'?x=456');
		$this->assertEquals(NLSUtils::addUrlParams('?#fff', $p),
			'?x=456#fff');
		$this->assertEquals(NLSUtils::addUrlParams('#', $p),
			'?x=456#');
	}

}