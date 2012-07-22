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

	/**
	 * did the last query meet a result?
	 * @var bool
	 */
	private $hasLast = false;

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
	 * did the last query meet a result?
	 * @return bool
	 */
	public function hasResult() {
		return $this->hasLast;
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
	 * Ensure that a path exists, setting it with null if it does not.
	 * @throws \InvalidArgumentException		Thrown when unable to ensure a path due if dead-end value exists along path
	 *
	 * @param string $notation		Dot notation
	 * @return Dotty
	 */
	public function ensure($notation) {
		$this->lastResult = null;
		$this->hasLast = false;

		$dataCursor		=& $this->data;
		if (empty($notation)) {
			$this->lastResult =& $dataCursor;
			$this->hasLast = true;
			return $this;
		}

		$instructions	= $this->parseNotation($notation);
		for ($i = 0; $i < count($instructions); ++$i) {
			$isLast = ($i + 1 === count($instructions));
			if (!is_array($dataCursor)) {
				if (is_null($dataCursor)) {
					$dataCursor = array();
				} else {
					throw new \InvalidArgumentException('unable to ensure path that contains a non-array');
				}
			}

			$x	= $instructions[$i];
			if (!array_key_exists($x, $dataCursor)) {
				if ($isLast) {
					$dataCursor[$x] = null;
				} else {
					$dataCursor[$x] = array();
				}
			}

			$dataCursor =& $dataCursor[$x];
		}

		$this->lastResult =& $dataCursor;
		$this->hasLast = true;
		return $this;
	}

	/**
	 * @throws \InvalidArgumentException
	 *
	 * @param string $notation		Dot notation
	 * @param boolean $require		Optional. Set true to throw an exception if $notation is not found.
	 * @return Dotty
	 */
	public function one($notation, $require = false) {
		$this->lastResult = null;
		$this->hasLast = false;

		$dataCursor		=& $this->data;
		if (empty($notation)) {
			$this->lastResult =& $dataCursor;
			$this->hasLast = true;
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
			} else if ($require) {
				throw new \InvalidArgumentException(sprintf('"%s" does not exist', implode('.', $pathSoFar)));
			} else {
				return $this;
			}
		}

		$this->lastResult =& $dataCursor;
		$this->hasLast = true;
		return $this;
	}

	/**
	 * Look through an array of arrays for the first key that matches $ket
	 *
	 * @param string $key			The key to match
	 * @param boolean $require		Optional. Set true to throw an exception if $notation is not found.
	 * @return Dotty
	 * @throws \InvalidArgumentException
	 */
	public function first($key, $require = false) {
		$this->lastResult = null;
		$this->hasLast = false;

		$result = array();
		self::r_first($key, $this->data, $result);

		if (empty($result)) {
			if ($require) {
				throw new \InvalidArgumentException(sprintf('"%s" does not exist', $key));
			} else {
				return $this;
			}
		}

		$this->lastResult =& $result[0];
		$this->hasLast = true;
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
		$this->lastResult = null;
		$this->hasLast = false;

		$results = array();
		self::r_all($key, $this->data, $results);
		$this->lastResult =& $results;
		$this->hasLast = !empty($results);
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