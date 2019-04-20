<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Training\Files;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\FileResponse;
use Nette\Http\IResponse;

/**
 * Files presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class FilesPresenter extends BasePresenter
{

	/** @var Files */
	protected $trainingFiles;


	/**
	 * @param Files $trainingFiles
	 */
	public function __construct(Files $trainingFiles)
	{
		$this->trainingFiles = $trainingFiles;
		parent::__construct();
	}


	/**
	 * @param string $filename
	 * @throws BadRequestException
	 * @throws AbortException
	 */
	public function actionTraining(string $filename): void
	{
		$session = $this->getSession('application');
		if (!$session->applicationId) {
			throw new BadRequestException("Unknown application id, missing or invalid token", IResponse::S404_NOT_FOUND);
		}

		$file = $this->trainingFiles->getFile($session->applicationId, $session->token, $filename);
		if (!$file) {
			throw new BadRequestException("No file {$filename} for application id {$session->applicationId}", IResponse::S404_NOT_FOUND);
		}
		$pathname = $file->info->getPathname();
		$this->sendResponse(new FileResponse($pathname, null, finfo_file(finfo_open(FILEINFO_MIME_TYPE), $pathname)));
	}


	/**
	 * @param string $filename
	 * @throws BadRequestException
	 */
	public function actionFile(string $filename): void
	{
		throw new BadRequestException("Cannot download {$filename}", IResponse::S404_NOT_FOUND);
	}

}
