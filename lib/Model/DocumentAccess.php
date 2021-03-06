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

namespace OCA\FullTextSearch\Model;


class DocumentAccess implements \JsonSerializable {

	/** @var string */
	private $ownerId;

	/** @var string */
	private $viewerId;

	/** @var array */
	private $users = [];

	/** @var array */
	private $groups = [];

	/** @var array */
	private $circles = [];

	/** @var array */
	private $links = [];

	/**
	 * DocumentAccess constructor.
	 *
	 * @param string $ownerId
	 */
	public function __construct($ownerId = '') {
		$this->setOwnerId($ownerId);
	}


	/**
	 * @param $ownerId
	 *
	 * @return $this
	 */
	public function setOwnerId($ownerId) {
		if ($ownerId === false) {
			$ownerId = '';
		}
		$this->ownerId = $ownerId;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getOwnerId() {
		return $this->ownerId;
	}


	/**
	 * @param string $viewerId
	 */
	public function setViewerId($viewerId) {
		$this->viewerId = $viewerId;
	}

	/**
	 * @return string
	 */
	public function getViewerId() {
		return $this->viewerId;
	}


	/**
	 * @param array $users
	 */
	public function setUsers($users) {
		$this->users = $users;
	}

	/**
	 * @return array
	 */
	public function getUsers() {
		return $this->users;
	}

	/**
	 * @param array $users
	 */
	public function addUsers($users) {
		$this->users = array_merge($this->users, $users);
	}


	/**
	 * @param array $groups
	 */
	public function setGroups($groups) {
		$this->groups = $groups;
	}

	/**
	 * @return array
	 */
	public function getGroups() {
		return $this->groups;
	}


	/**
	 * @param array $groups
	 */
	public function addGroups($groups) {
		$this->groups = array_merge($this->groups, $groups);
	}


	/**
	 * @param array $circles
	 */
	public function setCircles($circles) {
		$this->circles = $circles;
	}

	/**
	 * @return array
	 */
	public function getCircles() {
		return $this->circles;
	}

	/**
	 * @param array $circles
	 */
	public function addCircles($circles) {
		$this->circles = array_merge($this->circles, $circles);
	}

	/**
	 * @param array $links
	 */
	public function setLinks($links) {
		$this->links = $links;
	}

	/**
	 * @return array
	 */
	public function getLinks() {
		return $this->links;
	}


	/**
	 *
	 */
	public function jsonSerialize() {
		return [
			'ownerId'  => $this->getOwnerId(),
			'viewerId' => $this->getViewerId(),
			'users'    => $this->getUsers(),
			'groups'   => $this->getGroups(),
			'circles'  => $this->getCircles(),
			'links'    => $this->getLinks()
		];
	}
}