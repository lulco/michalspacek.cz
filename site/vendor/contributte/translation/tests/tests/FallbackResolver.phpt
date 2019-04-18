<?php

/**
 * This file is part of the Contributte/Translation
 */

declare(strict_types=1);

namespace Contributte\Translation\Tests\Tests;

use Contributte;
use Tester;

$container = require __DIR__ . '/../bootstrap.php';


/**
 * @author Ales Wita
 */
class FallbackResolver extends Contributte\Translation\Tests\AbstractTest
{
	public function test01(): void
	{
		Tester\Assert::same(['cs_CZ'], $this->compute('cs', ['cs', 'cs_CZ']));
		Tester\Assert::same(['cs', 'cs_CZ'], $this->compute('sk', ['cs', 'cs_CZ']));
		Tester\Assert::same(['cs', 'en'], $this->compute('cs_CZ', ['cs', 'cs_CZ', 'en']));
		Tester\Assert::same(['en', 'cs', 'cs_CZ'], $this->compute('en_US', ['cs', 'cs_CZ', 'en', 'en_US']));
		Tester\Assert::same(['cs', 'cs_CZ', 'en', 'en_US'], $this->compute('sk', ['cs', 'cs_CZ', 'en', 'en_US']));
	}


	/**
	 * @internal
	 *
	 * @param string $locale
	 * @param array $fallbackLocales
	 * @return array
	 */
	private function compute(?string $locale, array $fallbackLocales): array
	{
		$translatorMock = \Mockery::mock(Contributte\Translation\Translator::class);

		$translatorMock->shouldReceive('getAvailableLocales')
			->once()
			->withNoArgs()
			->andReturn($fallbackLocales);

		$resolver = new Contributte\Translation\FallbackResolver;

		$resolver->setFallbackLocales($fallbackLocales);

		return $resolver->compute($translatorMock, $locale);
	}
}


(new FallbackResolver($container))->run();
