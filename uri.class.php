<?php
/**
 * PHP library for working with URI's. Requires
 * PHP 5.3.7 or later. Replaces and extends PHP's
 * parse_url()
 * 
 * Based on P Guardiario's original work
 * 
 * @author    Nicholas Jordon
 * @copyright 2014 Nicholas Jordon - All Rights Reserved
 * @license   http://opensource.org/licenses/MIT
 * @version   0.1.0
 * @see       http://en.wikipedia.org/wiki/URI_scheme
 */
 
/**
 * PHP URI
 * 
 * Parses the input as a URI string. On failure $error
 * is set to 1 and $error_msg is populated.
 */
class uri {
	
	/*** Variables ***/
	public $input;
	public $scheme;
	public $protocol;
	public $scheme_name;
	public $user;
	public $username;
	public $pass;
	public $password;
	public $host;
	public $fqdn;
	public $port;
	public $authority;
	public $path;
	public $query;
	public $fragment;
	public $error;
	public $error_msg;
	
	
	/*** Methods ***/
	
	
	/**
	 * Parses the input as a URI and populates the
	 * variables. Fails if input is not a string or
	 * if the string cannot be parsed as a URI.
	 * 
	 * @param string $input The URI to parse.
	 */
	public function __construct($input) {
		$t = $this;
		$t->input    = $input;
		$t->error    = FALSE;
		$t->protocol = &$this->scheme;
		$t->username = &$this->user;
		$t->password = &$this->pass;
		$t->fqdn     = &$this->host;
		if (!is_string($input)) {
			$t->error = TRUE;
			$t->error_msg = 'Input was not a string!';
			
			$t->scheme      = FALSE;
			$t->scheme_name = FALSE;
			$t->user        = FALSE;
			$t->pass        = FALSE;
			$t->host        = FALSE;
			$t->port        = FALSE;
			$t->authority   = FALSE;
			$t->path        = FALSE;
			$t->query       = FALSE;
			$t->fragment    = FALSE;
		} else {
			$this->parse($input);
		}
	}
	
	/**
	 * Parses the supplied string as a URI and sets the
	 * variables in the class.
	 * 
	 * @param  string $uri The string to be parsed.
	 * @return void
	 */
	protected function parse($uri) {
		if ($this->error) {
			return FALSE;
		}
		$t = $this;
		$parsed = $t->_parse($uri);
		if (empty($parsed)) {
			$t->error = TRUE;
			$t->error = 'Could not parse the input as a URI';
			return $parsed;
		}
		$defaults = array(
			'scheme'      => '',
			'scheme_name' => '',
			'user'        => '',
			'pass'        => '',
			'host'        => '',
			'port'        => '',
			'authority'   => '',
			'path'        => '',
			'query'       => '',
			'fragment'    => ''
		);
		
		$values = $parsed + $defaults;
		
		$t->scheme         = $values['scheme'];
		$t->scheme_name    = $values['scheme_name'];
		$t->scheme_symbols = $values['scheme_symbols'];
		$t->user           = $values['user'];
		$t->pass           = $values['pass'];
		$t->host           = $values['host'];
		$t->port           = $values['port'];
		$t->path           = $values['path'];
		$t->query          = $values['query'];
		$t->fragment       = $values['fragment'];
		
		$t->gen_authority();
	}
	
	/**
	 * Helper function for parse(). Uses Regex instead of
	 * PHP's parse_url(). This makes the parsing much
	 * more accurate.
	 * 
	 * The regex used isn't perfect, but has a VERY LOW
	 * chance at incorrectly parsing a valid URI, and
	 * will correctly parse a wider range of URI's than
	 * parse_url(). This is because of how the URI
	 * specification allows special characters.
	 * 
	 * @param  string $uri The string to be parsed
	 * @return array       The correctly parsed string as an array
	 */
	private function _parse($uri) {
		settype($uri, 'string');
		$regex = (
			'/'.
			'^(([a-z]+)?(\:\/\/|\:|\/\/))?'.              // Scheme, Scheme Name, & Scheme Symbols
			'(?:'.                                        // Auth Start
				'([a-z0-9$_\.\+!\*\'\(\),;&=\-]+)'.         // Username
				'(?:\:([a-z0-9$_\.\+!\*\'\(\),;&=\-]*))?'.  // Password
			'@)?'.                                        // Auth End
			'('.                                          // Host Start
				'(?:\d{3}.\d{3}.\d{3}.\d{3})'.              // IP Address
				'|'.                                        // -OR-
				'(?:[a-z0-9\-_]+(?:\.[a-z0-9\-_]+)*)'.      // Domain Name
			')'.                                          // Host End
			'(?:\:([0-9]+))?'.                            // Port
			'((?:\:|\/)[a-z0-9\-_\/\.]+)?'.               // Path
			'(?:\?([a-z0-9$_\.\+!\*\'\(\),;:@&=\-%]*))?'. // Query
			'(?:#([a-z0-9\-_]*))?'.                       // Fragment
			'/i'
		);
		
		preg_match_all($regex, $uri, $parsed, PREG_SET_ORDER);
		
		// No empty slots please
		$parsed = (
			$parsed[0] +
			array('','','','','','','','','','','')
		);
		
		return array(
			'scheme'         => $parsed[1],
			'scheme_name'    => $parsed[2],
			'scheme_symbols' => $parsed[3],
			'user'           => $parsed[4],
			'pass'           => $parsed[5],
			'host'           => $parsed[6],
			'port'           => $parsed[7],
			'path'           => $parsed[8],
			'query'          => $parsed[9],
			'fragment'       => $parsed[10],
		);
	}
	
	/**
	 * Standard function to re-genrate $authority
	 * 
	 * @return void
	 */
	private function gen_authority() {
		$t = $this;
		$authority = '';
		
		if (!empty($t->user)) {
			$authority .= $t->user;
			if (empty($t->pass)) {
				$authority .= '@';
			} else {
				$authority .= ':';
			}
		}
		if (!empty($t->pass)) {
			$authority .= $t->pass.'@';
		}
		if (!empty($t->host)) {
			$authority .= $t->host;
		}
		if (!empty($t->port)) {
			$authority .= ':'.$t->port;
		}
		$t->authority = $authority;
	}
	
	/**
	 * Returns the current URI as an associative
	 * array similar to parse_url(). However it always
	 * sets each key as an empty string by default.
	 * 
	 * @return array The URI as an array.
	 */
	public function arr() {
		if ($this->error) {
			return FALSE;
		}
		return array(
			'scheme'    => $this->scheme,
			'user'      => $this->user,
			'pass'      => $this->pass,
			'host'      => $this->host,
			'port'      => $this->port,
			'authority' => $this->authority,
			'path'      => $this->path,
			'query'     => $this->query,
			'fragment'  => $this->fragment
		);
	}
	
	/**
	 * Returns the current URI as a string.
	 * 
	 * @return string The current URI.
	 */
	public function str() {
		if ($this->error) {
			return FALSE;
		}
		$t = $this;
		$str = '';
		if (!empty($t->scheme)) {
			$str .= $t->scheme;
		}
		if (!empty($t->user)) {
			$str .= $t->user;
			if (empty($t->pass)) {
				$str .= '@';
			} else {
				$str .= ':';
				$str .= $t->pass.'@';
			}
		}
		if (!empty($t->host)) {
			$str .= $t->host;
		}
		if (!empty($t->port)) {
			$str .= ':'.$t->port;
		}
		if (!empty($t->path)) {
			$str .= $t->path;
		}
		if (!empty($t->query)) {
			$str .= '?'.$t->query;
		}
		if (!empty($t->fragment)) {
			$str .= '#'.$t->fragment;
		}
		return $str;
	}
	
	/**
	 * Prints the current URI.
	 * 
	 * @return void
	 */
	public function p_str() {
		if ($this->error) {
			return FALSE;
		}
		echo $this->str();
	}
	
	/**
	 * Returns an associative array of various
	 * information about the $path.
	 * 
	 * Array Keys:
	 *   dirname, basename, extension, filename, array
	 * 
	 * @return array The $path's information
	 */
	public function path_info() {
		if ($this->error) {
			return FALSE;
		}
		$info = pathinfo($this->path);
		
		$arr = explode('/',$this->path);
		$last = count($arr) - 1;
			
		if ($arr[$last] == '') {
			unset($arr[$last]);
		}
		if ($arr[0] == '') {
			array_shift($arr);
		}
		$info['array'] = $arr;
		
		return $info;
	}
	
	/**
	 * Returns the query string parsed into an array
	 * 
	 * @return array $query as an array
	 */
	public function query_arr() {
		if ($this->error) {
			return FALSE;
		}
		parse_str($this->query, $return);
		return $return;
	}
	
	/**
	 * Appends $str to $section. By default it tries to
	 * autocorrect some errors. Setting $disable_safety
	 * to TRUE or 1 temporarly removes this functionality.
	 * 
	 * @param  string  $section        The section to append to.
	 * @param  string  $str            The string to append.
	 * @param  boolean $disable_safety The safety toggle.
	 * @return string                  The resulting URI.
	 */
	public function append($section, $str, $disable_safety = FALSE) {
		if ($this->error) {
			return FALSE;
		}
		$section = strtolower($section);
		if (!isset($this->$section)) {
			return FALSE;
		}
		if ($disable_safety) {
			$this->$section = $this->$section.$str;
		} else {
			$test = $this->$section.$str;
			$safety = $this->safety($section, $test);
			if ($safety != FALSE) {
				$this->$section = $safety;
			} else {
				return FALSE;
			}
		}
		$this->gen_authority();
		return $this->str();
	}
	
	/**
	 * Prepends $str to $section. By default it tries to
	 * autocorrect some errors. Setting $disable_safety
	 * to TRUE or 1 temporarly removes this functionality.
	 * 
	 * @param  string  $section        The section to prepend to.
	 * @param  string  $str            The string to prepend.
	 * @param  boolean $disable_safety The safety toggle.
	 * @return string                  The resulting URI.
	 */
	public function prepend($section, $str, $disable_safety = FALSE) {
		if ($this->error) {
			return FALSE;
		}
		$section = strtolower($section);
		if (!isset($this->$section)) {
			return FALSE;
		}
		if ($disable_safety) {
			$this->$section = $str.$this->$section;
		} else {
			$test = $str.$this->$section;
			$safety = $this->safety($section, $test);
			if ($safety != FALSE) {
				$this->$section = $safety;
			} else {
				return FALSE;
			}
		}
		$this->gen_authority();
		return $this->str();
	}
	
	/**
	 * Replaces $section with $str. By default it tries
	 * to autocorrect some errors. Setting
	 * $disable_safety to TRUE or 1 temporarly removes
	 * this functionality.
	 * 
	 * @param  string  $section        The section to replace.
	 * @param  string  $str            The string to replace $section with.
	 * @param  boolean $disable_safety The safety toggle.
	 * @return string                  The resulting URI.
	 */
	public function replace($section, $str, $disable_safety = FALSE) {
		if ($this->error) {
			return FALSE;
		}
		$section = strtolower($section);
		if (!isset($this->$section)) {
			return FALSE;
		}
		if ($disable_safety) {
			$this->$section = $str;
		} else {
			$safety = $this->safety($section, $str);
			if ($safety != FALSE) {
				$this->$section = $safety;
			} else {
				return FALSE;
			}
		}
		$this->gen_authority();
		return $this->str();
	}
	
	/**
	 * Attempts to correct any errors in $str based on
	 * what $type is.
	 * 
	 * @param  string $type The type error correction to apply.
	 * @param  string $str  The string to attempt to correct.
	 * @return mixed        The resulting string, or FALSE on failure.
	 */
	protected function safety($type, $str) {
		$type = strtoupper((string) $type);
		if ($type != 'QUERY') {
			$str = trim((string) $str);
		}
		$err = 0;
		switch ($type) {
			case 'SCHEME_NAME':
				if (!preg_match('/\A[a-z]{1,10}\Z/', $str)) {
					$err++;
				}
				break;
			
			case 'SCHEME':
				if (strpos($str, '\\') !== FALSE) {
					$str = str_replace('\\', '/', $str);
				}
				if (strpos($str, '//') === FALSE && stripos($str, ':') === FALSE) {
					if (!empty($str)) {
						$str = $str.'://'; // assume it is generic
					} else {
						break; // there is nothing to check
					}
				}
				
				$str = strtolower($str);
				if (!stripos($str, '://') === FALSE) { // explicit generic
					if (!preg_match('/\A[a-z]{1,10}:\/\/(\/)?\Z/', $str)) {
						$err++;
					}
				} elseif(stripos($str, ':') === FALSE) { // explicit pipe
					if (!preg_match('/\A[a-z]{1,10}:\Z/', $str)) {
						$err++;
					}
				} elseif(stripos($str, '//') === FALSE) { // inherit
					if ($str != '//') {
						$err++;
					}
				}
				break;
			
			case 'USER':
				$str = rawurlencode($str);
				break;
			
			case 'PASS':
				$str = rawurlencode($str);
				break;
			
			case 'HOST':
				$str = strtolower($str);
				if (
					(
						!preg_match('/\A(([a-z0-9_]([a-z0-9\-_]+)?)\.)+[a-z0-9]([a-z0-9\-]+)?\Z/', $str) // fqdn
						&&
						!preg_match('/\A([0-9]\.){3}[0-9]\Z/', $str) // ip
					)
					||
					strlen($str) > 255
				) {
					$err++;
				}
				break;
			
			case 'PORT':
				if ($str[0] == ':') {
					$str = substr($str, 1);
				}
				if (!preg_match('/\A[0-9]{0,5}\Z/', $str)) {
					$err++;
				}
				break;
			
			case 'PATH':
				$str = str_replace(array('//', '\\'), '/', $str); // common mistakes
				$path_arr = explode('/', $str);
				$safe_arr = array();
				foreach ($path_arr as $path_part) {
					$safe_arr[] = rawurlencode($path_part);
				}
				$str = implode('/', $safe_arr);
				break;
			
			case 'QUERY':
				if (is_array($str)) {
					$str = http_build_query($str);
				}
				if ($str[0] == '?') {
					$str = substr($str, 1);
				}
				$frag_loc = strpos($str, '#');
				if ($frag_loc) {
					$str = substr($str, 0, ($frag_loc - 1));
				} elseif ($str[0] == '#') {
					$str = '';
				}
				break;
			
			case 'FRAGMENT':
				if ($str[0] == '#') {
					unset($str[0]);
				}
				$str = urlencode($str);
				break;
			
			
			
			default:
				return FALSE;
				break;
		}
		
		if ($err) {
			return FALSE;
		}
		
		return $str;
	}
	
	/**
	 * Re-initializes the class with the original URI
	 * 
	 * @return void
	 */
	public function reset() {
		$this->__construct($this->input);
	}
}