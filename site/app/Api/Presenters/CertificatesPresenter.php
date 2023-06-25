<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Tls\CertificateAttemptFactory;
use MichalSpacekCz\Tls\CertificateFactory;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Tls\Exceptions\SomeCertificatesLoggedToFileException;
use MichalSpacekCz\Www\Presenters\BasePresenter;
use Nette\Security\AuthenticationException;

class CertificatesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Certificates $certificates,
		private readonly CertificateFactory $certificateFactory,
		private readonly CertificateAttemptFactory $certificateAttemptFactory,
		private readonly HttpInput $httpInput,
	) {
		parent::__construct();
	}


	protected function startup(): void
	{
		parent::startup();
		try {
			$this->certificates->authenticate($this->httpInput->getPostString('user') ?? '', $this->httpInput->getPostString('key') ?? '');
		} catch (AuthenticationException) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Invalid credentials']);
		}
	}


	public function actionDefault(): never
	{
		$this->sendJson(['status' => 'ok', 'certificates' => $this->certificates->getNewest()]);
	}


	public function actionLogIssued(): void
	{
		$certs = $this->certificateFactory->listFromLogRequest($this->httpInput->getPostArray('certs') ?? []);
		$failures = $this->certificateAttemptFactory->listFromLogRequest($this->httpInput->getPostArray('failure') ?? []);
		try {
			$count = $this->certificates->log($certs, $failures);
			$this->sendJson([
				'status' => 'ok',
				'statusMessage' => 'Certificates reported successfully',
				'count' => $count,
			]);
		} catch (SomeCertificatesLoggedToFileException) {
			$this->sendJson(['status' => 'error', 'statusMessage' => 'Some certs logged to file']);
		}
	}

}
