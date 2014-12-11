<?php
namespace MichalSpacekCz\Application;

// Load Nette Framework or autoloader generated by Composer
require __DIR__ . '/../../../vendor/autoload.php';

/**
 * The michalspacek.cz bootstrap class.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Bootstrap extends \Nette\Object
{

	/** @var string */
	private $appDir;

	/** @var string */
	private $logDir;

	/** @var string */
	private $tempDir;


	public function __construct($appDir, $logDir, $tempDir)
	{
		$this->appDir = $appDir;
		$this->logDir = $logDir;
		$this->tempDir = $tempDir;
	}


	public function run()
	{
		$configurator = new \Nette\Config\Configurator;

		$environment = (isset($_SERVER['ENVIRONMENT']) ? $_SERVER['ENVIRONMENT'] : 'production');

		// Enable Nette Debugger for error visualisation & logging
		$configurator->setDebugMode($environment == 'development');
		$configurator->enableDebugger($this->logDir);

		// Enable RobotLoader - this will load all classes automatically
		$configurator->setTempDirectory($this->tempDir);
		$configurator->createRobotLoader()
			->addDirectory($this->appDir)
			->register();

		// Root domain
		$rootDomain = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
		if (preg_match('/([^.]+\.[^.:]+)(?::[0-9]+)?$/', $rootDomain, $matches)) {
			$rootDomain = $matches[1];
		}

		// Create Dependency Injection container from config files
		$configFiles = array(
			$this->appDir . '/config/config.neon',
			$this->appDir . '/config/parameters.neon',
			$this->appDir . '/config/presenters.neon',
			$this->appDir . '/config/services.neon',
			$this->appDir . "/config/config.extra-{$rootDomain}.neon",
			$this->appDir . '/config/config.local.neon',
		);
		foreach (array_filter($configFiles, 'is_file') as $filename) {
			$configurator->addConfig($filename, $configurator::NONE);
		}
		$container = $configurator->createContainer();
		$container->application->run();
	}

}
