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

	/**
	 * @var mixed&
	 */
	private $data	= null;

	/**
	 * The last result
	 * @var mixed
	 */
	private $lastResult	= null;

	private function __construct(&$data) {
		$this->data	=& $data;
	}

	/**
	 * Set the data to parse in a chain
	 *
	 * @static
	 * @param array $data
	 * @return Dotty
	 */
	public static function with(array &$data) {
		return new Dotty($data);
	}

	/**
	 * Access the result
	 * @return mixed&
	 */
	public function &result() {
		return $this->lastResult;
	}

	/**
	 * Parse a notation string into an array of instructions
	 * @param string $notation
	 * @return array
	 */
	private function parseNotation($notation) {
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

		return self::$notationMemo[$notation] = $instructions;
	}

	/**
	 * @throws \InvalidArgumentException
	 *
	 * @param string $notation		Dot notation
	 * @return Dotty
	 */
	public function one($notation) {
		$dataCursor		=& $this->data;
		if (empty($notation)) {
			$this->lastResult =& $dataCursor;
			return $this;
		}

		$instructions	= $this->parseNotation($notation);

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

		$this->lastResult =& $dataCursor;
		return $this;
	}

	/**
	 * Query an array for a set of data. The last symbol in the notation designates a set
	 *
	 * @param string $notation		Dot notation
	 * @return Dotty
	 */
	public function set($notation) {
		$instructions	= $this->parseNotation($notation);

		// the last symbol is the
		$setKey			= array_pop($instructions);

		$this->one(implode('.', $instructions))->all($setKey);
		return $this;
	}

	/**
	 * Look through an array of arrays for the first key that matches $ket
	 *
	 * @param string $key		The key to match
	 * @return Dotty
	 * @throws \InvalidArgumentException
	 */
	public function first($key) {
		$result = array();
		self::r_first($key, $this->data, $result);

		if (empty($result)) {
			throw new \InvalidArgumentException(sprintf('"%s" does not exist', $key));
		}

		$this->lastResult =& $result[0];
		return $this;
	}

	/**
	 * Recursive helper
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
	 * @param string $key		The key to match
	 * @return Dotty
	 */
	public function all($key) {
		$results = array();
		self::r_all($key, $this->data, $results);
		$this->lastResult =& $results;
		return $this;
	}

	/**
	 * Recursive helper
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