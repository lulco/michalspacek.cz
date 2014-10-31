<?php
namespace AdminModule;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}


	protected function startup()
	{
		parent::startup();

		$contentSecurityPolicy = $this->getContext()->getByType(\MichalSpacekCz\ContentSecurityPolicy::class);
		$header = $contentSecurityPolicy->getHeader();

		if ($header !== false) {
			$httpResponse = $this->getContext()->getByType(\Nette\Http\IResponse::class);
			$httpResponse->setHeader('Content-Security-Policy', $header);
		}

		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\UserManager::class);
		if (!$this->user->isLoggedIn()) {
			$authenticator->verifySignInAuthorization($this->getSession('admin')->knockKnock);
			$this->redirect('Sign:in');
		}
	}


	public function beforeRender()
	{
		$this->template->trackingCode = false;
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new \Bare\Next\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}


}
