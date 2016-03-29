<?php
/**
* nubodesk.php 1 2016-03-27 01:25:40Z newtonjr $
*
* Copyright (c) 2016, Nubodesk.  All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*   this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*   notice, this list of conditions and the following disclaimer in the
*   documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
* ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
* LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
* CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
* SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
* INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
* CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
* ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
* POSSIBILITY OF SUCH DAMAGE.
*
* Nubodesk is a trademark of nubodesk.com.
*/
if (!function_exists('curl_init')) {
  throw new Exception('Nubodesk needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Nubodesk needs the JSON PHP extension.');
}
/**
* Nubodesk PHP class
*
* @version 0.0.1
*/
class Nubodesk {
	const VERSION = '0.0.1';

	/**
	* Maps aliases to Nubodesk domains.
	*/
	// public static $DOMAIN_MAP = array(
	// 	'api'    => 'https://api.nubodesk.com',
	// 	'oauth'  => 'https://dev.nubodesk.com/',
	// 	'www'    => 'https://www.nubodesk.com/',
	// );
	public static $DOMAIN_MAP = array(
		'api'    => 'http://localhost:3000/',
		'oauth'  => 'http://localhost:3030/',
		'www'    => 'https://www.nubodesk.com/',
	);

	/**
	 * List of query parameters that get automatically dropped when rebuilding
	 * the current URL.
	 */
	protected static $DROP_QUERY_PARAMS = array(
		'code'
	);

	/**
	 * The Application Key.
	 *
	 * @var string
	 */
	protected $appKey;

	/**
	 * [$user data user]
	 * @var [type]
	 */
	// protected $user;

	/**
	 * The Application App Secret.
	 *
	 * @var string
	 */
	protected $appSecret;

	/**
	 * The OAuth token received in exchange for a valid authorization
	 * code.  null means the access token has yet to be determined.
	 *
	 * @var string
	 */
	protected $token = null;

	/**
	 * [$authorize description]
	 * @var boolean
	 */
	protected $authorize = false;
	protected $strCookie = '';

	protected $ch = null;

	/**
	* Default options for curl.
	*/
	public $CURL_OPTS = array(
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 60,
		CURLOPT_USERAGENT      => 'nubodesk-php-0.0.1',
	);

	/**
	 * [__construct description]
	 * @param [type] $config [description]
	 * The configuration:
	 * - appKey: the application Key
	 * - secret: the application secret
	 */
	public function __construct($config) {
		$this->appKey = $config['appKey'];
		$this->secret = $config['secret'];

		session_start();

		//
		// retorno com o codigo para trocar pelo token de acesso
		// 
		if (isset($_GET['code'])) {
			$this->getAccessTokenFromCode();
		}
	}

	/**
	 * [__destruct description]
	 */
	public function __destruct() {
		if ($this->authorize) {
			$this->api('logoff', 'POST');
		}
	}

	/**
	 * [getToken]
	 * @return [type] [description]
	 */
	protected function getAccessTokenFromCode() {
		$params = array(
			'grant_type' => 'authorization_code',
			'code' => $_GET['code'],
			'redirect_uri' => $this->getCurrentUrl()
		);

		$this->CURL_OPTS[CURLOPT_USERPWD] = $this->appKey . ":" . $this->secret;
		$this->CURL_OPTS[CURLOPT_POST] = 1;
		$this->CURL_OPTS[CURLOPT_HTTPHEADER] = array('Content-Type' => 'application/x-www-form-urlencoded');

		$ret = $this->makeRequest(self::$DOMAIN_MAP['oauth'].'oauth2/token', $params);
		if ($ret) {
			if (isset($ret['access_token'])) {
				$this->token = array('token' => $ret['access_token']['token'], 'secret' => $ret['access_token']['secret']);
			} else {
				exit(print_r($ret, true));
			}
		}
	}

	/**
	 * [hasToken description]
	 * @return boolean [description]
	 */
	public function hasToken(){
		return $this->token != null;
	}

	/**
	 * [setToken description]
	 * @param [type] $token  [description]
	 * @param [type] $secret [description]
	 */
	public function setToken($token, $secret){
		$this->token = array('token' => $token, 'secret' => $secret);
	}

	/**
	 * [getToken description]
	 * @return [type] [description]
	 */
	public function getToken(){
		return $this->token;
	}

	/**
	 * [getAuthorizeURL description]
	 * @param  [type] $redirectUri [description]
	 * @return [type]              [description]
	 */
	public function getAuthorizeURL() {
		$url = self::$DOMAIN_MAP['oauth'].'oauth2/authorize?';
		$url .= 'client_id='.$this->appKey;
		$url .= '&redirect_uri='.$this->getCurrentUrl();
		$url .= '&response_type=code';
		return $url;
	}

	/**
	* Returns the Current URL, stripping it of known NB parameters that should
	* not persist.
	*
	* @return string The current URL
	*/
	protected function getCurrentUrl() {
		if (isset($_SERVER['HTTPS']) &&
		($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
			$protocol = 'https://';
		}
		else {
			$protocol = 'http://';
		}
		$currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$parts = parse_url($currentUrl);

		$query = '';
		if (!empty($parts['query'])) {
			// drop known nb params
			$params = explode('&', $parts['query']);
			$retained_params = array();
			foreach ($params as $param) {
				if ($this->shouldRetainParam($param)) {
					$retained_params[] = $param;
				}
			}

			if (!empty($retained_params)) {
				$query = '?'.implode($retained_params, '&');
			}
		}

		// use port if non default
		$port =
		isset($parts['port']) &&
		(($protocol === 'http://' && $parts['port'] !== 80) ||
		($protocol === 'https://' && $parts['port'] !== 443))
		? ':' . $parts['port'] : '';

		// rebuild
		return $protocol . $parts['host'] . $port . $parts['path'] . $query;
	}

	/**
	 * Returns true if and only if the key or key/value pair should
	 * be retained as part of the query string.  This amounts to
	 * a brute-force search of the very small list of Nubodesk-specific
	 * params that should be stripped out.
	 *
	 * @param string $param A key or key/value pair within a URL's query (e.g.
	 *                     'foo=a', 'foo=', or 'foo'.
	 *
	 * @return boolean
	*/
	protected function shouldRetainParam($param) {
		foreach (self::$DROP_QUERY_PARAMS as $drop_query_param) {
			if (strpos($param, $drop_query_param.'=') === 0) {
			return false;
			}
		}

		return true;
	}

	/**
	 * [hasAuth return true if user has logging]
	 * @return boolean [description]
	 */
	protected function hasAuth() {
		if (!$this->authorize && $this->hasToken()) {

			$this->strCookie = session_name()."=".session_id()."; path=".session_save_path();

			$arr = array();
			$arr[CURLOPT_POST] = 1;
			$arr[CURLOPT_HTTPHEADER] = array(
				'Authorization' => 'Authorization: Bearer '.$this->token['secret'],
				'Content-Type' => 'application/x-www-form-urlencoded'
			);
			$arr[CURLOPT_HEADER] = true;

			$res = $this->makeRequest(self::$DOMAIN_MAP['api'].'auth', array(), $arr);

			if ($res != null && isset($res['status']) && $res['status'] == 'success') {
				$this->authorize = true;
			}
		}
		return $this->authorize;
	}

	/**
	* Make an API call.
	*
	* @return mixed The decoded response
	*/
	public function api($method, $type='GET', $params=array()) {
		if ($this->hasToken() && $this->hasAuth()) {

			// echo 'api', $method, "\n";
			
			$arr = array();
			if ($type == 'POST') {
				$arr[CURLOPT_POST] = 1;
			} else if ($type == 'PUT') {
				$arr[CURLOPT_CUSTOMREQUEST] = "PUT";
			} else if ($type == 'DELETE') {
				$arr[CURLOPT_CUSTOMREQUEST] = "DELETE";
			} else {
				$arr[CURLOPT_POST] = 0;
			}

			$arr[CURLOPT_COOKIESESSION] = true;
			$arr[CURLOPT_HTTPHEADER] = $arr[CURLOPT_HTTPHEADER] = array(
				'Content-Type' => 'application/x-www-form-urlencoded'
			);
			$arr[CURLOPT_COOKIE] = $this->strCookie;

			return $this->makeRequest(self::$DOMAIN_MAP['api'].$method, $params, $arr);

		} else {
			return false;
		}
	}

	/**
	 * [makeRequest description]
	 * @param  [type] $url    [description]
	 * @param  [type] $params [description]
	 * @param  [type] $ch     [description]
	 * @return [type]         [description]
	 */
	protected function makeRequest($url, $params, $options=null, $ch = null) {
		if ($ch == null) {
			$ch = curl_init();
		}

		$opts = $this->CURL_OPTS;
		if ($options != null && is_array($options)) {
			foreach ($options as $key => $value) {
				$opts[$key] = $value;
			}
		}

		if (count($params) > 0) {
			$opts[CURLOPT_POSTFIELDS] = http_build_query($params);
		}

		$opts[CURLOPT_URL] = $url;

		// disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
		// for 2 seconds if the server does not support this header.
		if (isset($opts[CURLOPT_HTTPHEADER])) {
			$existing_headers = $opts[CURLOPT_HTTPHEADER];
			$existing_headers[] = 'Expect:';
			$opts[CURLOPT_HTTPHEADER] = $existing_headers;
		} else {
			$opts[CURLOPT_HTTPHEADER] = array('Expect:');
		}

		curl_setopt_array($ch, $opts);
		$result = curl_exec($ch);

		if (isset($opts[CURLOPT_HEADER])) {
			$header_len = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($result, 0, $header_len);
			$result = substr($result, $header_len);

			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
			array_shift($matches); 
			$this->strCookie = implode("\n", $matches[0]);
		}

		if ($result === false || $result == '') {
			return false;
		}

		curl_close($ch);

		return json_decode($result, true);
	}
}
?>