<?php
/**
 * @author John Smart
 */

require 'bootstrap.php';

use dotty\Dotty;

class DottyTest extends \PHPUnit_Framework_TestCase
{
	public function test0() {
		$data = array('name' => 'Scrooge McDuck');
		$found = &Dotty::dot('name', $data);
		$this->assertEquals('Scrooge McDuck', $found);

		$data = array('Daffy Duck');
		$found = &Dotty::dot('[0]', $data);
		$this->assertEquals('Daffy Duck', $found);
	}

	public function test1() {
		$data = array(
			'piece' => array(
				'name' => 'Mozarts 9th',
				'measures' => array(
					array(
						'notes' => array(1, 2, 3),
						'intensity' => 10
					),
					array(
						'notes' => array(1, 4, 5),
						'intensity' => 7
					),
					array(
						'notes' => array(2, 3, 4, 5),
						'intensity' => 5
					)
				)
			)
		);

		$found = &Dotty::dot('piece.name', $data);
		$this->assertEquals($data['piece']['name'], $found);

		$found	= 'Radiohead';
		$this->assertEquals($found, $data['piece']['name']);

		$found = &Dotty::dot('piece.measures[1].notes', $data);
		$this->assertEquals($data['piece']['measures'][1]['notes'], $found);

		$found = &Dotty::dot('piece.measures[1].notes[2]', $data);
		$this->assertEquals($data['piece']['measures'][1]['notes'][2], $found);

		$found = &Dotty::dot('piece.measures[1].notes[2]', $data);
		$found	= 14;
		$this->assertEquals($found, $data['piece']['measures'][1]['notes'][2]);
	}

	public function testEx() {
		$this->setExpectedException('\InvalidArgumentException');
		Dotty::dot('foobar', $data = array());
	}
}