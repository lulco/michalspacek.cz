<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\PrivateProperty;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class HttpInputTest extends TestCase
{

	public function __construct(
		private readonly Request $request,
		private readonly HttpInput $httpInput,
	) {
	}


	public function testGetCookieString(): void
	{
		Assert::null($this->httpInput->getCookieString('foo'));
		$this->request->setCookie('foo', 'bar');
		Assert::same('bar', $this->httpInput->getCookieString('foo'));
		PrivateProperty::setValue($this->request, 'cookies', ['waldo' => ['quux' => 'foobar']]);
		Assert::null($this->httpInput->getCookieString('waldo'));
	}


	public function testGetPostString(): void
	{
		Assert::null($this->httpInput->getPostString('foo'));
		$this->request->setPost('foo', 'bar');
		Assert::same('bar', $this->httpInput->getPostString('foo'));
		$this->request->setPost('waldo', ['quux' => 'foobar']);
		Assert::null($this->httpInput->getPostString('waldo'));
	}


	public function testGetPostArray(): void
	{
		Assert::null($this->httpInput->getPostArray('foo'));
		$this->request->setPost('foo', 'bar');
		Assert::null($this->httpInput->getPostArray('foo'));
		$this->request->setPost('waldo', ['quux' => 'foobar']);
		Assert::same(['quux' => 'foobar'], $this->httpInput->getPostArray('waldo'));
	}

}

$runner->run(HttpInputTest::class);
