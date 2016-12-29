<?php
namespace App\AdminModule\Presenters;

/**
 * Tracking presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrackingPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(\MichalSpacekCz\WebTracking $webTracking)
	{
		$this->webTracking = $webTracking;
	}


	public function actionEnable()
	{
		$this->webTracking->enable();
		$this->redirect('Homepage:');
	}


	public function actionDisable()
	{
		$this->webTracking->disable();
		$this->redirect('Homepage:');
	}

}
