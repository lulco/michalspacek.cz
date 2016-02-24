<?php
namespace MichalSpacekCz\Training;

/**
 * Training application notifications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Notifications
{

	/** @var Applications */
	protected $trainingApplications;

	public function __construct(
		Applications $trainingApplications,
		Dates $trainingDates,
		Statuses $trainingStatuses
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
	}


	public function getApplications()
	{
		$applications = array();
		foreach ($this->trainingStatuses->getParentStatuses(Statuses::STATUS_NOTIFIED) as $status) {
			foreach ($this->trainingApplications->getByStatus($status) as $application) {
				$date = $this->trainingDates->get($application->dateId);
				if ($date->public && !$date->cooperationId) {
					$applications[] = $application;
				}
			}
		}
		return $applications;
	}


	public function getCounts()
	{
		$applications = $this->getApplications();
		$paid = array_filter($applications, function ($application) {
			return isset($application->paid);
		});
		return array(count($applications), count($paid));
	}

}
