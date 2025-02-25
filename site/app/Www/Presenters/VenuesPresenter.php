<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesList;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesListFactory;
use MichalSpacekCz\Training\Exceptions\TrainingVenueNotFoundException;
use MichalSpacekCz\Training\Venues\TrainingVenues;
use Nette\Application\BadRequestException;

class VenuesPresenter extends BasePresenter
{

	private int $venueId;
	private UpcomingTrainingDatesList $upcomingTrainingDatesList;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly TrainingVenues $trainingVenues,
		private readonly UpcomingTrainingDatesListFactory $upcomingTrainingDatesListFactory,
	) {
		parent::__construct();
	}


	public function actionVenue(string $name): void
	{
		try {
			$venue = $this->trainingVenues->get($name);
		} catch (TrainingVenueNotFoundException $e) {
			throw new BadRequestException("Where in the world is {$name}?", previous: $e);
		}
		$this->venueId = $venue->getId();
		$this->upcomingTrainingDatesList = $this->upcomingTrainingDatesListFactory->createForVenue($this->venueId);

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.venue', [$venue->getName()]);
		$this->template->venue = $venue;
		$this->template->hasUpcomingTrainings = count($this->upcomingTrainingDatesList) > 0;
	}


	protected function createComponentUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesList;
	}

}
