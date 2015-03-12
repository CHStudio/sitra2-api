<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Agence Interactive 2014
 * @author	Stephane HULARD <s.hulard@chstudio.fr>
 */

/**
 * A wrapper used to interact with Sitra API endpoints
 * @link http://www.sitra-rhonealpes.com/wiki/index.php/API_Sitra_2
 */
class SitraApi
{
	/**
	 * Define sitra API versions
	 */
	const V001 = 'v001';
	const V002 = 'v002';

	/**
	 * Root URL for all API calls
	 */
	const BASE = "http://api.sitra-tourisme.com/api/";

	/**
	 * Get by id endpoint
	 * @link http://www.sitra-rhonealpes.com/wiki/index.php/API_-_services_-_v001/objet-touristique/get-by-id
	 * @link http://www.sitra-rhonealpes.com/wiki/index.php/API_-_services_-_v001/objet-touristique/get-by-identifier
	 */
	const GET = "/objet-touristique/get-by-{type}/{id}";

	/**
	 * Search and retrieve object list
	 * @link http://www.sitra-rhonealpes.com/wiki/index.php/API_-_services_-_v001/recherche/list-objets-touristiques/
	 */
	const SEARCH = "/recherche/list-objets-touristiques/";

	/**
	 * Parameter schema defined with validation rules
	 * This list is used for query construction
	 * @var array
	 */
	protected static $SCHEMA = array(
		self::GET => array(
			"responseFields" => "array",
			"locales" => "array",
			"id" => "string::detectTypeFromValue"
		),
		//http://www.sitra-rhonealpes.com/wiki/index.php/API_-_services_-_format_de_la_requete
		self::SEARCH => array(
			"identifiants" => "array",
			"identifiersV1" => "array",
			"selectionIds" => "array",
			"center" => "array",
			"radius" => "integer",
			"communeCodesInsee" => "array",
			"territoireIds" => "array[integer]",
			"searchQuery" => "string",
			"searchFields" => "/NOM|NOM_DESCRIPTION|NOM_DESCRIPTION_CRITERES/",
			"criteresQuery" => "string",
			"dateDebut" => "/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/",
			"dateFin" => "/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/",
			"first" => "integer",
			"count" => "integer::max200",
			"order" => "/NOM|IDENTIFIANT|PERTINENCE|DISTANCE|RANDOM/",
			"asc" => "boolean",
			"randomSeed" => "string",
			"locales" => "array",
			"responseFields" => "array",
			"membreProprietaireIds" => "array[integer]"
		)
	);

	/**
	 * Sitra API version used
	 * @var string
	 */
	protected $version = self::V001;

	/**
	 * Sitra API key, found in project details
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Sitra project identifier
	 * @var string
	 */
	protected $projetId;

	/**
	 * cURL handle used to perform requests
	 * @var resource
	 */
	protected $handle;

	/**
	 * Query that will be executed on the next exec method run
	 * @var array
	 */
	protected $criteria;

	/**
	 * Store the number of results which match the query
	 * @var integer
	 */
	protected $numFound;

	/**
	 * SitraApi
	 * @param string $apiKey
	 * @param string $projetId
	 */
	public function __construct($version = null) {
		if( !isset($version) ) {
			$version = self::V002;
		}
		$this->version($version);
	}

	/**
	 * Set access to sitra endpoint API
	 * @param string $apiKey
	 * @param string $projetId
	 * @return SitraApi
	 */
	public function configure($apiKey, $projetId) {
		$this->apiKey = $apiKey;
		$this->projetId = $projetId;

		return $this;
	}

	/**
	 * Set Sitra2 API version to use
	 * @return SitraApi
	 * @throws \RuntimeException
	 */
	public function version($version) {
		if( $version !== self::V001 && $version !== self::V002 ) {
			throw new \RuntimeException('Invalid API version: '.$version);
		}
		$this->version = $version;
		return $this;
	}

	/**
	 * Check if defined configuration is a valid one
	 * @return boolean
	 */
	public function check() {
		$this->prepareCurlHandle();
		curl_setopt($this->handle, CURLOPT_NOBODY, false);
		curl_setopt($this->handle, CURLOPT_POST, false);
		curl_setopt($this->handle, CURLOPT_URL, $this->url().'?query='.urlencode(json_encode($this->getCredentials())));
		curl_exec($this->handle);
		return curl_getinfo($this->handle, CURLINFO_HTTP_CODE) === 200;
	}

	/**
	 * Compute a valid endpoint URL
	 * @param string $endpoint
	 */
	public function url($endpoint = self::SEARCH) {
		return self::BASE.$this->version.$endpoint;
	}

	/**
	 * Access the total number of result for the last searched query
	 * @return integer
	 */
	public function getNumFound() {
		return $this->numFound;
	}

	/**
	 * Retrieve the built query. This method must be call before search because query is cleaned in search
	 * @return array
	 */
	public function getCriteria() {
		return $this->criteria;
	}

	/**
	 * Inject credentials in the given query
	 * @param array $a
	 * @return array
	 */
	public function getCredentials($a = array()) {
		$a['apiKey'] = $this->apiKey;
		$a[$this->version==='v001'?'siteWebExportIdV1':'projetId'] = $this->projetId;
		return $a;
	}

	/**
	 * Prepare curl handle resource
	 * @throws \RunTimeException
	 */
	private function prepareCurlHandle() {
		//Check that configuration is defined
		if( $this->apiKey === null || $this->projetId === null ) {
			throw new RunTimeException(
				"You need to configure your SitraApi instance with an Api key and a Site identifier!"
			);
		}

		if( $this->handle === null ) {
			$this->handle = curl_init();
		}
		curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->handle, CURLOPT_NOBODY, false);
		curl_setopt($this->handle, CURLOPT_POSTFIELDS, null);
		curl_setopt($this->handle, CURLOPT_POST, true);
		curl_setopt($this->handle, CURLINFO_HEADER_OUT, true);
	}

	/**
	 * Callback used to fill type attribute from the ID one
	 * @param string $name
	 */
	protected function detectTypeFromValue($name) {
		if( isset($this->criteria[$name]) ) {
			if( preg_match('/^[0-9]+$/', $this->criteria[$name]) ) {
				$this->criteria['type'] = 'id';
			} else {
				$this->criteria['type'] = 'identifier';
			}
		}
	}

	/**
	 * Validate that property set is smaller than 200
	 * @param string $name
	 */
	protected function max200( $name ) {
		if( $this->criteria[$name] > 200 ) {
			throw new InvalidArgumentException("You can't put a value bigger than 200 to: ".$name);
		}
	}

	/**
	 * Specific handler to validate that the given value fit the given array rule
	 * Rule is structured like : array[integer]
	 * Types are extracted by the gettype function
	 * @param string $name property name
	 * @param string $rules schema rule to validate
	 * @param array $value
	 */
	private function validateArray($name, $rules, $value) {
		$subType = array();
		if( preg_match("/^array\[([a-z]+)\]$/", $rules, $subType) ) {
			$subType = $subType[1];
		} else {
			$subType = "string";
		}

		$valid = array();
		foreach ($value as $item) {
			if( gettype($item) === $subType ) {
				$valid[] = $item;
			}
		}
		if( count( $valid ) > 0 ) {
			$this->criteria[$name] = $valid;
		}
	}

	/**
	 * Magic methods used to set criteria on the opened query
	 * @param string $name
	 * @param array $arguments
	 * @throws \RunTimeException
	 * @throws \BadMethodCallException
	 * @throws \InvalidArgumentException
	 * @return SitraApi
	 */
	public function __call( $name, $arguments = array() ) {
		if( !isset($this->query) ) {
			throw new RunTimeException('You must call "start" method before adding some criteria!');
		}

		$schema = self::$SCHEMA[$this->query['endpoint']];
		if(!array_key_exists($name, $schema)) {
			throw new BadMethodCallException('You can\'t set "'.$name.'" criterion on the selected endpoint: '.$this->query['endpoint']);
		}
		if( count($arguments) != 1 ) {
			throw new InvalidArgumentException('You can only give one argument to this method!');
		}

		//Extract some useful vars
		$value = $arguments[0];
		$type = gettype($value);
		$rules = $schema[$name];
		if( ($pos = strrpos($rules, '::')) !== false ) {
			$callback = substr($rules, $pos + 2);
			$rules = substr($rules, 0, $pos);
		}
		//Remove previously set value
		unset($this->criteria[$name]);

		//Sometimes we use regex to validate value
		if( strpos($rules, '/') !== false ) {
			if( $type === "string" && preg_match($rules, $value) ) {
				$this->criteria[$name] = $value;
			}
		//Sometime we use the type
		} else {
			//If this is a simple type
			if( $type !== "array" && $type === $rules ) {
				$this->criteria[$name] = $value;
			//If there is a complex type to check (sub array types)
			} elseif( substr($rules, 0, 5) === 'array' && $type === 'array' ) {
				$this->validateArray($name, $rules, $value);
			}
		}

		//If the property is not same, it does not match schema rules
		if( !isset($this->criteria[$name]) ) {
			throw new InvalidArgumentException('Given value can\'t be used to set "'.$name.'", you need to be: "'.$rules.'"');
		}
		//If there is a callback, we execute it
		if( isset($callback) ) {
			call_user_func(array($this, $callback), $name);
		}

		//The return current instance for fluent
		return $this;
	}

	/**
	 * Start query construction
	 * @param string $endpoint Must be self::GET or self::SEARCH
	 * @throws \InvalidArgumentException
	 * @return SitraApi
	 */
	public function start($endpoint = self::SEARCH) {
		if( !in_array($endpoint, array(self::GET, self::SEARCH)) ) {
			throw new InvalidArgumentException("You must specified a valid endpoint constant SitraApi::GET or SitraApi::LIST");
		}
		$this->prepareCurlHandle();

		$this->criteria = $this->query = array();
		$this->query["endpoint"] = $endpoint;
		$this->query["url"] = $this->url($endpoint);
		unset($this->numFound);

		return $this;
	}

	/**
	 * Apply a raw query to current search
	 * Used when the same query is repeated multiple times
	 * @param array $query
	 * @return SitraApi
	 */
	public function raw($query) {
		if( !is_array($query) ) {
			throw new InvalidArgumentException('You must give an array which contains the query!!');
		}
		foreach( $query as $key => $value ) {
			call_user_func(array($this, $key), $value);
		}
		return $this;
	}

	/**
	 * Execute the built query
	 * @throws \RuntimeException
	 * @return array
	 */
	public function search() {
		if( !isset($this->query['url']) ) {
			throw new RunTimeException('You need to call "start" method before trigger a search!');
		}

		extract($this->query);

		$parts = array();
		preg_match_all('/{([^}]+)}/', $url, $parts);
		if( isset($parts[1]) ) {
			foreach( $parts[1] as $part ) {
				if( !isset($this->criteria[$part]) ) {
					throw new RunTimeException('You need to define "'.$part.'" parameter before execute the query');
				}
				$url = str_replace('{'.$part.'}', $this->criteria[$part], $url);
				unset($this->criteria[$part]);
			}
		}

		//Add credentials and format Query
		$criteria = $this->getCredentials($this->criteria);
		$body = "";
		switch( $this->query['endpoint'] ) {
			case self::GET:
				$url .= '?';
				foreach( $criteria as $name => $value ) {
					$url .= $name.'='.(is_array($value)?implode(',',$value):$value).'&';
				}
				curl_setopt($this->handle, CURLOPT_POST, false);
				break;
			case self::SEARCH:
				$body = "query=".json_encode($criteria);
				curl_setopt($this->handle, CURLOPT_POSTFIELDS, $body);
				break;
		}
		unset($this->query);

		//Execute request
		curl_setopt($this->handle, CURLOPT_URL, $url);
		$result = curl_exec($this->handle);
		$info = curl_getinfo($this->handle);

		//Manage some cURL execution errors
		if( curl_getinfo($this->handle, CURLINFO_HTTP_CODE) !== 200 ) {
			throw new RunTimeException(
				'Processed request return a "'.$info['http_code'].'" HTTP: '.PHP_EOL.
				$info['request_header'].
				"BODY: ".PHP_EOL.
				$body);
		}
		if( ($result = json_decode($result)) === null ) {
			throw new RunTimeException("Can't decode JSON response!");
		}

		if( $endpoint === self::SEARCH ) {
			$this->numFound = $result->numFound;
			return isset($result->objetsTouristiques)?$result->objetsTouristiques:array();
		} else {
			return $result;
		}
	}
}