<?php
namespace nlac;

use Codeception\Util\Debug;
use Codeception\Util\Stub;
use Codeception\Module;
use AspectMock\Test as test;

class NLSCssMergeTest extends \Codeception\TestCase\Test {
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
		
		//Mocking mkdir()
		test::func('nlac','mkdir',function($dir=''){});
	}

	protected function _after() {
		test::clean();
	}
	
	//Tests - TODO
	public function testProcess() {
		$d = new NLSDownloader();
		$m = new NLSCssMerge([
			'downloadResources' => true,
			'downloadResourceRootPath' => '/',
			'downloadResourceRootUrl' => '/',
			'minify' => false,
			'closeCurl' => true
		], $d);
	}

}