<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

/**
 * Trainings locales model.
 *
 * Split from Trainings to avoid circular references.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Locales
{

	/** @var \Nette\Database\Context */
	protected $database;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get localized training actions.
	 *
	 * @param string $action
	 * @return array of (locale, action)
	 */
	public function getLocaleActions(string $action): array
	{
		return $this->database->fetchPairs(
			'SELECT
				l.language,
				a.action
			FROM
				url_actions a
				JOIN training_url_actions ta ON a.id_url_action = ta.key_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE ta.key_training = (
				SELECT ta.key_training
				FROM url_actions a
				JOIN training_url_actions ta ON a.id_url_action = ta.key_url_action
				WHERE a.action = ?
			)',
			$action
		);
	}

}
