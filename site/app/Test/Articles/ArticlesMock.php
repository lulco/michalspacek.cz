<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Articles;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\Blog\BlogPost;
use Nette\Utils\Html;

class ArticlesMock extends Articles
{

	/** @var list<BlogPost> */
	private array $articles = [];


	/** @noinspection PhpMissingParentConstructorInspection Intentionally */
	public function __construct()
	{
	}


	public function getNearestPublishDate(): ?DateTime
	{
		return null;
	}


	/**
	 * @param list<ArticleEdit> $edits
	 */
	public function addBlogPost(int $postId, DateTime $published, string $suffix, array $edits = [], bool $omitExports = false): void
	{
		$post = new BlogPost();
		$post->postId = $postId;
		$post->title = Html::fromText("Title {$suffix}");
		$post->href = "https://example.com/{$suffix}";
		$post->published = $published;
		$post->lead = Html::fromText("Excerpt {$suffix}");
		$post->text = Html::fromText("Text {$suffix}");
		$post->edits = $edits;
		$post->slugTags = [];
		$post->omitExports = $omitExports;
		$this->articles[] = $post;
	}


	/**
	 * @return list<BlogPost>
	 */
	public function getAll(?int $limit = null): array
	{
		return $this->articles;
	}


	public function reset(): void
	{
		$this->articles = [];
	}

}
