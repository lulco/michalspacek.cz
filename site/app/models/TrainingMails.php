<?php
namespace MichalSpacekCz;

/**
 * Training mails model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingMails extends BaseModel
{

	/**
	 * @var \MichalSpacekCz\TrainingApplications
	 */
	protected $trainingApplications;

	/**
	 * @var \MichalSpacekCz\TrainingDates
	 */
	protected $trainingDates;

	/**
	 * Templates directory, ends with a slash.
	 *
	 * @var string
	 */
	protected $templatesDir;


	public function __construct(\Nette\Database\Connection $connection, \MichalSpacekCz\TrainingApplications $trainingApplications, \MichalSpacekCz\TrainingDates $trainingDates)
	{
		$this->database = $connection;
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
	}

	public function sendSignUpMail($applicationId, $template, $recipientAddress, $recipientName, $start, $training, $trainingName, $venueName, $venueNameExtended, $venueAddress, $venueCity)
	{
		\Nette\Diagnostics\Debugger::log("Sending sign-up email to {$recipientName} <{$recipientAddress}>, application id: {$applicationId}, training: {$trainingAction}");

		$template->training     = $training;
		$template->trainingName = $trainingName;
		$template->start        = $start;
		$template->venueName    = $venueName;
		$template->venueNameExtended = $venueNameExtended;
		$template->venueAddress = $venueAddress;
		$template->venueCity    = $venueCity;

		$mail = new \Nette\Mail\Message();
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setBody($template)
			->clearHeader('X-Mailer')  // Hide Nette Mailer banner
			->send();
	}


	public function setTemplatesDir($dir)
	{
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= '/';
		}
		$this->templatesDir = $dir;
	}


	public function setEmailFrom($from)
	{
		$this->emailFrom = $from;
	}


	public function getApplications()
	{
		$applications = $this->trainingApplications->getByStatus(TrainingApplications::STATUS_ATTENDED);
		foreach ($applications as $application) {
			$application->files = $this->trainingApplications->getFiles($application->id);
		}

		foreach ($this->trainingApplications->getByStatus(TrainingApplications::STATUS_TENTATIVE) as $application) {
			if ($this->trainingDates->get($application->dateId)->status == TrainingDates::STATUS_CONFIRMED) {
				$applications[] = $application;
			}
		}

		return $applications;
	}


	public function sendInvitation(\Nette\Database\Row $application, \Nette\Templating\FileTemplate $template)
	{
		\Nette\Diagnostics\Debugger::log("Sending invitation email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		$template->setFile($this->templatesDir . 'admin/invitation.latte');
		$template->application = $application;
		$this->sendMail($application->email, $application->name, $template);
	}

	public function sendMaterials(\Nette\Database\Row $application, \Nette\Templating\FileTemplate $template)
	{
		\Nette\Diagnostics\Debugger::log("Sending materials email to {$application->name} <{$application->email}>, application id: {$application->id}, training: {$application->trainingAction}");

		if ($application->familiar) {
			$template->setFile($this->templatesDir . 'admin/materialsFamiliar.latte');
		} else {
			$template->setFile($this->templatesDir . 'admin/materials.latte');
		}
		$template->application = $application;
		$this->sendMail($application->email, $application->name, $template);
	}


	private function sendMail($recipientAddress, $recipientName, $template)
	{
		$mail = new \Nette\Mail\Message();
		$mail->setFrom($this->emailFrom)
			->addTo($recipientAddress, $recipientName)
			->addBcc($this->emailFrom)
			->setBody($template)
			->clearHeader('X-Mailer')  // Hide Nette Mailer banner
			->send();
	}


}
