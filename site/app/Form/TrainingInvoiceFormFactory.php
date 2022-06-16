<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use Nette\Application\UI\Form;
use stdClass;

class TrainingInvoiceFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Applications $trainingApplications,
		private readonly TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param callable(): void $onError
	 * @param array<int, string> $unpaidInvoiceIds
	 * @return Form
	 */
	public function create(callable $onSuccess, callable $onError, array $unpaidInvoiceIds): Form
	{
		$form = $this->factory->create();
		$form->addText('invoice', 'Faktura:')
			->setRequired('Zadejte prosím číslo faktury')
			->addRule($form::IS_IN, 'Zadejte číslo některé z nezaplacených faktur', $unpaidInvoiceIds);
		$this->trainingControlsFactory->addPaidDate($form, 'paid', 'Zaplaceno:', true);
		$form->addSubmit('submit', 'Zaplaceno');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $onError): void {
			$count = $this->trainingApplications->setPaidDate($values->invoice, $values->paid);
			if ($count === null) {
				$onError();
			} else {
				$onSuccess($count);
			}
		};
		return $form;
	}

}
