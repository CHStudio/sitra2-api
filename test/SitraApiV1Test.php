<?php
/**
 * This file is part of the beebot package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Agence Interactive 2015
 * @author    Stephane HULARD <s.hulard@chstudio.fr>
 */

/**
 * SitraApi v1 wrapper test
 */
class SitraApiV1Test extends SitraApiTest
{
	protected function setUp()
	{
		$this->object = new SitraApi(SitraApi::V001);
		$this->reflection = new \ReflectionClass($this->object);
	}
}