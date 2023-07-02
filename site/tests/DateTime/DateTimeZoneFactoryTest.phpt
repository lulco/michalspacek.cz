<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime;

use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class DateTimeZoneFactoryTest extends TestCase
{

	public function __construct(
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
	) {
	}


	public function testGet(): void
	{
		$known = 'Europe/Prague';
		$tz = $this->dateTimeZoneFactory->get($known);
		Assert::same($known, $tz->getName());

		$unknown = 'Europe/Brno'; // 😜
		Assert::exception(function () use ($unknown): void {
			$this->dateTimeZoneFactory->get($unknown);
		}, InvalidTimezoneException::class, "Invalid timezone 'Europe/Brno'");
	}

}

$runner->run(DateTimeZoneFactoryTest::class);
