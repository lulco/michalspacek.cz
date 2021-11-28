<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\FormDataLogger;
use MichalSpacekCz\Training\FormSpam;
use MichalSpacekCz\Training\Mails;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;
use Nette\Forms\Controls\TextInput;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use Netxten\Forms\Controls\HiddenFieldWithLabel;
use OutOfBoundsException;
use PDOException;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationFactory
{

	public function __construct(
		private FormFactory $factory,
		private Translator $translator,
		private TrainingControlsFactory $trainingControlsFactory,
		private Dates $trainingDates,
		private FormDataLogger $formDataLogger,
		private FormSpam $formSpam,
		private Applications $trainingApplications,
		private Mails $trainingMails,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param callable(): Template $createTemplate
	 * @param string $action
	 * @param Html $name
	 * @param Row[] $dates
	 * @param SessionSection<string> $sessionSection
	 * @return Form
	 */
	public function create(
		callable $onSuccess,
		callable $onError,
		callable $createTemplate,
		string $action,
		Html $name,
		array $dates,
		SessionSection $sessionSection,
	): Form {
		$form = $this->factory->create();

		$inputDates = array();
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$el = Html::el()->setText($this->trainingDates->formatDateVenueForUser($date));
			if ($date->label) {
				if ($multipleDates) {
					$el->addText(" [{$date->label}]");
				} else {
					$el->addHtml(Html::el('small', ['class' => 'label'])->setText($date->label));
				}
			}
			$inputDates[$date->dateId] = $el;
		}

		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if ($multipleDates) {
			$form->addSelect('trainingId', $label, $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -')
				->addRule(Form::INTEGER);
		} else {
			$key = key($inputDates);
			$field = new HiddenFieldWithLabel($label, (string)$key, $inputDates[$key]);
			$field->addRule(Form::INTEGER);
			$form->addComponent($field, 'trainingId');
		}

		$this->trainingControlsFactory->addAttendee($form);
		$this->trainingControlsFactory->addCompany($form);
		$this->trainingControlsFactory->addNote($form)
			->setHtmlAttribute('placeholder', $this->translator->translate('messages.trainings.applicationform.note'));
		$this->trainingControlsFactory->addCountry($form);

		$form->addSubmit('signUp', 'Odeslat');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $onError, $createTemplate, $action, $name, $dates, $sessionSection): void {
			try {
				$this->formSpam->check($values, $action, $sessionSection);
				$this->checkTrainingDate($values, $action, $dates, $sessionSection);

				$date = $dates[$values->trainingId];
				if ($date->tentative) {
					$this->trainingApplications->addInvitation(
						$date,
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
					);
				} else {
					if (isset($sessionSection->application[$action]) && $sessionSection->application[$action]['dateId'] == $values->trainingId) {
						$applicationId = $this->trainingApplications->updateApplication(
							$date,
							$sessionSection->application[$action]['id'],
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
						);
						$sessionSection->application[$action] = null;
					} else {
						$applicationId = $this->trainingApplications->addApplication(
							$date,
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
						);
					}
					$this->trainingMails->sendSignUpMail(
						$applicationId,
						$createTemplate(),
						$values->email,
						$values->name,
						$date->start,
						$date->end,
						$action,
						$name,
						$date->remote,
						$date->venueName,
						$date->venueNameExtended,
						$date->venueAddress,
						$date->venueCity,
					);
				}
				$sessionSection->trainingId = $values->trainingId;
				$sessionSection->name = $values->name;
				$sessionSection->email = $values->email;
				$sessionSection->company = $values->company;
				$sessionSection->street = $values->street;
				$sessionSection->city = $values->city;
				$sessionSection->zip = $values->zip;
				$sessionSection->country = $values->country;
				$sessionSection->companyId = $values->companyId;
				$sessionSection->companyTaxId = $values->companyTaxId;
				$sessionSection->note = $values->note;
				$onSuccess($action);
			} catch (SpammyApplicationException $e) {
				Debugger::log($e);
				$onError('messages.trainings.spammyapplication');
			} catch (PDOException $e) {
				Debugger::log($e, Debugger::ERROR);
				$onError('messages.trainings.errorapplication');
			}
		};
		$this->setApplication($form, $sessionSection);
		return $form;
	}


	/**
	 * @param Form $form
	 * @param SessionSection<string> $application
	 */
	private function setApplication(Form $form, SessionSection $application): void
	{
		$values = array(
			'name' => $application->name,
			'email' => $application->email,
			'company' => $application->company,
			'street' => $application->street,
			'city' => $application->city,
			'zip' => $application->zip,
			'country' => $application->country,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
		);
		$form->setDefaults($values);

		if (!empty($application->country)) {
			$message = "messages.label.taxid.{$application->country}";
			$caption = $this->translator->translate($message);
			if ($caption !== $message) {
				/** @var TextInput $input */
				$input = $form->getComponent('companyTaxId');
				$input->caption = "{$caption}:";
			}
		}
	}


	/**
	 * @param stdClass $values
	 * @param string $name
	 * @param Row[] $dates
	 * @param SessionSection $sessionSection
	 */
	private function checkTrainingDate(stdClass $values, string $name, array $dates, SessionSection $sessionSection): void
	{
		if (!isset($dates[$values->trainingId])) {
			$this->formDataLogger->log($values, $name, $sessionSection);
			$message = "Training date id {$values->trainingId} is not an upcoming training, should be one of " . implode(', ', array_keys($dates));
			throw new OutOfBoundsException($message);
		}
	}

}
