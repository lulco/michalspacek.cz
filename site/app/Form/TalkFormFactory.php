<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Media\VideoThumbnails;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Talks\Talks;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class TalkFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Talks $talks,
		private readonly LinkGenerator $linkGenerator,
		private readonly VideoThumbnails $videoThumbnails,
	) {
	}


	/**
	 * @param callable(Html): void $onSuccess
	 * @param Talk|null $talk
	 * @return Form
	 */
	public function create(callable $onSuccess, ?Talk $talk = null): Form
	{
		$form = $this->factory->create();
		$allTalks = $this->getAllTalksExcept($talk ? (string)$talk->getAction() : null);

		$form->addText('action', 'Akce:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$form->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule($form::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$form->addTextArea('description', 'Popis:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka popisu je %d znaků', 65535);
		$this->trainingControlsFactory->addDate(
			$form->addText('date', 'Datum:'),
			true,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
		$form->addText('href', 'Odkaz na přednášku:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na přednášku je %d znaků', 200);
		$form->addText('duration', 'Délka:')
			->setHtmlType('number');
		$form->addSelect('slidesTalk', 'Použít slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí slajdy');
		$form->addSelect('filenamesTalk', 'Soubory pro slajdy z:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, ze které se použijí soubory pro slajdy');
		$form->addText('slidesHref', 'Odkaz na slajdy:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na slajdy je %d znaků', 200);
		$form->addText('slidesEmbed', 'Embed odkaz na slajdy:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na slajdy je %d znaků', 200);
		$form->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na video je %d znaků', 200);
		$videoThumbnailFormFields = $this->videoThumbnails->addFormFields($form, $talk?->getVideo()->getThumbnailFilename() !== null, $talk?->getVideo()->getThumbnailAlternativeContentType() !== null);
		$form->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$form->addText('event', 'Událost:')
			->setRequired('Zadejte prosím událost')
			->addRule($form::MAX_LENGTH, 'Maximální délka události je %d znaků', 200);
		$form->addText('eventHref', 'Odkaz na událost:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na událost je %d znaků', 200);
		$form->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);
		$form->addTextArea('transcript', 'Přepis:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka přepisu je %d znaků', 65535);
		$form->addTextArea('favorite', 'Popis pro oblíbené:')
			->setRequired(false)
			->addRule($form::MAX_LENGTH, 'Maximální délka popisu pro oblíbené je %d znaků', 65535);
		$form->addSelect('supersededBy', 'Nahrazeno přednáškou:', $allTalks)
			->setPrompt('Vyberte prosím přednášku, kterou se tato nahradí');
		$form->addCheckbox('publishSlides', 'Publikovat slajdy:');
		$submit = $form->addSubmit('submit', 'Přidat');

		if ($talk) {
			$this->setTalk($form, $talk, $submit);
		}

		$form->onSuccess[] = function (Form $form) use ($talk, $onSuccess): void {
			$values = $form->getValues();
			$videoThumbnailBasename = $this->videoThumbnails->getUploadedMainFileBasename($values);
			$videoThumbnailBasenameAlternative = $this->videoThumbnails->getUploadedAlternativeFileBasename($values);
			if ($talk) {
				$removeVideoThumbnail = $values->removeVideoThumbnail ?? false;
				$removeVideoThumbnailAlternative = $values->removeVideoThumbnailAlternative ?? false;
				$this->talks->update(
					$talk->getId(),
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					(int)$values->duration,
					$values->href,
					$values->slidesTalk,
					$values->filenamesTalk,
					$values->slidesHref,
					$values->slidesEmbed,
					$values->videoHref,
					$videoThumbnailBasename ?? ($removeVideoThumbnail ? null : $talk->getVideo()->getThumbnailFilename()),
					$videoThumbnailBasenameAlternative ?? ($removeVideoThumbnailAlternative ? null : $talk->getVideo()->getThumbnailAlternativeFilename()),
					$values->videoEmbed,
					$values->event,
					$values->eventHref,
					$values->ogImage,
					$values->transcript,
					$values->favorite,
					$values->supersededBy,
					$values->publishSlides,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($talk->getId(), $values);
				if ($removeVideoThumbnail && $talk->getVideo()->getThumbnailFilename()) {
					$this->videoThumbnails->deleteFile($talk->getId(), $talk->getVideo()->getThumbnailFilename());
				}
				if ($removeVideoThumbnailAlternative && $talk->getVideo()->getThumbnailAlternativeFilename()) {
					$this->videoThumbnails->deleteFile($talk->getId(), $talk->getVideo()->getThumbnailAlternativeFilename());
				}
				$message = Html::el()->setText('Přednáška upravena ');
			} else {
				$talkId = $this->talks->add(
					$values->action,
					$values->title,
					$values->description,
					$values->date,
					(int)$values->duration,
					$values->href,
					$values->slidesTalk,
					$values->filenamesTalk,
					$values->slidesHref,
					$values->slidesEmbed,
					$values->videoHref,
					$videoThumbnailBasename,
					$videoThumbnailBasenameAlternative,
					$values->videoEmbed,
					$values->event,
					$values->eventHref,
					$values->ogImage,
					$values->transcript,
					$values->favorite,
					$values->supersededBy,
					$values->publishSlides,
				);
				$this->videoThumbnails->saveVideoThumbnailFiles($talkId, $values);
				$message = Html::el()->setText('Přednáška přidána ');
			}
			$message->addHtml(Html::el('a')->href($this->linkGenerator->link('Www:Talks:talk', [$values->action]))->setText('Zobrazit'));
			$onSuccess($message);
		};

		$this->videoThumbnails->addOnValidateUploads($form, $videoThumbnailFormFields);

		return $form;
	}


	public function setTalk(Form $form, Talk $talk, SubmitButton $submit): void
	{
		$values = [
			'action' => $talk->getAction(),
			'title' => $talk->getTitleTexy(),
			'description' => $talk->getDescriptionTexy(),
			'date' => $talk->getDate()->format('Y-m-d H:i'),
			'href' => $talk->getHref(),
			'duration' => $talk->getDuration(),
			'slidesTalk' => $talk->getSlidesTalkId(),
			'filenamesTalk' => $talk->getFilenamesTalkId(),
			'slidesHref' => $talk->getSlidesHref(),
			'slidesEmbed' => $talk->getSlidesEmbed(),
			'videoHref' => $talk->getVideo()->getVideoHref(),
			'videoEmbed' => $talk->getVideoEmbed(),
			'event' => $talk->getEventTexy(),
			'eventHref' => $talk->getEventHref(),
			'ogImage' => $talk->getOgImage(),
			'transcript' => $talk->getTranscriptTexy(),
			'favorite' => $talk->getFavorite(),
			'supersededBy' => $talk->getSupersededById(),
			'publishSlides' => $talk->isPublishSlides(),
		];
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}


	/**
	 * @param string|null $talkAction
	 * @return array<int, string>
	 */
	private function getAllTalksExcept(?string $talkAction): array
	{
		$allTalks = [];
		foreach ($this->talks->getAll() as $talk) {
			if ($talkAction !== $talk->getAction()) {
				$title = Strings::truncate($talk->getTitleTexy(), 40);
				$event = Strings::truncate(strip_tags($talk->getEvent()->render()), 30);
				$allTalks[$talk->getId()] = sprintf('%s (%s, %s)', $title, $talk->getDate()->format('j. n. Y'), $event);
			}
		}
		return $allTalks;
	}

}
