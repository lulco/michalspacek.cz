<?php

/**
 * This file is part of the Contributte/Translation
 */

declare(strict_types=1);

namespace Contributte\Translation\LocalesResolvers;

use Contributte;
use Nette;


/**
 * @author Ales Wita
 * @author Filip Prochazka
 */
class Header implements ResolverInterface
{
	use Nette\SmartObject;

	/** @var Nette\Http\Request */
	private $httpRequest;


	/**
	 * @param Nette\Http\IRequest $httpRequest
	 * @throws Contributte\Translation\InvalidArgumentException
	 */
	public function __construct(Nette\Http\IRequest $httpRequest)
	{
		if (!is_a($httpRequest, Nette\Http\Request::class, true)) {
			throw new Contributte\Translation\InvalidArgumentException('Header locale resolver need "Nette\\Http\\Request" or his child for using "detectLanguage" method.');
		}

		$this->httpRequest = $httpRequest;
	}


	/**
	 * @param Contributte\Translation\Translator $translator
	 * @return string|null
	 */
	public function resolve(Contributte\Translation\Translator $translator): ?string
	{
		/** @var string[] $langs */
		$langs = [];

		foreach ($translator->availableLocales as $v1) {
			$langs[] = $v1;

			if (Nette\Utils\Strings::length($v1) > 2) {
				$langs[] = Nette\Utils\Strings::substring($v1, 0, 2);// en_US => en
			}
		}

		if (count($langs) === 0) {
			return null;
		}

		return $this->httpRequest->detectLanguage($langs);
	}
}
