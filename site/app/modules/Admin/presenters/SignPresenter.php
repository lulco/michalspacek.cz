<?php
namespace App\AdminModule\Presenters;

/**
 * Sign in/out presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SignPresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/** @persistent */
	public $backlink = '';

	/** @var \MichalSpacekCz\User\Manager */
	protected $authenticator;

	/** @var string[] */
	private $noReturningUserCheck = [
		'knockKnock',
	];


	/**
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 */
	public function __construct(\MichalSpacekCz\User\Manager $authenticator)
	{
		$this->authenticator = $authenticator;
		parent::__construct();
	}


	public function actionDefault()
	{
		$this->redirect('in');
	}


	public function actionKnockKnock($param)
	{
		if ($this->authenticator->verifyReturningUser($param)) {
			$this->authenticator->setReturningUser($param);
		}

		$this->redirect($this->user->isLoggedIn() ? 'Homepage:' : 'in');
	}


	public function actionIn()
	{
		if (!$this->authenticator->isReturningUser()) {
			$this->forward('Honeypot:signIn');
		}

		$this->getSession()->start();
		if (($token = $this->authenticator->verifyPermanentLogin()) !== null) {
			$this->user->login($this->authenticator->getIdentity($token->userId, $token->username));
			$this->authenticator->regeneratePermanentLogin($this->user);
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		}
		$this->template->pageTitle = 'Přihlásit se';
	}


	protected function createComponentSignIn($formName)
	{
		$form = new \MichalSpacekCz\Form\SignIn($this, $formName);
		$form->onSuccess[] = [$this, 'submittedSignIn'];
		return $form;
	}


	public function submittedSignIn(\MichalSpacekCz\Form\SignIn $form, $values)
	{
		$this->user->setExpiration('30 minutes', \Nette\Security\IUserStorage::CLEAR_IDENTITY);
		try {
			$this->user->login($values->username, $values->password);
			\Tracy\Debugger::log("Successful sign-in attempt ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			if ($values->remember) {
				$this->authenticator->storePermanentLogin($this->user);
			} else {
				$this->authenticator->clearPermanentLogin($this->user);
			}
			$this->restoreRequest($this->backlink);
			$this->redirect('Homepage:');
		} catch (\Nette\Security\AuthenticationException $e) {
			\Tracy\Debugger::log("Failed sign-in attempt: {$e->getMessage()} ({$values->username}, {$this->getHttpRequest()->getRemoteAddress()})", 'auth');
			$form->addError('Špatné uživatelské jméno nebo heslo');
		}
	}


	public function actionOut()
	{
		$this->authenticator->clearPermanentLogin($this->user);
		$this->user->logout();
		$this->flashMessage('Byli jste odhlášeni');
		$this->redirect('in');
	}

}
