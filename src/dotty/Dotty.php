<?php
/**
 * @author John Smart
 */

namespace dotty;

/**
 * Access arrays quickly using dot-notation.
 */
class Dotty {

	private static $notationMemo	= array();

	private function __construct() {
	}

	/**
	 * Parse a notation string into an array of instructions
	 * @param string $notation
	 * @return array
	 */
	private static function parseNotation($notation) {
		if (isset(self::$notationMemo[$notation])) {
			return self::$notationMemo[$notation];
		}

		$instructions	= array();
		$symbols		= explode('.', $notation);
		foreach ($symbols as $symbol) {
			if (preg_match('/^(.*)\[([\d]+)\]$/', $symbol, $matches)) {
				if (!empty($matches[1])) {
					$instructions[]	= $matches[1]; // symbol
				}
				$instructions[]	= (int)$matches[2]; // offset
			} else {
				$instructions[]	= $symbol;
			}
		}

		return self::$notationMemo[$notation]	= $instructions;
	}

	/**
	 * @throws \InvalidArgumentException
	 *
	 * @param string $notation		Dot notation
	 * @param array& $dataCursor	Data to search through
	 * @return mixed&	A reference to the data
	 */
	public static function &dot($notation, array &$data) {
		$dataCursor		=& $data;
		$instructions	= self::parseNotation($notation);

		$pathSoFar	= array();
		foreach ($instructions as $x) {
			if (is_int($x)) {
				if (count($pathSoFar)) {
					$pathSoFar[count($pathSoFar) - 1] .= "[{$x}]";
				} else {
					$pathSoFar[]	= "[{$x}]";
				}
			} else {
				$pathSoFar[] = $x;
			}

			if (array_key_exists($x, $dataCursor)) {
				$dataCursor =& $dataCursor[$x];
			} else {
				throw new \InvalidArgumentException(sprintf('"%s" does not exist', implode('.', $pathSoFar)));
			}
		}

		return $dataCursor;
	}

	/**
	 * Query an array for a set of data. The last symbol in the notation designates a set
	 *
	 * @static
	 * @param string $notation		Dot notation
	 * @param array& $dataCursor	Data to search through
	 * @return array&
	 */
	public static function set($notation, array &$data) {
		$instructions	= self::parseNotation($notation);

		// the last symbol is the
		$setKey			= array_pop($instructions);

		// get the sub tree
		if (empty($instructions)) {
			return $data[$setKey];
		}

		try {
			$subData		= &self::dot(implode('.', $instructions), $data);
		} catch (\InvalidArgumentException $iae) {
			return array();
		}

		return self::all($setKey, $subData);
	}

	/**
	 * Look through an array of arrays for the first key that matches $ket
	 * @static
	 * @param string $key		The key to match
	 * @param array& $data		The data to search through
	 * @return mixed&
	 */
	public static function &first($key, array &$data) {
		$result = array();
		self::r_first($key, $data, $result);

		if (empty($result)) {
			throw new \InvalidArgumentException(sprintf('"%s" does not exist', $key));
		}

		return $result[0];
	}

	/**
	 * Recursive helper
	 * @static
	 * @param $key
	 * @param $data
	 * @param array $result
	 */
	private static function r_first($key, &$data, array &$result) {
		if (!is_array($data)) {
			// base-case
			return;
		}

		foreach ($data as $subKey => &$dataCursor) {
			if ($subKey === $key) {
				// base-case
				$result[] =& $dataCursor;
				return;
			}

			// induction step
			self::r_first($key, $dataCursor, $result);
			if (!empty($result)) {
				// base-case
				return;
			}
		} unset($dataCursor);
	}

	/**
	 * Look through an array of arrays for all key-matches of $key
	 * @static
	 * @param string $key		The key to match
	 * @param array& $data		The data to search through
	 * @return array&
	 */
	public static function &all($key, array &$data) {
		$results = array();
		self::r_all($key, $data, $results);
		return $results;
	}

	/**
	 * Recursive helper
	 * @static
	 * @param $key
	 * @param $data
	 * @param array $results
	 */
	private static function r_all($key, &$data, array &$results) {
		if (!is_array($data)) {
			// base-case
			return;
		}

		foreach ($data as $subKey => &$dataCursor) {
			if ($subKey === $key) {
				// base-case
				$results[] =& $dataCursor;
			}

			// induction step
			self::r_all($key, $dataCursor, $results);
		} unset($dataCursor);
	}
}