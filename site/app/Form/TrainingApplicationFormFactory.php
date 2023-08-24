<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormSuccess;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDates;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Http\SessionSection;
use Nette\Utils\Html;

class TrainingApplicationFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Translator $translator,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly TrainingDates $trainingDates,
		private readonly TrainingApplicationFormSuccess $formSuccess,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param string $action
	 * @param Html $name
	 * @param array<int, TrainingDate> $dates
	 * @param SessionSection<string> $sessionSection
	 * @return Form
	 */
	public function create(
		callable $onSuccess,
		callable $onError,
		string $action,
		Html $name,
		array $dates,
		SessionSection $sessionSection,
	): Form {
		$form = $this->factory->create();

		$inputDates = [];
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$el = Html::el()->setText($this->trainingDates->formatDateVenueForUser($date));
			if ($date->getLabel()) {
				if ($multipleDates) {
					$el->addText(" [{$date->getLabel()}]");
				} else {
					$el->addHtml(Html::el('small', ['class' => 'label'])->setText($date->getLabel()));
				}
			}
			$inputDates[$date->getId()] = $el;
		}

		// trainingId is actually dateId, oh well
		if ($multipleDates) {
			$form->addSelect('trainingId', $this->translator->translate('label.trainingdate'), $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -')
				->addRule($form::INTEGER);
		}

		$this->trainingControlsFactory->addAttendee($form);
		$this->trainingControlsFactory->addCompany($form);
		$this->trainingControlsFactory->addNote($form)
			->setHtmlAttribute('placeholder', $this->translator->translate('messages.trainings.applicationform.note'));
		$this->trainingControlsFactory->addCountry($form);

		$form->addSubmit('signUp', 'Odeslat');

		$form->onSuccess[] = function (Form $form) use ($onSuccess, $onError, $action, $name, $dates, $multipleDates, $sessionSection): void {
			$this->formSuccess->success($form, $onSuccess, $onError, $action, $name, $dates, $multipleDates, $sessionSection);
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
		$values = [
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
		];
		$form->setDefaults($values);

		if (!empty($application->country)) {
			$message = "messages.label.taxid.{$application->country}";
			$caption = $this->translator->translate($message);
			if ($caption !== $message) {
				$input = $form->getComponent('companyTaxId');
				if (!$input instanceof TextInput) {
					throw new ShouldNotHappenException(sprintf("The 'companyTaxId' component should be '%s' but it's a %s", TextInput::class, get_debug_type($input)));
				}
				$input->caption = "{$caption}:";
			}
		}
	}

}
