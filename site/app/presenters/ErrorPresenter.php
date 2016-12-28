<?php
namespace App\Presenters;

use \Nette\Http\IResponse;

/**
 * Error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Redirections */
	protected $redirections;

	/** @var array */
	protected $statuses = [
		IResponse::S400_BAD_REQUEST,
		IResponse::S403_FORBIDDEN,
		IResponse::S404_NOT_FOUND,
		IResponse::S405_METHOD_NOT_ALLOWED,
		IResponse::S410_GONE,
	];


	/**
	 * @param \MichalSpacekCz\Redirections $translator
	 */
	public function __construct(\MichalSpacekCz\Redirections $redirections)
	{
		$this->redirections = $redirections;
	}


	public function startup()
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(\Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}


	/**
	 * @param \Nette\Application\BadRequestException $exception
	 */
	public function actionDefault(\Nette\Application\BadRequestException $exception)
	{
		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, IResponse::S301_MOVED_PERMANENTLY);
		}

		$code = (in_array($exception->getCode(), $this->statuses) ? $exception->getCode() : IResponse::S400_BAD_REQUEST);
		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note =  $this->translator->translate("messages.error.{$code}");
	}


}
