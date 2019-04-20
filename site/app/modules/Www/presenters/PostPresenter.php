<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Post;
use MichalSpacekCz\Training\Dates;
use Nette\Application\AbortException;
use Nette\Application\UI\InvalidLinkException;

/**
 * Post presenter.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class PostPresenter extends BasePresenter
{
	/** @var Post */
	protected $blogPost;

	/** @var Dates */
	protected $trainingDates;

	/** @var string[][] */
	protected $localeLinkParams = [];


	/**
	 * @param Post $blogPost
	 * @param Dates $trainingDates
	 */
	public function __construct(Post $blogPost, Dates $trainingDates)
	{
		$this->blogPost = $blogPost;
		$this->trainingDates = $trainingDates;
		parent::__construct();
	}


	/**
	 * @param string $slug
	 * @param string|null $preview
	 * @throws AbortException
	 * @throws InvalidLinkException
	 */
	public function actionDefault(string $slug, ?string $preview = null): void
	{
		$post = $this->blogPost->get($slug, $preview);
		if ($preview !== null) {
			if (!$post->needsPreviewKey()) {
				$this->redirect($this->getAction(), $slug);
			}
			$this->template->robots = 'noindex';
		}
		$edits = $this->blogPost->getEdits($post->postId);
		$this->template->post = $post;
		$this->template->pageTitle = htmlspecialchars_decode(strip_tags((string)$post->title));
		$this->template->pageHeader = $post->title;
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->edits = $edits;
		if ($edits && current($edits)->editedAt->diff($post->published)->days >= $this->blogPost->getUpdatedInfoThreshold()) {
			$this->template->edited = current($edits)->editedAt;
		}

		foreach ($this->blogPost->getLocaleUrls($post->slug) as $post) {
			$this->localeLinkParams[$post->locale] = ['slug' => $post->slug, 'preview' => ($post->needsPreviewKey() ? $post->previewKey : null)];
		}
	}


	/**
	 * Get original module:presenter:action for locale links.
	 *
	 * @return string
	 */
	protected function getLocaleLinkAction(): string
	{
		return ($this->localeLinkParams ? parent::getLocaleLinkAction() : 'Www:Articles:');
	}


	/**
	 * Translated locale parameters for blog posts.
	 *
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		return ($this->localeLinkParams ?: []);
	}

}
