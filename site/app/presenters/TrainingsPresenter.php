<?php
use MichalSpacekCz\Training;
use Nette\Http\Response;

/**
 * Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Files */
	protected $files;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Mails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Files */
	protected $trainingFiles;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/** @var \Nette\Database\Row */
	private $training;

	/** @var array */
	private $dates;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Files
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Files $trainingFiles
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Vat $vat
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 */
	public function __construct(
		Nette\Localization\ITranslator $translator,
		MichalSpacekCz\Formatter\Texy $texyFormatter,
		MichalSpacekCz\Files $files,
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Dates $trainingDates,
		Training\Files $trainingFiles,
		Training\Trainings $trainings,
		MichalSpacekCz\Vat $vat,
		Netxten\Templating\Helpers $netxtenHelpers
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->files = $files;
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingDates = $trainingDates;
		$this->trainingFiles = $trainingFiles;
		$this->trainings = $trainings;
		$this->vat = $vat;
		$this->netxtenHelpers = $netxtenHelpers;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.trainings');
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
	}


	public function actionTraining($name)
	{
		$session = $this->getSession();
		$session->start();  // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$this->training = $this->trainings->get($name);
		if (!$this->training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}
		$this->dates = $this->trainings->getDates($name);
		if (empty($this->dates)) {
			throw new Nette\Application\BadRequestException("No dates for {$name} training", Response::S503_SERVICE_UNAVAILABLE);
		}

		$this->template->name             = $this->training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.training', [$this->training->name]);
		$this->template->title            = $this->training->name;
		$this->template->description      = $this->training->description;
		$this->template->content          = $this->training->content;
		$this->template->upsell           = $this->training->upsell;
		$this->template->prerequisites    = $this->training->prerequisites;
		$this->template->audience         = $this->training->audience;
		$this->template->originalHref     = $this->training->originalHref;
		$this->template->capacity         = $this->training->capacity;
		$this->template->price            = $this->training->price;
		$this->template->priceVat         = $this->vat->addVat($this->training->price);
		$this->template->studentDiscount  = $this->training->studentDiscount;
		$this->template->materials        = $this->training->materials;
		$this->template->lastFreeSeats    = $this->trainingDates->lastFreeSeatsAnyDate($this->dates);
		$this->template->dates            = $this->dates;

		$this->template->pastTrainingsMe = $this->trainingDates->getPastDates($name);

		$this->template->pastTrainingsJakub = $this->trainingDates->getPastDatesByJakub($name);

		$this->template->reviews = $this->trainings->getReviews($name, 3);
	}


	public function actionApplication($name, $param)
	{
		$training  = $this->trainings->get($name);
		if (!$training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$session = $this->getSession('training');

		$application = $this->trainingApplications->getApplicationByToken($param);
		if (!$application) {
			unset(
				$session->application,
				$session->name,
				$session->email,
				$session->company,
				$session->street,
				$session->city,
				$session->zip,
				$session->country,
				$session->companyId,
				$session->companyTaxId,
				$session->note,
				$session->equipment
			);
			$this->redirect('training', $name);
		}

		$data                 = (array)$session->application;
		$data[$name]          = array('id' => $application->applicationId, 'dateId' => $application->dateId);
		$session->application = $data;

		$session->name         = $application->name;
		$session->email        = $application->email;
		$session->company      = $application->company;
		$session->street       = $application->street;
		$session->city         = $application->city;
		$session->zip          = $application->zip;
		$session->country      = $application->country;
		$session->companyId    = $application->companyId;
		$session->companyTaxId = $application->companyTaxId;
		$session->note         = $application->note;
		$session->equipment    = $application->equipment;

		$this->redirect('training', $application->trainingAction);
	}


	protected function createComponentApplication($formName)
	{
		$form = new MichalSpacekCz\Form\TrainingApplication($this, $formName, $this->dates, $this->translator, $this->netxtenHelpers);
		$form->setApplicationFromSession($this->getSession('training'));
		$form->onSuccess[] = $this->submittedApplication;
	}


	public function submittedApplication(MichalSpacekCz\Form\TrainingApplication $form)
	{
		$session = $this->getSession('training');

		$values = $form->getValues();
		$name   = $form->parent->params['name'];

		try {
			$this->checkSpam($values, $name);
			$this->checkTrainingDate($values, $name);

			$date = $this->dates[$values->trainingId];
			if ($date->tentative) {
				$this->trainingApplications->addInvitation(
					$this->training,
					$values->trainingId,
					$values->name,
					$values->email,
					$values->company,
					$values->street,
					$values->city,
					$values->zip,
					$values->country,
					$values->companyId,
					$values->companyTaxId,
					$values->note,
					$values->equipment
				);
			} else {
				if (isset($session->application[$name]) && $session->application[$name]['dateId'] == $values->trainingId) {
					$applicationId = $this->trainingApplications->updateApplication(
						$session->application[$name]['id'],
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->country,
						$values->companyId,
						$values->companyTaxId,
						$values->note,
						$values->equipment
					);
					$session->application[$name] = null;
				} else {
					$applicationId = $this->trainingApplications->addApplication(
						$this->training,
						$values->trainingId,
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->country,
						$values->companyId,
						$values->companyTaxId,
						$values->note,
						$values->equipment
					);
				}
				$this->trainingMails->sendSignUpMail(
					$applicationId,
					$this->createTemplate(),
					$values->email,
					$values->name,
					$date->start,
					$name,
					$this->training->name,
					$date->venueName,
					$date->venueNameExtended,
					$date->venueAddress,
					$date->venueCity
				);
			}
			$session->trainingId   = $values->trainingId;
			$session->name         = $values->name;
			$session->email        = $values->email;
			$session->company      = $values->company;
			$session->street       = $values->street;
			$session->city         = $values->city;
			$session->zip          = $values->zip;
			$session->country      = $values->country;
			$session->companyId    = $values->companyId;
			$session->companyTaxId = $values->companyTaxId;
			$session->note         = $values->note;
			$session->equipment    = $values->equipment;
			$this->redirect('success', $name);
		} catch (UnexpectedValueException $e) {
			Tracy\Debugger::log($e);
			$this->flashMessage($this->translator->translate('messages.trainings.spammyapplication'), 'error');
		} catch (PDOException $e) {
			Tracy\Debugger::log($e, Tracy\Debugger::ERROR);
			$this->flashMessage($this->translator->translate('messages.trainings.errorapplication'), 'error');
		}
	}


	private function checkTrainingDate(Nette\Utils\ArrayHash $values, $name)
	{
		if (!isset($this->dates[$values->trainingId])) {
			$this->logData($values, $name);
			$message = "Training date id {$values->trainingId} is not an upcoming training, should be one of " . implode(', ', array_keys($this->dates));
			throw new OutOfBoundsException($message);
		}
	}


	private function checkSpam(Nette\Utils\ArrayHash $values, $name)
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note)) {
			$this->logData($values, $name);
			throw new UnexpectedValueException('Spammy note: ' . $values->note);
		}
	}


	private function logData(Nette\Utils\ArrayHash $values, $name)
	{
		$session = $this->getSession('training');
		$logValues = $logSession = array();
		if (isset($session->application[$name])) {
			foreach ($session->application[$name] as $key => $value) {
				$logSession[] = "{$key} => \"{$value}\"";
			}
		}
		foreach ($values as $key => $value) {
			$logValues[] = "{$key} => \"{$value}\"";
		}
		$message = sprintf('Application session data for %s: %s, form values: %s',
			$name,
			(empty($logSession) ? 'empty' : implode(', ', $logSession)),
			implode(', ', $logValues)
		);
		Tracy\Debugger::log($message);
	}


	public function actionReviews($name, $param)
	{
		if ($param !== null) {
			throw new Nette\Application\BadRequestException('No param here, please', Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$this->template->name             = $training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.trainingreviews', [$training->name]);
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->reviews = $this->trainings->getReviews($name);
	}


	public function actionFiles($name, $param)
	{
		$session = $this->getSession('application');

		if ($param !== null) {
			$application = $this->trainingApplications->getApplicationByToken($param);
			$session->token = $param;
			$session->applicationId = ($application ? $application->applicationId : null);
			$this->redirect('files', ($application ? $application->trainingAction : $name));
		}

		if (!$session->applicationId || !$session->token) {
			throw new Nette\Application\BadRequestException("Unknown application id, missing or invalid token", Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->getIncludingCustom($name);
		if (!$training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$application = $this->trainingApplications->getApplicationById($session->applicationId);
		if (!$application) {
			throw new Nette\Application\BadRequestException("No training application for id {$session->applicationId}", Response::S404_NOT_FOUND);
		}

		if ($application->trainingAction != $name) {
			$this->redirect('files', $application->trainingAction);
		}

		$files = $this->trainingFiles->getFiles($application->applicationId);
		$this->trainingApplications->setAccessTokenUsed($application);
		if (!$files) {
			throw new Nette\Application\BadRequestException("No files for application id {$session->applicationId}", Response::S404_NOT_FOUND);
		}
		foreach ($files as $file) {
			$file->info = $this->files->getInfo("{$file->dirName}/{$file->fileName}");
		}

		$this->template->trainingTitle = $training->name;
		$this->template->trainingName = ($training->custom ? null : $training->action);
		$this->template->trainingDate = $application->trainingStart;

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingmaterials', [$training->name]);
		$this->template->files = $files;
	}


	public function actionSuccess($name, $param)
	{
		if ($param !== null) {
			throw new Nette\Application\BadRequestException('No param here, please', Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}
		$this->dates = $this->trainings->getDates($name);
		if (empty($this->dates)) {
			throw new Nette\Application\BadRequestException("No dates for {$name} training", Response::S503_SERVICE_UNAVAILABLE);
		}

		$session = $this->getSession('training');
		if (!isset($session->trainingId)) {
			$this->redirect('training', $name);
		}

		if (!isset($this->dates[$session->trainingId])) {
			$date = $this->trainingDates->get($session->trainingId);
			$this->redirect('success', $date->action);
		}

		$date = $this->dates[$session->trainingId];
		if ($date->tentative) {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.tentative'));
		} else {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.confirmed'));
		}

		$this->template->name             = $training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.trainingapplication', [$training->name]);
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->lastFreeSeats    = false;
		$this->template->start            = $date->start;
		$this->template->venueCity        = $date->venueCity;
		$this->template->tentative        = $date->tentative;

		$upcoming = $this->trainingDates->getPublicUpcoming();
		unset($upcoming[$name]);
		$this->template->upcomingTrainings = $upcoming;

		$this->template->form = $this->createComponentApplication('application');
	}


}
