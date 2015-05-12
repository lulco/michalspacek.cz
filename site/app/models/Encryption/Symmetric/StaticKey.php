<?php
namespace MichalSpacekCz\Encryption\Symmetric;

/**
 * StaticKey encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class StaticKey extends \Nette\Object
{

	const KEY_IV_CIPHERTEXT_SEPARATOR = '$';

	/** @var string[] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;

	/** @var \MichalSpacekCz\Encryption */
	protected $encryption;


	public function __construct(\MichalSpacekCz\Encryption\Encryption $encryption)
	{
		$this->encryption = $encryption;
	}


	public function setKeys($keys)
	{
		$this->keys = $keys;
	}


	public function setActiveKeyIds($activeKeyIds)
	{
		$this->activeKeyIds = $activeKeyIds;
	}


	/**
	 * Encrypt data.
	 *
	 * It's safe to throw exceptions here as the stack trace will not contain the key,
	 * because the key is not passed as a parameter to the function.
	 *
	 * @param string $data The plaintext
	 * @param string $group The group from which to read the key
	 * @return string
	 */
	public function encrypt($data, $group)
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);

		try {
			$cipherText = \Crypto::Encrypt($data, $key);
		} catch (\CryptoTestFailedException $e) {
			throw new \RuntimeException('Crypto test failed because: ' . $e->getMessage());
		} catch (\CannotPerformOperationException $e) {
			throw new \RuntimeException('Cannot encrypt because: ' . $e->getMessage());
		}

		return $this->formatKeyCipherText($keyId, $cipherText);
	}


	public function decrypt($data, $group)
	{
		list($cipher, $keyId, $iv, $cipherText) = $this->parseKeyIvCipherText($data);
		$key = $this->getKey($group, $keyId);
		return $this->encryption->decrypt($cipherText, $key, $cipher, $iv);
	}


	private function getKey($group, $keyId)
	{
		if (isset($this->keys[$group][$keyId])) {
			return $this->keys[$group][$keyId];
		} else {
			throw new \OutOfRangeException('Unknown encryption key id: ' . $keyId);
		}
	}


	private function getActiveKeyId($group)
	{
		return $this->activeKeyIds[$group];
	}


	private function parseKeyIvCipherText($data)
	{
		$data = explode(self::KEY_IV_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 5) {
			throw new \OutOfBoundsException('Data must have cipher, key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array($data[1], base64_decode($data[2]), base64_decode($data[3]), base64_decode($data[4]));
	}


	private function formatKeyCipherText($keyId, $cipherText)
	{
		$data = array(
			base64_encode($keyId),
			base64_encode($cipherText),
		);
		return self::KEY_IV_CIPHERTEXT_SEPARATOR . implode(self::KEY_IV_CIPHERTEXT_SEPARATOR, $data);
	}

}
