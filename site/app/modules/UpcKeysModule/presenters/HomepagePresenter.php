<?php
namespace App\UpcKeysModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \App\Presenters\BasePresenter
{

	/** @var string */
	protected $ssid;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\UpcKeys */
	protected $upcKeys;


	public function __construct(\Nette\Database\Context $context, \MichalSpacekCz\UpcKeys $upcKeys)
	{
		$this->database = $context;
		$this->upcKeys = $upcKeys;
	}


	/**
	 * Default action handler.
	 *
	 * Doesn't use flash messages because I want the errors to be gone after post-redirect-get.
	 *
	 * @param string|NULL $ssid
	 */
	public function actionDefault($ssid = null)
	{
		$this->ssid = $ssid;
		if ($this->ssid !== null) {
			if ($this->ssid !== strtoupper($this->ssid)) {
				$this->redirect('this', strtoupper($this->ssid));
			}
			if ($this->upcKeys->isValidSsid($this->ssid)) {
				$keys = $this->upcKeys->getKeys($this->ssid);
				if (!$keys) {
					$this->template->error = 'Oops, something went wrong, please try again in a moment';
				} else {
					foreach ($keys as $key) {
						switch ($key->type) {
							case \MichalSpacekCz\UpcKeys::SSID_TYPE_24GHZ:
								$key->type = '2.4 GHz';
								break;
							case \MichalSpacekCz\UpcKeys::SSID_TYPE_5GHZ:
								$key->type = '5 GHz';
								break;
							default:
								throw new \RuntimeException('Unknown network type ' . $type);
								break;
						}
					}
					$this->template->keys = $keys;
				}
				$this->template->ssid = $this->ssid;
			} else {
				$this->template->error = 'Wi-Fi network name is not "UPC" and 7 numbers, the password cannot be recovered by this tool';
			}
		}
		$this->template->placeholder = $this->upcKeys->getSsidPlaceholder();
	}


	protected function createComponentSsid($formName)
	{
		$form = new \MichalSpacekCz\Form\UpcKeys($this, $formName, $this->ssid, $this->upcKeys);
		$form->onSuccess[] = $this->submittedSsid;
		return $form;
	}


	public function submittedSsid(\MichalSpacekCz\Form\UpcKeys $form)
	{
		$values = $form->getValues();
		$ssid = strtoupper(trim($values->ssid));
		$this->upcKeys->saveKeys($ssid);
		$this->redirect('this', $ssid);
	}


}
