<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationMultiple extends \Nette\Application\UI\Form
{

	use Controls\TrainingAttendee;
	use Controls\TrainingCompany;
	use Controls\TrainingCountry;
	use Controls\TrainingNote;
	use Controls\TrainingSource;
	use Controls\TrainingStatusDate;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param integer $count
	 * @param array $statuses
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, $count, $statuses, \MichalSpacekCz\Training\Applications $trainingApplications, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->trainingApplications = $trainingApplications;
		$this->translator = $translator;
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$applicationsContainer = $this->addContainer('applications');
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$this->addAttendee($dataContainer);
			$this->addCompany($dataContainer);
			$this->addNote($dataContainer);
			$dataContainer->getComponent('name')->caption = 'Jméno:';
			$dataContainer->getComponent('company')->caption = 'Společnost:';
			$dataContainer->getComponent('street')->caption = 'Ulice:';
		}

		$this->addCountry($this);
		$this->addStatusDate('date', 'Datum:', true);
		$this->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$this->addSource($this)
			->setPrompt('- vyberte zdroj -');

		$this->addSubmit('submit', 'Přidat');
	}

}
