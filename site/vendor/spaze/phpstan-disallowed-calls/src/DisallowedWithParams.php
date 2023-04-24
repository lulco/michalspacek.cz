<?php
declare(strict_types = 1);

namespace Spaze\PHPStan\Rules\Disallowed;

use Spaze\PHPStan\Rules\Disallowed\Params\DisallowedCallParam;

interface DisallowedWithParams extends Disallowed
{

	/**
	 * @return array<int|string, DisallowedCallParam>
	 */
	public function getAllowParamsInAllowed(): array;


	/**
	 * @return array<int|string, DisallowedCallParam>
	 */
	public function getAllowParamsAnywhere(): array;


	/**
	 * @return array<int|string, DisallowedCallParam>
	 */
	public function getAllowExceptParamsInAllowed(): array;


	/**
	 * @return array<int|string, DisallowedCallParam>
	 */
	public function getAllowExceptParams(): array;

}
