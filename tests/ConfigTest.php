<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class ConfigTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provide_get_holidays
	 */
	public function test_get_holidays($input, $expect) {
		$result = get_holidays($input);
		$debug = array(
			'input' => $input,
			'expect' => $expect,
			'result' => $result,
		);
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_holidays() {
		$winter = array(
			1 => array(1),
		);
		$summer = array(
			4 => array(1),
			5 => array(28, 29),
			7 => array(4),
		);
		$fall = array(
			9 => array(3,4),
			10 => array(31),
			11 => array(23, 26),
			12 => array(24, 25, 31),
		);

		return array(
			array('summer', $summer),
			array('fall', $fall),
			array('winter', $winter),
		);
	}

	/**
	 * @dataProvider provide_get_num_dinners_per_assignment
	 */
	public function test_get_num_dinners_per_assignment($input, $expect) {
		$result = get_num_dinners_per_assignment($input);
		$debug = array(
			'input' => $input,
			'expect' => $expect,
			'result' => $result,
		);
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_num_dinners_per_assignment() {
		return array(
			array(MEETING_NIGHT_CLEANER, 2),
			array('undefined entry', 0),
		);
	}
}
?>
