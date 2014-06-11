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
	 * Root URL for all API calls
	 */
	const BASE = "http://api.sitra-tourisme.com/api/";

	/**
	 * Sitra API version used
	 */
	const VERSION = "v001";

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

	protected static $SCHEMA = array(
		self::GET => array(
			"responseFields" => "array",
			"locales" => "array",
			"id" => "string::detectTypeFromId"
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
			"count" => "integer",
			"order" => "/NOM|IDENTIFIANT|PERTINENCE|DISTANCE|RANDOM/",
			"asc" => "boolean",
			"randomSeed" => "string",
			"locales" => "array",
			"responseFields" => "array",
			"membreProprietaireIds" => "array[integer]"
		)
	);

	/**
	 * Sitra API key, found in project details
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Sitra project identifier
	 * @var string
	 */
	protected $siteId;

	/**
	 * cURL handle used to perform requests
	 * @var resource
	 */
	protected $handle;

	/**
	 * Query that will be executed on the next exec method run
	 * @var array
	 */
	protected $query;

	/**
	 * @param string $apiKey
	 * @param string $siteId
	 */
	public function __construct($apiKey = null, $siteId = null) {
		$this->configure($apiKey, $siteId);
	}

	/**
	 * Set access to sitra endpoint API
	 * @param string $apiKey
	 * @param string $siteId
	 */
	public function configure($apiKey, $siteId) {
		$this->apiKey = $apiKey;
		$this->siteId = $siteId;

		return $this;
	}

	/**
	 * Check if defined configuration is a valid one
	 * @return boolean
	 */
	public function check() {
		$this->prepareCurlHandle();
		curl_setopt($this->handle, CURLOPT_NOBODY, true);
		curl_setopt($this->handle, CURLOPT_POST, false);
		curl_setopt($this->handle, CURLOPT_URL, $this->url());
		curl_exec($this->handle);
		return curl_getinfo($this->handle, CURLINFO_HTTP_CODE) === 200;
	}

	/**
	 * Compute a valid endpoint URL
	 * @param string $endpoint
	 */
	public function url($endpoint = self::SEARCH) {
		return self::BASE.self::VERSION.$endpoint;
	}

	/**
	 * Prepare curl handle resource
	 * @throws \RunTimeException
	 */
	private function prepareCurlHandle() {
		//Check that configuration is defined
		if( $this->apiKey === null || $this->siteId === null ) {
			throw new \RunTimeException(
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
	 */
	protected function detectTypeFromId() {
		if( isset($this->query['id']) ) {
			if( preg_match('/^[0-9]+$/', $this->query['id']) ) {
				$this->query['type'] = 'id';
			} else {
				$this->query['type'] = 'identifier';
			}
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
			$this->query[$name] = $valid;
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
			throw new \RunTimeException('You must call "start" method before adding some criteria!');
		}

		$schema = self::$SCHEMA[$this->query['endpoint']];
		if(!array_key_exists($name, $schema)) {
			throw new \BadMethodCallException('You can\'t set "'.$name.'" criterion on the selected endpoint: '.$this->query['endpoint']);
		}
		if( count($arguments) != 1 ) {
			throw new \InvalidArgumentException('You can only give one argument to this method!');
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
		unset($this->query[$name]);

		//Sometimes we use regex to validate value
		if( strpos($rules, '/') !== false ) {
			if( $type === "string" && preg_match($rules, $value) ) {
				$this->query[$name] = $value;
			}
		//Sometime we use the type
		} else {
			//If this is a simple type
			if( $type !== "array" && $type === $rules ) {
				$this->query[$name] = $value;
			//If there is a complex type to check (sub array types)
			} elseif( substr($rules, 0, 5) === 'array' && $type === 'array' ) {
				$this->validateArray($name, $rules, $value);
			}
		}

		//If the property is not same, it does not match schema rules
		if( !isset($this->query[$name]) ) {
			throw new \InvalidArgumentException('Given value can\'t be used to set "'.$name.'", you need to be: "'.$rules.'"');
		}
		//If there is a callback, we execute it
		if( isset($callback) ) {
			call_user_func(array($this, $callback));
		}

		//The return current instance for fluent
		return $this;
	}

	/**
	 * Start query construction
	 * @throws \InvalidArgumentException
	 * @return SitraApi
	 */
	public function start($endpoint = self::SEARCH) {
		if( !in_array($endpoint, array(self::GET, self::SEARCH)) ) {
			throw new \InvalidArgumentException("You must specified a valid endpoint constant SitraApi::GET or SitraApi::LIST");
		}
		$this->prepareCurlHandle();

		$this->query = array(
			"endpoint" => $endpoint,
			"url" => $this->url($endpoint)
		);
		return $this;
	}

	/**
	 * Execute the built query
	 * @throws \RuntimeException
	 * @return array
	 */
	public function search() {
		$parts = array();
		$url = $this->query['url'];
		preg_match_all('/{([^}]+)}/', $url, $parts);
		if( isset($parts[1]) ) {
			foreach( $parts[1] as $part ) {
				if( !isset($this->query[$part]) ) {
					throw new \RunTimeException('You need to define "'.$part.'" parameter before execute the query');
				}
				$url = str_replace('{'.$part.'}', $this->query[$part], $url);
				unset($this->query[$part]);
			}
		}

		$endpoint = $this->query['endpoint'];
		unset($this->query['url']);
		unset($this->query['endpoint']);

		//Add credentials and format Query
		$this->query['apiKey'] = $this->apiKey;
		$this->query['siteWebExportIdV1'] = $this->siteId;
		$body = "";
		switch( $endpoint ) {
			case self::GET:
				$url .= '?';
				foreach( $this->query as $name => $value ) {
					$url .= $name.'='.(is_array($value)?implode(',',$value):$value).'&';
				}
				curl_setopt($this->handle, CURLOPT_POST, false);
				break;
			case self::SEARCH:
				$body = "query=".json_encode($this->query);
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
			throw new \RunTimeException(
				'Processed request return a "'.$info['http_code'].'" HTTP: '.PHP_EOL.
				$info['request_header'].
				"BODY: ".PHP_EOL.
				$body);
		}
		if( ($result = json_decode($result)) === null ) {
			throw new \RunTimeException("Can't decode JSON response!");
		}

		return isset($result->objetsTouristiques)?$result->objetsTouristiques:$result;
	}
}