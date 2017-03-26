<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog\Post;

/**
 * Blog post loader service.
 *
 * Fast loader, no extra work, no formatting, no circular references.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Loader
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Database\Row */
	protected $post;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Check whether the post exists.
	 *
	 * @param string $post
	 * @return boolean
	 */
	public function exists(string $post, ?string $previewKey = null): bool
	{
		return (bool)$this->fetch($post, $previewKey);
	}


	/**
	 * Fetch post.
	 *
	 * @param string $post
	 * @param string $previewKey
	 * @return \Nette\Database\Row|null
	 */
	public function fetch(string $post, ?string $previewKey = null): ?\Nette\Database\Row
	{
		if ($this->post === null) {
			$this->post = $this->database->fetch(
				'SELECT
					bp.id_blog_post AS postId,
					bp.slug,
					bp.title AS titleTexy,
					bp.lead AS leadTexy,
					bp.text AS textTexy,
					bp.published,
					bp.preview_key AS previewKey,
					bp.originally AS originallyTexy,
					bp.og_image AS ogImage,
					bp.tags,
					bp.recommended,
					tct.card AS twitterCard
				FROM blog_posts bp
				LEFT JOIN twitter_card_types tct
					ON tct.id_twitter_card_type = bp.key_twitter_card_type
				WHERE bp.slug = ?
					AND (bp.published <= ? OR bp.preview_key = ?)',
				$post,
				new \Nette\Utils\DateTime(),
				$previewKey
			) ?: null;
		}
		return $this->post;
	}

}
