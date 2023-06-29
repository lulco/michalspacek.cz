<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Http\IResponse;
use RuntimeException;
use Throwable;

class Info
{

	private Cache $cache;


	public function __construct(
		private readonly Ares $ares,
		private readonly RegisterUz $registerUz,
		Storage $cacheStorage,
		private bool $loadCompanyDataVisible = true,
	) {
		$this->cache = new Cache($cacheStorage, self::class);
	}


	/**
	 * @throws Throwable
	 * @throws CompanyInfoException
	 */
	public function getData(string $country, string $companyId): Data
	{
		$cached = $this->cache->load("{$country}/{$companyId}", function (&$dependencies) use ($country, $companyId) {
			switch ($country) {
				case 'cz':
					$data = $this->ares->getData($companyId);
					break;
				case 'sk':
					$data = $this->registerUz->getData($companyId);
					break;
				default:
					throw new RuntimeException('Unsupported country');
			}
			$dependencies[Cache::Expire] = ($data->status === IResponse::S200_OK ? '3 days' : '15 minutes');
			return $data;
		});
		if (!$cached instanceof Data) {
			throw new CompanyInfoException(sprintf("Cached data for %s/%s is a '%s' not a '%s' object", $country, $companyId, get_debug_type($cached), Data::class));
		}
		return $cached;
	}


	public function setLoadCompanyDataVisible(bool $visible): void
	{
		$this->loadCompanyDataVisible = $visible;
	}


	public function isLoadCompanyDataVisible(): bool
	{
		return $this->loadCompanyDataVisible;
	}

}
