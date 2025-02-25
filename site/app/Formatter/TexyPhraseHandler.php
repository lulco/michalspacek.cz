<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\Articles\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\Formatter\Exceptions\UnexpectedHandlerInvocationReturnType;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\TrainingLocales;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

class TexyPhraseHandler
{

	private const TRAINING_ACTION = 'Www:Trainings:training';
	private const COMPANY_TRAINING_ACTION = 'Www:CompanyTrainings:training';


	public function __construct(
		private readonly Application $application,
		private readonly TrainingLocales $trainingLocales,
		private readonly LocaleLinkGeneratorInterface $localeLinkGenerator,
		private readonly BlogPostLocaleUrls $blogPostLocaleUrls,
		private readonly Translator $translator,
	) {
	}


	/**
	 * @param HandlerInvocation $invocation handler invocation
	 * @param string $phrase
	 * @param string $content
	 * @param Modifier $modifier
	 * @param Link|null $link
	 * @return HtmlElement<HtmlElement|string>|string|false
	 * @throws InvalidLinkException
	 * @throws UnexpectedHandlerInvocationReturnType
	 */
	public function solve(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|false
	{
		if (!$link) {
			return $this->proceed($invocation);
		}

		$localeRegExp = '([a-z]{2}_[A-Z]{2})';
		$presenter = $this->application->getPresenter();
		if (!$presenter instanceof Presenter) {
			throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", Presenter::class, get_debug_type($presenter)));
		}
		$url = $link->URL ?? $link->raw;

		// "title":[link:Module:Presenter:action params]
		if (str_starts_with($url, 'link:')) {
			$link->URL = $this->getLink(substr($url, 5), $this->translator->getDefaultLocale());
		}

		// "title":[link-en_US:Module:Presenter:action params]
		if (str_starts_with($url, 'link-') && preg_match("/^link-{$localeRegExp}:(.*)\\z/", $url, $matches)) {
			$link->URL = $this->getLink($matches[2], $matches[1]);
		}

		// "title":[blog:post#fragment]
		if (str_starts_with($url, 'blog:')) {
			$link->URL = $this->getBlogLink(substr($url, 5), $this->translator->getDefaultLocale());
		}

		// "title":[blog-en_US:post#fragment]
		if (str_starts_with($url, 'blog-') && preg_match("/^blog-{$localeRegExp}:(.*)\\z/", $url, $matches)) {
			$link->URL = $this->getBlogLink($matches[2], $matches[1]);
		}

		// "title":[inhouse-training:training]
		if (str_starts_with($url, 'inhouse-training:')) {
			$args = $this->trainingLocales->getLocaleActions(substr($url, 17))[$this->translator->getDefaultLocale()];
			$link->URL = $presenter->link('//:' . self::COMPANY_TRAINING_ACTION, $args);
		}

		// "title":[training:training]
		if (str_starts_with($url, 'training:')) {
			$texy = $invocation->getTexy();
			$name = substr($url, 9);
			$name = $this->trainingLocales->getLocaleActions($name)[$this->translator->getDefaultLocale()];
			$link->URL = $presenter->link('//:' . self::TRAINING_ACTION, $name);
			$el = HtmlElement::el();
			$trainingLink = $texy->phraseModule->solve($invocation, $phrase, $content, $modifier, $link);
			if ($trainingLink) {
				$el->add($trainingLink);
				$el->add($texy->protect($this->getTrainingSuffix($name), $texy::CONTENT_TEXTUAL));
				return $el;
			}
		}

		return $this->proceed($invocation);
	}


	private function getLink(string $url, string $locale): string
	{
		$args = Strings::split($url, '/[\s,]+/');
		$action = array_shift($args);
		if (Arrays::contains([self::TRAINING_ACTION, self::COMPANY_TRAINING_ACTION], $action)) {
			$args = [$this->trainingLocales->getLocaleActions($args[0])[$locale]];
		}
		return $this->getLinkWithParams($action, [$locale => $args], $locale);
	}


	private function getBlogLink(string $url, string $locale): string
	{
		$args = explode('#', $url);
		$fragment = (empty($args[1]) ? '' : "#{$args[1]}");

		$params = [];
		foreach ($this->blogPostLocaleUrls->get($args[0]) as $post) {
			$params[$post->locale] = ['slug' => $post->slug, 'preview' => ($post->needsPreviewKey() ? $post->previewKey : null)];
		}
		if (!$params) {
			throw new ShouldNotHappenException("The blog links array should not be empty, maybe the linked blog post '{$url}' is missing?");
		}
		return $this->getLinkWithParams("Www:Post:default{$fragment}", $params, $locale);
	}


	/**
	 * @param non-empty-array<string, array<string, string|null>> $params
	 */
	private function getLinkWithParams(string $destination, array $params, string $locale): string
	{
		$defaultParams = current($params);
		$this->localeLinkGenerator->setDefaultParams($params, $defaultParams);
		return $this->localeLinkGenerator->allLinks($destination, $params)[$locale];
	}


	/**
	 * @param string $training Training name
	 * @return string
	 */
	private function getTrainingSuffix(string $training): string
	{
		$el = Html::el()
			->addHtml(Html::el()->setText(' '))
			->addHtml(Html::el('small')->setText(sprintf('(**%s:%s**)', TrainingDateTexyFormatterPlaceholder::getPlaceholder(), $training)));
		return $el->render();
	}


	/**
	 * @throws UnexpectedHandlerInvocationReturnType
	 */
	private function proceed(HandlerInvocation $invocation): HtmlElement|string|false
	{
		$result = $invocation->proceed();
		if (!$result instanceof HtmlElement && !is_string($result) && $result !== false) {
			throw new UnexpectedHandlerInvocationReturnType($result);
		}
		return $result;
	}

}
