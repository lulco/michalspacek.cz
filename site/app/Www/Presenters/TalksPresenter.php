<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Media\VideoFactory;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\UnknownSlideException;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Talks\TalkSlides;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;

class TalksPresenter extends BasePresenter
{

	public function __construct(
		private readonly Talks $talks,
		private readonly TalkSlides $talkSlides,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly VideoFactory $videoFactory,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->favoriteTalks = $this->talks->getFavorites();
		$this->template->upcomingTalks = $this->talks->getUpcoming();

		$talks = [];
		foreach ($this->talks->getAll() as $talk) {
			$talks[$talk->date->format('Y')][] = $talk;
		}
		$this->template->talks = $talks;
	}


	/**
	 * @param string $name
	 * @param string|null $slide
	 * @throws InvalidLinkException
	 * @throws ContentTypeException
	 */
	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$talk = $this->talks->get($name);
			if ($talk->slidesTalkId) {
				$slidesTalk = $this->talks->getById($talk->slidesTalkId);
				$slides = ($slidesTalk->publishSlides ? $this->talkSlides->getSlides($slidesTalk->talkId, $slidesTalk->filenamesTalkId) : []);
				$slideNo = $this->talkSlides->getSlideNo($talk->slidesTalkId, $slide);
				$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$slidesTalk->action]);
			} else {
				$slides = ($talk->publishSlides ? $this->talkSlides->getSlides($talk->talkId, $talk->filenamesTalkId) : []);
				$slideNo = $this->talkSlides->getSlideNo($talk->talkId, $slide);
				if ($slideNo !== null) {
					$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$talk->action]);
				}
			}
		} catch (UnknownSlideException | TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $talk);
		$this->template->pageHeader = $talk->title;
		$this->template->talk = $talk;
		$this->template->slideNo = $slideNo;
		$this->template->slides = $slides;
		$this->template->ogImage = ($slides[$slideNo ?? 1]->image ?? ($talk->ogImage !== null ? sprintf($talk->ogImage, $slideNo ?? 1) : null));
		$this->template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();
		$this->template->video = $this->videoFactory->createFromDatabaseRow($talk)->setLazyLoad(count($slides) > 3);
		$this->template->slidesPlatform = $talk->slidesHref ? SlidesPlatform::tryFromUrl($talk->slidesHref)?->getName() : null;
	}

}
