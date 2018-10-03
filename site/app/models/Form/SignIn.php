<?php
namespace MichalSpacekCz\Form;

/**
 * Sign-in form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SignIn extends ProtectedForm
{

	use Controls\SignIn;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addSignIn($this);
	}

}
