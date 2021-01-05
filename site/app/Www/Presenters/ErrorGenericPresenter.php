<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Application\Error;
use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\Request;

class ErrorGenericPresenter implements IPresenter
{

	/** @var Error */
	private $error;


	public function __construct(Error $error)
	{
		$this->error = $error;
	}


	public function run(Request $request): Response
	{
		return $this->error->response($request);
	}

}
