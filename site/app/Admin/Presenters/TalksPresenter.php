<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TalkSlidesFormFactory;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Talks\TalkInputs;
use MichalSpacekCz\Talks\TalkInputsFactory;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Talks\TalkSlides;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Form;
use Nette\Utils\Html;

class TalksPresenter extends BasePresenter
{

	private Talk $talk;

	/** @var Row[] */
	private array $slides;

	private int $newCount;

	private int $maxSlideUploads;


	public function __construct(
		private readonly Talks $talks,
		private readonly TalkSlides $talkSlides,
		private readonly TalkInputsFactory $talkInputsFactory,
		private readonly TalkSlidesFormFactory $talkSlidesFormFactory,
		private readonly HttpInput $httpInput,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->upcomingTalks = $this->talks->getUpcoming();
		$this->template->talks = $this->talks->getAll();
	}


	public function actionTalk(int $param): void
	{
		try {
			$this->talk = $this->talks->getById($param);
		} catch (TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $this->talk);
		$this->template->talk = $this->talk;
	}


	public function actionSlides(int $param): void
	{
		try {
			$this->talk = $this->talks->getById($param);
			$this->slides = $this->talkSlides->getSlides($this->talk->getId(), $this->talk->getFilenamesTalkId());
		} catch (ContentTypeException | TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.admin.talkslides', $this->talk);
		$this->template->talkTitle = $this->talk->getTitle();
		$this->template->slides = $this->slides;
		$this->template->talk = $this->talk;
		$this->template->maxSlideUploads = $this->maxSlideUploads = (int)ini_get('max_file_uploads');
		$new = $this->httpInput->getPostArray('new');
		$count = $new ? count($new) : 0;
		$this->template->newCount = $this->newCount = ($count ?: (int)empty($this->slides));
		$this->template->dimensions = $this->talkSlides->getSlideDimensions();
	}


	protected function createComponentEditTalkInputs(): TalkInputs
	{
		return $this->talkInputsFactory->createFor($this->talk);
	}


	protected function createComponentAddTalkInputs(): TalkInputs
	{
		return $this->talkInputsFactory->create();
	}


	protected function createComponentSlides(): Form
	{
		return $this->talkSlidesFormFactory->create(
			function (Html $message, string $type, int $talkId): never {
				$this->flashMessage($message, $type);
				$this->redirect('Talks:slides', $talkId);
			},
			$this->talk->getId(),
			$this->slides,
			$this->newCount,
			$this->maxSlideUploads,
			$this->request,
		);
	}

}
