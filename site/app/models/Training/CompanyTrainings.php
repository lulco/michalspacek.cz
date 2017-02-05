<?php
namespace MichalSpacekCz\Training;

/**
 * Company trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyTrainings
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Netxten\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Netxten\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\Netxten\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Training\Dates $trainingDates,
		\Nette\Localization\ITranslator $translator
	)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->trainingDates = $trainingDates;
		$this->translator = $translator;
	}


	/**
	 * Get training info.
	 *
	 * @param string $name
	 * @param boolean $includeCustom
	 * @return \Nette\Database\Row
	 */
	public function getInfo($name)
	{
		$result = $this->database->fetch(
			'SELECT
				t.id_training AS trainingId,
				a.action,
				t.name,
				ct.description,
				t.content,
				ct.upsell,
				t.prerequisites,
				t.audience,
				t.original_href AS originalHref,
				t.capacity,
				ct.price,
				t.student_discount AS studentDiscount,
				t.materials,
				t.custom,
				ct.duration,
				ct.double_duration AS doubleDuration,
				ct.double_duration_price AS doubleDurationPrice
			FROM trainings t
				JOIN company_trainings ct ON t.id_training = ct.key_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				a.action = ?
				AND l.language = ?',
			$name,
			$this->translator->getDefaultLocale()
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->content       = $this->texyFormatter->format($result->content);
			$result->upsell        = $this->texyFormatter->format($result->upsell);
			$result->prerequisites = $this->texyFormatter->format($result->prerequisites);
			$result->audience      = $this->texyFormatter->format($result->audience);
			$result->materials     = $this->texyFormatter->format($result->materials);
		}

		return $result;
	}


	/**
	 * Get company trainings without any public trainings
	 *
	 * @return array of \Nette\Database\Row
	 */
	public function getWithoutPublicUpcoming()
	{
		$result = $this->database->fetchAll(
			'SELECT
				a.action,
				t.name
			FROM trainings t
				JOIN company_trainings ct ON t.id_training = ct.key_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				t.key_successor IS NULL
				AND l.language = ?
			ORDER BY t.order IS NULL, t.order',
			$this->translator->getDefaultLocale()
		);
		$public = $this->trainingDates->getPublicUpcoming();

		$trainings = array();
		foreach ($result as $training) {
			if (!isset($public[$training->action])) {
				$trainings[$training->action] = $training;
			}
		}

		return $trainings;
	}


}
