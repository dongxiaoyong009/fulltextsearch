<?php
/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FullTextSearch\Service;

use OCA\FullTextSearch\AppInfo\Application;
use OCP\ILogger;
use OCP\Util;

class MiscService {

	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	public function log($message, $level = 2) {
		$data = array(
			'app'   => Application::APP_NAME,
			'level' => $level
		);

		$this->logger->log($level, $message, $data);
	}

	/**
	 * @param $arr
	 * @param $k
	 *
	 * @param string $default
	 *
	 * @return array|string|integer
	 */
	public static function get($k, $arr, $default = '') {
		if ($arr === null) {
			return $default;
		}

		if (!key_exists($k, $arr)) {
			return $default;
		}

		return $arr[$k];
	}


	public static function noEndSlash($path) {
		if (substr($path, -1) === '/') {
			$path = substr($path, 0, -1);
		}

		return $path;
	}


	/**
	 * @param string $time
	 *
	 * @return float
	 */
	public static function getMicroTime($time) {
		list($usec, $sec) = explode(' ', $time);

		return ((float)$usec + (float)$sec);
	}


	public function addJavascript() {
		Util::addStyle(Application::APP_NAME, 'fulltextsearch');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.api');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.settings');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.searchbox');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.result');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1.navigation');
		Util::addScript(Application::APP_NAME, 'fulltextsearch.v1');
	}


}

