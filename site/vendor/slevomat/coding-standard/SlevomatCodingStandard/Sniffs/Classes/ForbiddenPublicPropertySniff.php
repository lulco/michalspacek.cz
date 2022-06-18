<?php declare(strict_types = 1);

namespace SlevomatCodingStandard\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\PropertyHelper;
use SlevomatCodingStandard\Helpers\StringHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use function array_merge;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_VAR;
use const T_VARIABLE;

final class ForbiddenPublicPropertySniff implements Sniff
{

	public const CODE_FORBIDDEN_PUBLIC_PROPERTY = 'ForbiddenPublicProperty';

	/**
	 * @return array<int, (int|string)>
	 */
	public function register(): array
	{
		return [T_VARIABLE];
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 * @param int $variablePointer
	 */
	public function process(File $file, $variablePointer): void
	{
		if (!PropertyHelper::isProperty($file, $variablePointer)) {
			return;
		}

		// skip Sniff classes, they have public properties for configuration (unfortunately)
		if ($this->isSniffClass($file, $variablePointer)) {
			return;
		}

		$scopeModifierToken = $this->getPropertyScopeModifier($file, $variablePointer);
		if ($scopeModifierToken['code'] === T_PROTECTED || $scopeModifierToken['code'] === T_PRIVATE) {
			return;
		}

		$errorMessage = 'Do not use public properties. Use method access instead.';
		$file->addError($errorMessage, $variablePointer, self::CODE_FORBIDDEN_PUBLIC_PROPERTY);
	}

	private function isSniffClass(File $file, int $position): bool
	{
		$classTokenPosition = ClassHelper::getClassPointer($file, $position);
		$classNameToken = ClassHelper::getName($file, $classTokenPosition);

		return StringHelper::endsWith($classNameToken, 'Sniff');
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
	 * @return mixed[]
	 */
	private function getPropertyScopeModifier(File $file, int $position): array
	{
		$scopeModifierPosition = TokenHelper::findPrevious($file, array_merge([T_VAR], Tokens::$scopeModifiers), $position - 1);

		return $file->getTokens()[$scopeModifierPosition];
	}

}
