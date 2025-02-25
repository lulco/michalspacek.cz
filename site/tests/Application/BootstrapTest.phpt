<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;
use Tracy\ILogger;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class BootstrapTest extends TestCase
{

	private string $exceptionLog;
	private ?string $tempLog = null;


	public function __construct()
	{
		$this->exceptionLog = Debugger::$logDirectory . '/' . ILogger::EXCEPTION . '.log';
		if (file_exists($this->exceptionLog)) {
			$this->tempLog = $this->exceptionLog . '.' . uniqid(more_entropy: true);
			rename($this->exceptionLog, $this->tempLog);
		}
		ServerEnv::setString('SERVER_NAME', 'michalspacek.cz');
	}


	public function __destruct()
	{
		if (file_exists($this->exceptionLog)) {
			echo file_get_contents($this->exceptionLog);
			unlink($this->exceptionLog);
		}
		if ($this->tempLog && file_exists($this->tempLog)) {
			rename($this->tempLog, $this->exceptionLog);
		}
	}


	/**
	 * @return array<string, array{environment:string|null}>
	 */
	public function getBootEnvironments(): array
	{
		return [
			'production' => [
				'environment' => null,
			],
			'development' => [
				'environment' => 'development',
			],
		];
	}


	/** @dataProvider getBootEnvironments */
	public function testBoot(?string $environment): void
	{
		if ($environment === null) {
			ServerEnv::unset('ENVIRONMENT');
		} else {
			ServerEnv::setString('ENVIRONMENT', $environment);
		}
		Assert::noError(function () use (&$container): void {
			$container = Bootstrap::boot();
		});
		Assert::type(Container::class, $container);
	}

}

$runner->run(BootstrapTest::class);
