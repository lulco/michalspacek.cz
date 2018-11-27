<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Nette\Http\IResponse;

/**
 * ARES service.
 *
 * See https://wwwinfo.mfcr.cz/ares/xml_doc/schemas/documentation/zkr_103.txt
 * for meaning of abbreviations like AA, NU, CD, CO etc. (in Czech)
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Ares implements CompanyDataInterface
{

	/**
	 * See kod_vyhledani in https://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_datatypes/v_1.0.5/ares_datatypes_v_1.0.5.xsd
	 */
	private const STATUS_FOUND = 1;

	/** @var string */
	private $url;

	/** @var string */
	private $proxyUrl;

	/** @var boolean */
	private $useProxy = false;

	/** @var \MichalSpacekCz\KeyCdn */
	protected $keyCdn;


	/**
	 * @param \MichalSpacekCz\KeyCdn $keyCdn
	 */
	public function __construct(\MichalSpacekCz\KeyCdn $keyCdn)
	{
		$this->keyCdn = $keyCdn;
	}


	/**
	 * @param string $url
	 */
	public function setUrl(string $url): void
	{
		$this->url = $url;
	}


	/**
	 * @param string $url
	 */
	public function setProxyUrl(string $url): void
	{
		$this->proxyUrl = $url;
	}


	/**
	 * @param boolean $useProxy
	 */
	public function setUseProxy(bool $useProxy): void
	{
		$this->useProxy = $useProxy;
	}


	/**
	 * @param string $companyId
	 * @return Data
	 */
	public function getData(string $companyId): Data
	{
		$company = new Data();
		try {
			if (empty($companyId)) {
				throw new \RuntimeException('Company Id is empty');
			}
			$content = $this->fetch($companyId);
			libxml_disable_entity_loader();
			$xml = simplexml_load_string($content);
			$ns = $xml->getDocNamespaces();
			$result = $xml->children($ns['are'])->children($ns['D'])->VH;
			$data = $xml->children($ns['are'])->children($ns['D'])->VBAS;
			if ((int)$result->K !== self::STATUS_FOUND) {
				throw new \UnexpectedValueException('Invalid status', (int)$result->K);
			}

			if (isset($data->AA)) {
				$street = (string)$data->AA->NU;
				$houseNumber = (string)$data->AA->CD;
				$streetNumber = (string)$data->AA->CO;
				$city = (string)$data->AA->N;
				$zip = (string)$data->AA->PSC;
				$country = strtolower($this->countryCode($data));
			} else {
				$street = $houseNumber = $streetNumber = $city = $zip = $country = null;
			}

			$company->status = IResponse::S200_OK;
			$company->statusMessage = 'OK';
			$company->companyId = (string)$data->ICO;
			$company->companyTaxId = (string)$data->DIC;
			$company->company = (string)$data->OF;
			$company->street = $street;
			$company->houseNumber = $houseNumber;
			$company->streetNumber = $streetNumber;
			$company->streetFull = $this->formatStreet($city, $street, $houseNumber, $streetNumber);
			$company->city = $city;
			$company->zip = $zip;
			$company->country = $country;
		} catch (\UnexpectedValueException $e) {
			\Tracy\Debugger::log(get_class($e) . ": {$e->getMessage()}, code: {$e->getCode()}, company id: {$companyId}");
			$company->status = IResponse::S400_BAD_REQUEST;
			$company->statusMessage = 'Not Found';
		} catch (\RuntimeException $e) {
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		} catch (\Exception $e) {
			\Tracy\Debugger::log($e);
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		}

		return $company;
	}


	/**
	 * Fetch data from API
	 *
	 * @param string $companyId
	 * @return string
	 */
	private function fetch(string $companyId): string
	{
		$context = stream_context_create();
		stream_context_set_params($context, [
			'notification' => function ($notificationCode, $severity, $message, $messageCode) {
				if ($severity === STREAM_NOTIFY_SEVERITY_ERR) {
					throw new \RuntimeException(trim($message) . " ({$notificationCode})", $messageCode);
				}
			},
			'options' => [
				'http' => ['ignore_errors' => true],  // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			],
		]);
		$url = ($this->useProxy ? $this->keyCdn->signUrl(sprintf($this->proxyUrl, $companyId)) : sprintf($this->url, $companyId));
		return file_get_contents($url, false, $context);
	}


	/**
	 * @param string $city
	 * @param string $street
	 * @param string $houseNumber
	 * @param string $streetNumber
	 * @return string
	 */
	private function formatStreet(string $city, string $street, string $houseNumber, string $streetNumber): string
	{
		$result = $street;
		if (empty($result)) {
			$result = $city;
		}
		if (!empty($houseNumber) && !empty($streetNumber)) {
			$result .= " {$houseNumber}/{$streetNumber}";
		} elseif (!empty($houseNumber)) {
			$result .= " {$houseNumber}";
		} elseif (!empty($streetNumber)) {
			$result .= " {$streetNumber}";
		}
		return $result;
	}


	/**
	 * Return ISO 3166-1 alpha-2 by ISO 3166-1 numeric.
	 *
	 * @param \SimpleXMLElement $data
	 * @return string ISO 3166-1 alpha-2 code
	 */
	private function countryCode(\SimpleXMLElement $data): string
	{
		$codes = array(
			'203' => 'CZ',
			'703' => 'SK',
		);
		return ($codes[(string)$data->AA->KS] ?? substr((string)$data->DIC, 0, 2) ?: 'CZ');
	}

}
