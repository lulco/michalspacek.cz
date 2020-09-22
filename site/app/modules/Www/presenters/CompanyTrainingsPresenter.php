<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Trainings;
use MichalSpacekCz\Vat;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/**
 * Company Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyTrainingsPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;

	/** @var Trainings */
	protected $trainings;

	/** @var CompanyTrainings */
	protected $companyTrainings;

	/** @var Locales */
	protected $trainingLocales;

	/** @var Reviews */
	protected $trainingReviews;

	/** @var Vat */
	protected $vat;

	/** @var IResponse */
	protected $httpResponse;


	/**
	 * @param Texy $texyFormatter
	 * @param Trainings $trainings
	 * @param CompanyTrainings $companyTrainings
	 * @param Locales $trainingLocales
	 * @param Reviews $trainingReviews
	 * @param Vat $vat
	 * @param IResponse $httpResponse
	 */
	public function __construct(
		Texy $texyFormatter,
		Trainings $trainings,
		CompanyTrainings $companyTrainings,
		Locales $trainingLocales,
		Reviews $trainingReviews,
		Vat $vat,
		IResponse $httpResponse
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->trainings = $trainings;
		$this->companyTrainings = $companyTrainings;
		$this->trainingLocales = $trainingLocales;
		$this->trainingReviews = $trainingReviews;
		$this->vat = $vat;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.companytrainings');
		$this->template->trainings = $this->trainings->getNames();
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}


	/**
	 * @param string $name
	 * @throws BadRequestException
	 */
	public function actionTraining($name)
	{
		$training = $this->companyTrainings->getInfo($name);
		if (!$training) {
			throw new BadRequestException("I don't do {$name} training, yet", IResponse::S404_NOT_FOUND);
		}

		$this->template->name = $training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.companytraining', [$training->name]);
		$this->template->title = $training->name;
		$this->template->description = $training->description;
		$this->template->content = $training->content;
		$this->template->upsell = $training->upsell;
		$this->template->prerequisites = $training->prerequisites;
		$this->template->audience = $training->audience;
		$this->template->duration = $training->duration;
		$this->template->alternativeDuration = $training->alternativeDuration;
		$this->template->price = $training->price;
		$this->template->priceVat = $this->vat->addVat($training->price);
		$this->template->alternativeDurationPriceText = $training->alternativeDurationPriceText;
		$this->template->materials = $training->materials;
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->trainingId, 3);
		if ($training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_GONE);
		}
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		if ($this->getAction() === 'default') {
			return parent::getLocaleLinkParams();
		} else {
			$params = [];
			foreach ($this->trainingLocales->getLocaleActions($this->getParameter('name')) as $key => $value) {
				$params[$key] = ['name' => $value];
			}
			return $params;
		}
	}

}
