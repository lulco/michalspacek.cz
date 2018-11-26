<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Nette\Caching\Cache;

/**
 * Company info service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Info
{

	/** @var Ares */
	private $ares;

	/** @var RegisterUz */
	private $registerUz;

	/** @var \Nette\Caching\Cache */
	private $cache;

	/** @var boolean */
	private $loadCompanyDataVisible = true;


	/**
	 * @param Ares $ares
	 * @param RegisterUz $registerUz
	 * @param \Nette\Caching\IStorage $cacheStorage
	 */
	public function __construct(Ares $ares, RegisterUz $registerUz, \Nette\Caching\IStorage $cacheStorage)
	{
		$this->ares = $ares;
		$this->registerUz = $registerUz;
		$this->cache = new Cache($cacheStorage, self::class);
	}


	/**
	 * @param string $country
	 * @param string $companyId
	 * @return Data
	 */
	public function getData(string $country, string $companyId): Data
	{
		return $this->cache->load("{$country}/{$companyId}", function(&$dependencies) use ($country, $companyId) {
			switch ($country) {
				case 'cz':
					$data = $this->ares->getData($companyId);
					break;
				case 'sk':
					$data = $this->registerUz->getData($companyId);
					break;
				default:
					throw new \RuntimeException('Unsupported country');
					break;
			}
			$dependencies[Cache::EXPIRATION] = ($data->status === \Nette\Http\IResponse::S200_OK ? '3 days' : '15 minutes');
			return $data;
		});
	}


	/**
	 * @param boolean $visible
	 */
	public function setLoadCompanyDataVisible(bool $visible): void
	{
		$this->loadCompanyDataVisible = $visible;
	}


	/**
	 * @return boolean
	 */
	public function isLoadCompanyDataVisible(): bool
	{
		return $this->loadCompanyDataVisible;
	}

}
