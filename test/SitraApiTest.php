<?php
/**
 * This file is part of the beebot package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Agence Interactive 2014
 * @author    Stephane HULARD <s.hulard@chstudio.fr>
 */

/**
 * SitraApi wrapper test
 */
class SitraApiTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var SitraApi
	 */
	protected $object;

	/**
	 * @var \ReflectionClass
	 */
	protected $reflection;

	protected function setUp()
	{
		$this->object = new SitraApi();
		$this->reflection = new \ReflectionClass($this->object);
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testUnconfigured() {
		$this->object->check();
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testBadConfiguration() {
		$this->object
			->configure("someApi", "someSiteId")
			->start(SitraApi::GET)
			->id('sitraSTR343985')
			->search();
	}

	/**
	 * @expectedException \RunTimeException
	 */
	public function testSearchWithoutAllRequired() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start(SitraApi::GET)
			->search();
	}
	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testStartWithBadEndpoint() {
		$this->object
			->start("aFunnyEndpoint;)");
	}

	/**
	 * @expectedException \RunTimeException
	 */
	public function testSearchWithoutStart() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->search();
	}

	/**
	 * @expectedException \RunTimeException
	 */
	public function testCriterionWithoutStart() {
		$this->object
			->responseFields(array("id"))
			->search();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidRaw() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start()
			->raw('string');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testBadCriterionFormat() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start()
			->count("string") //count must be integer
			->search();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testBadCriterionWrongParameters() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start()
			->count() //all property setter must give 1 parameter
			->search();
	}

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testSearchCriterionOnGetEndpoint() {
		$result = $this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start(SitraApi::GET)
			->count(10)
			->search();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testMaxCount() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start()
			->membreProprietaireIds(array(841))
			->count(500) //Sitra always return a max of 200
			->search();
	}


	public function testConfigureAndCheck() {
		$this->object->configure("someApi", "someSiteId");

		$prop = $this->reflection->getProperty('apiKey');
		$prop->setAccessible(true);
		$this->assertEquals("someApi", $prop->getValue($this->object));

		$prop = $this->reflection->getProperty('siteId');
		$prop->setAccessible(true);
		$this->assertEquals("someSiteId", $prop->getValue($this->object));

		$this->assertFalse($this->object->check());
	}

	public function testConfigureAndSearch() {
		$this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start()
			->responseFields(array('id'))
			->order('NOM')
			->count(5)
			->selectionIds(array(AI_SELECTIONID));
		$this->assertTrue($this->object->check());
		$result = $this->object->search();

		$this->assertCount(5, $result);
		$this->assertEquals(10, $this->object->getNumFound());

		$criteria = $this->object->getCriteria();
		$this->assertEquals(array(AI_SELECTIONID), $criteria['selectionIds']);
		$this->assertEquals(5, $criteria['count']);
		$this->assertEquals('NOM', $criteria['order']);
	}

	public function testConfigureAndGet() {
		$result = $this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start(SitraApi::GET)
			->id('162222')
			->search();

		$this->assertEquals(162222, $result->id);
		$this->assertEquals('PATRIMOINE_NATUREL', $result->type);
		$this->assertEquals("Pas du Frou" , $result->nom->libelleFr);
	}

	public function testConfigureRawAndGet() {
		$result = $this->object
			->configure(AI_APIKEY, AI_SITEID)
			->start(SitraApi::GET)
			->raw(array("id"=>'162222'))
			->search();

		$this->assertEquals(162222, $result->id);
		$this->assertEquals('PATRIMOINE_NATUREL', $result->type);
		$this->assertEquals("Pas du Frou" , $result->nom->libelleFr);
	}
}