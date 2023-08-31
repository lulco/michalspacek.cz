<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use Nette\Utils\Html;

interface ArticleWithSummary
{

	public function hasSummary(): bool;


	public function getSummary(): ?Html;

}
