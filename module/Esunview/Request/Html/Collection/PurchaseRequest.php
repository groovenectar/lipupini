<?php

namespace Module\Esunview\Request\Html\Collection;

use Module\Esunview\Payment\Gateway;
use Module\Lipupini\Collection;
use Module\Lipupini\Request;

class PurchaseRequest extends Request\Queued {
	use Collection\Trait\CollectionRequest;

	public string|null $collectionFilePath = null;

	public function initialize(): void {
		if (!preg_match('#^' . preg_quote($this->system->baseUriPath) . 'purchase/?#', $_SERVER['REQUEST_URI_DECODED'])) return;
		$this->collectionNameFromSegment(2);

		$this->collectionFilePath =
			preg_replace(
				'#^/purchase/' . preg_quote($this->collectionName) . '/?#', '',
				$_SERVER['REQUEST_URI_DECODED'] // Automatically strips query string
			)
		;

		// Make sure file in collection exists before proceeding
		if (!file_exists($this->system->dirCollection . '/' . $this->collectionName . '/' . $this->collectionFilePath)) {
			return;
		}

		(new Gateway($this->system))->redirectToPaymentItem($this->collectionName, $this->collectionFilePath);
	}
}
