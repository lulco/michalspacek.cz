<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */

class FiltersTest extends TestCase
{

	public function __construct(
		private readonly Filters $filters,
	) {
	}


	public function testFormat(): void
	{
		Assert::same('<em>foo</em> bar 303', $this->filters->format('*foo* %s %d', 'bar', 303)->render());
	}

}

$runner->run(FiltersTest::class);
