<?php

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2020
 */

namespace OCA\Music\Utility;

use \OCA\Music\AppFramework\Core\Logger;
use \OCA\Music\Db\Cache;


class Random {
	private $cache;
	private $logger;

	public function __construct(Cache $cache, Logger $logger) {
		$this->cache = $cache;
		$this->logger = $logger;
	}

	/**
	 * Get desired number of random items from the given array
	 *
	 * @param array $itemArray
	 * @param int $count
	 * @return array
	 */
	public static function pickItems($itemArray, $count) {
		$count = \min($count, \count($itemArray)); // can't return more than all items
		$indices = \array_rand($itemArray, $count);
		if ($count == 1) { // return type is not array when randomizing a single index
			$indices = [$indices];
		}

		return Util::arrayMultiGet($itemArray, $indices);
	}

	/**
	 * Get desired number of random array indices. This function supports paging
	 * so that all the indices can be browsed through page-by-page, without getting
	 * the same index more than once. This requires persistence and identifying the
	 * logical array in question. The array is identified by the user ID and a free
	 * text identifier supplied by the caller.
	 * 
	 * For a single logical array, the indices are shuffled every time when the
	 * page 0 is requested. Also, if the size of the array in question has changed
	 * since the previous call, then the indices are reshuffled.
	 *
	 * @param int $arrSize
	 * @param int $offset
	 * @param int $count
	 * @param string $userId
	 * @param string $arrId
	 * @return int[]
	 */
	public function getIndices($arrSize, $offset, $count, $userId, $arrId) {
		$cacheKey = 'random_indices_' . $arrId;

		$indices = self::decodeIndices($this->cache->get($userId, $cacheKey));

		// reshuffle if necessary
		if ($offset == 0 || \count($indices) != $arrSize) {
			if ($arrSize > 0) {
				$indices = \range(0, $arrSize - 1);
			} else {
				$indices = [];
			}
			\shuffle($indices);
			$this->cache->set($userId, $cacheKey, self::encodeIndices($indices));
		}

		return \array_slice($indices, $offset, $count);
	}

	private static function encodeIndices($indices) {
		return \implode(',', $indices);
	}

	private static function decodeIndices($buffer) {
		if (empty($buffer)) {
			return [];
		} else {
			return \explode(',', $buffer);
		}
	}
}
