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

use Exception;
use OC\App\AppManager;
use OCA\FullTextSearch\Exceptions\ProviderDoesNotExistException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotCompatibleException;
use OCA\FullTextSearch\Exceptions\ProviderIsNotUniqueException;
use OCA\FullTextSearch\Exceptions\ProviderOptionsDoesNotExistException;
use OCA\FullTextSearch\IFullTextSearchProvider;
use OCA\FullTextSearch\Model\ProviderWrapper;
use OCP\AppFramework\QueryException;

class ProviderService {

	/** @var AppManager */
	private $appManager;

	/** @var ConfigService */
	private $configService;

	/** @var MiscService */
	private $miscService;

	/** @var ProviderWrapper[] */
	private $providers = [];

	/** @var bool */
	private $providersLoaded = false;


	/**
	 * ProviderService constructor.
	 *
	 * @param AppManager $appManager
	 * @param ConfigService $configService
	 * @param MiscService $miscService
	 *
	 */
	public function __construct(
		AppManager $appManager, ConfigService $configService, MiscService $miscService
	) {
		$this->appManager = $appManager;
		$this->configService = $configService;
		$this->miscService = $miscService;
	}


	/**
	 * Load all FullTextSearchProviders set in any info.xml file
	 *
	 * @throws Exception
	 */
	private function loadProviders() {
		if ($this->providersLoaded) {
			return;
		}

		try {
			$apps = $this->appManager->getInstalledApps();
			foreach ($apps as $appId) {
				$this->loadProvidersFromApp($appId);
			}
		} catch (Exception $e) {
			$this->miscService->log($e->getMessage());
		}

		$this->providersLoaded = true;
	}


	/**
	 * @param string $appId
	 * @param string $providerId
	 *
	 * @throws ProviderIsNotCompatibleException
	 * @throws ProviderIsNotUniqueException
	 * @throws QueryException
	 */
	public function loadProvider($appId, $providerId) {

		$provider = \OC::$server->query((string)$providerId);
		if (!($provider instanceof IFullTextSearchProvider)) {
			throw new ProviderIsNotCompatibleException(
				$providerId . ' is not a compatible IFullTextSearchProvider'
			);
		}

		$this->providerIdMustBeUnique($provider);

		try {
			$provider->loadProvider();
			$wrapper = new ProviderWrapper($appId, $provider);
			$wrapper->setVersion($this->configService->getAppVersion($appId));
			$this->providers[] = $wrapper;
		} catch (Exception $e) {
		}
	}


	/**
	 * @return ProviderWrapper[]
	 * @throws Exception
	 */
	public function getProviders() {
		$this->loadProviders();

		return $this->providers;
	}

	/**
	 * @return IFullTextSearchProvider[]
	 * @throws Exception
	 */
	public function getConfiguredProviders() {
		$this->loadProviders();

		$providers = [];
		foreach ($this->providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();
			if ($this->isProviderIndexed($provider->getId())) {
				$providers[] = $provider;
			}
		}

		return $providers;
	}


	/**
	 * @param array $providerList
	 *
	 * @return IFullTextSearchProvider[]
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getFilteredProviders($providerList) {
		$this->loadProviders();

		$providers = $this->getConfiguredProviders();
		if (in_array('all', $providerList)) {
			return $providers;
		}

		$ret = [];
		foreach ($providerList as $providerId) {
			if ($this->isProviderIndexed($providerId)) {
				$providerWrapper = $this->getProvider($providerId);
				$ret[] = $providerWrapper->getProvider();
			}
		}

		return $ret;
	}


	/**
	 * @param string $providerId
	 *
	 * @return ProviderWrapper
	 * @throws Exception
	 * @throws ProviderDoesNotExistException
	 */
	public function getProvider($providerId) {

		$providers = $this->getProviders();
		foreach ($providers as $providerWrapper) {
			$provider = $providerWrapper->getProvider();
			if ($provider->getId() === $providerId) {
				return $providerWrapper;
			}
		}

		throw new ProviderDoesNotExistException('Provider \'' . $providerId . '\' does not exist');
	}


	/**
	 * @param string $providerId
	 *
	 * @return bool
	 */
	public function isProviderIndexed($providerId) {
		try {
			$indexed = $this->configService->getProviderOptions(
				$providerId, ConfigService::PROVIDER_INDEXED
			);
		} catch (ProviderOptionsDoesNotExistException $e) {
			return false;
		}

		if ($indexed === '1') {
			return true;
		}

		return false;

	}


	public function setProviderAsIndexed(IFullTextSearchProvider $provider, $boolean) {
		$this->configService->setProviderOptions(
			$provider->getId(), ConfigService::PROVIDER_INDEXED, (($boolean) ? '1' : '0')
		);
	}


	public function setProvidersAsNotIndexed() {
		$this->configService->resetProviderOptions(ConfigService::PROVIDER_INDEXED);
	}


	/**
	 * @param string $appId
	 *
	 * @throws ProviderIsNotCompatibleException
	 * @throws ProviderIsNotUniqueException
	 * @throws QueryException
	 */
	private function loadProvidersFromApp($appId) {
		$appInfo = $this->appManager->getAppInfo($appId);
		if (!is_array($appInfo) || !key_exists('fulltextsearch', $appInfo)
			|| !key_exists('provider', $appInfo['fulltextsearch'])) {
			return;
		}

		$providers = $appInfo['fulltextsearch']['provider'];
		$this->loadProvidersFromList($appId, $providers);
	}


	/**
	 * @param string $appId
	 * @param string|array $providers
	 *
	 * @throws ProviderIsNotCompatibleException
	 * @throws ProviderIsNotUniqueException
	 * @throws QueryException
	 */
	private function loadProvidersFromList($appId, $providers) {
		if (!is_array($providers)) {
			$providers = [$providers];
		}

		foreach ($providers AS $provider) {
			$this->loadProvider($appId, $provider);
		}
	}


	/**
	 * @param IFullTextSearchProvider $provider
	 *
	 * @throws ProviderIsNotUniqueException
	 * @throws Exception
	 */
	private function providerIdMustBeUnique(IFullTextSearchProvider $provider) {
		foreach ($this->providers AS $providerWrapper) {
			$knownProvider = $providerWrapper->getProvider();
			if ($knownProvider->getId() === $provider->getId()) {
				throw new ProviderIsNotUniqueException(
					'FullTextSearchProvider ' . $provider->getId() . ' already exist'
				);
			}
		}
	}


	/**
	 * @param IFullTextSearchProvider[] $providers
	 *
	 * @return array
	 */
	public function serialize($providers) {
		$arr = [];
		foreach ($providers as $provider) {
			$arr[] = [
				'id'   => $provider->getId(),
				'name' => $provider->getName()
			];
		}

		return $arr;
	}

}
