<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Training\Exceptions\TrainingStatusIdNotIntException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class StatusesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly Statuses $trainingStatuses,
	) {
	}


	protected function tearDown(): void
	{
		$this->database->reset();
		PrivateProperty::setValue($this->trainingStatuses, 'statusIds', []);
	}


	public function testGetStatusId(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::same(303, $this->trainingStatuses->getStatusId(Statuses::STATUS_SIGNED_UP));

		$this->database->setFetchFieldDefaultResult('nah, it cached');
		Assert::same(303, $this->trainingStatuses->getStatusId(Statuses::STATUS_SIGNED_UP));
	}


	public function testGetStatusIdNotInt(): void
	{
		$this->database->setFetchFieldDefaultResult('donut');
		Assert::exception(function (): void {
			$this->trainingStatuses->getStatusId(Statuses::STATUS_SIGNED_UP);
		}, TrainingStatusIdNotIntException::class, "Training status 'SIGNED_UP' id is a string not an integer");
	}

}

$runner->run(StatusesTest::class);
