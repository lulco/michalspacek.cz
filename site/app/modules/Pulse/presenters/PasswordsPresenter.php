<?php
namespace App\PulseModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class PasswordsPresenter extends \App\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\Pulse\Passwords */
	protected $passwords;

	/** @var \MichalSpacekCz\Pulse\Passwords\Rating */
	protected $passwordsRating;


	public function __construct(\MichalSpacekCz\Pulse\Passwords $passwords, \MichalSpacekCz\Pulse\Passwords\Rating $passwordsRating)
	{
		$this->passwords = $passwords;
		$this->passwordsRating = $passwordsRating;
	}


	/**
	 * Storages action handler.
	 *
	 * @param string|null $param
	 */
	public function actionStorages($param = null)
	{
		if ($param === null) {
			$data = $this->passwords->getAllStorages();
			$this->template->isDetail = false;
		} else {
			$data = $this->passwords->getStorages(explode(',', $param));
			$this->template->isDetail = true;
		}
		$this->template->pageTitle = 'Password storage disclosures';
		$this->template->data = $data;
		$this->template->count = count($data->storages);
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
	}


	/**
	 * Storages rating action handler.
	 */
	public function actionStoragesRating()
	{
		$this->template->pageTitle = 'Password storage rating';
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
		$this->template->slowHashes = $this->passwords->getSlowHashes();
		$this->template->visibleDisclosures = $this->passwords->getVisibleDisclosures();
		$this->template->invisibleDisclosures = $this->passwords->getInvisibleDisclosures();
	}


	/**
	 * Storages questions action handler.
	 */
	public function actionStoragesQuestions()
	{
		$this->template->pageTitle = 'Password storage disclosures questions';
	}

}
