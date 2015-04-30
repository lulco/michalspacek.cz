<?php
namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training;

/**
 * E-mails to send form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingMailsOutbox extends \Nette\Application\UI\Form
{

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, array $applications)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$applicationsContainer = $this->addContainer('applications');

		foreach ($applications as $application) {
			$applicationIdsContainer = $applicationsContainer->addContainer($application->id);
			$checked = true;
			$disabled = false;
			switch ($application->status) {
				case Training\Statuses::STATUS_ATTENDED:
					$checked = (bool)$application->files;
					$disabled = !$checked;
					break;
				case Training\Statuses::STATUS_SIGNED_UP:
					$checked = ($application->price && $application->vatRate && $application->priceVat);
					$disabled = !$checked;
					break;
			}
			$applicationIdsContainer->addCheckbox('send')
				->setDefaultValue($checked)
				->setDisabled($disabled);
			$applicationIdsContainer->addTextArea('additional', null)
				->setAttribute('placeholder', 'Dodatečný text')
				->setAttribute('cols', 80)
				->setAttribute('rows', 3);
			switch ($application->status) {
				case Training\Statuses::STATUS_SIGNED_UP:
					$applicationIdsContainer->addText('invoiceId')
						->setType('number')
						->setAttribute('placeholder', 'Faktura č.')
						->setAttribute('title', 'Faktura č.')
						->setDefaultValue($application->invoiceId)
						->addConditionOn($applicationIdsContainer['send'], self::FILLED)
							->addRule(self::FILLED, 'Chybí číslo faktury');
					$applicationIdsContainer->addUpload('invoice')
						->setAttribute('title', 'Faktura v PDF')
						->addConditionOn($applicationIdsContainer['send'], self::FILLED)
							->addRule(self::FILLED, 'Chybí faktura')
							->addRule(self::MIME_TYPE, 'Faktura není v PDF', 'application/pdf');
					break;
			}
		}

		$this->addSubmit('submit', 'Odeslat');
	}

}
