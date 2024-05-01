<?php

namespace Module\Esunview;

class State extends \Module\Lipupini\State {
	// `$stripeKey` needs to be in an environment variable eventually
	public function __construct(public string $contactEmail, public string $stripeKey, ...$props)
	{
		parent::__construct(...$props);
	}
}
