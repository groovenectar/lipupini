<?php

namespace Module\Esunview\Request\Html;

use Module\Esunview\Payment\Gateway;
use Module\Lipupini\Collection\Trait\CollectionRequest;
use Module\Lipupini\Exception;
use Module\Lipupini\Request;

class GiftRequest extends Request\Html {
	public static array $words = [
		'reward',
		'contribution',
		'tip',
		'donation',
		'present',
		'offering',
		'favor',
		'gift',
		'payment',
		'transaction',
	];
	public string $word = '';
	public string $collectionFolder = '';

	use CollectionRequest;

	public function initialize(): void  {
		if (!preg_match('#^' . preg_quote($this->system->baseUriPath) . '(g|' . implode('|', static::$words) . ')/#', $_SERVER['REQUEST_URI_DECODED'], $matches)) return;
		$this->collectionNameFromSegment(2);
		// To be considered a folder request, there must not be an extension
		if (pathinfo($_SERVER['REQUEST_URI_DECODED'], PATHINFO_EXTENSION)) return;
		$this->word = $matches[1];
		if ($this->word !== 'g' && !in_array($this->word, static::$words)) {
			throw new Exception('Word?');
		}

		$this->collectionFolder = preg_replace(
			'#^/' . preg_quote($this->word) . '/' . preg_quote($this->collectionName) . '/?#', '',
			$_SERVER['REQUEST_URI_DECODED']
		);

		$filesystemPath = rtrim($this->system->dirCollection . '/' . $this->collectionName . '/' . $this->collectionFolder, '/');

		$gateway = new Gateway($this->system);

		if (
			!$gateway::gatedFolder($filesystemPath) ||
			!$gateway::gatedCollectionFolderClosed($this->collectionName, $this->collectionFolder)
		) {
			header('Location: ' . $this->system->baseUriPath . '@' . rtrim($this->collectionName . '/' . urlencode($this->collectionFolder), '/'));
		}

		if (!empty($_GET['provider'])) {
			$word = isset($_GET['word']) && in_array($_GET['word'], static::$words) ? $_GET['word'] : $this->word;
			$gateway->redirectToPaymentFolder(
				$this->collectionName,
				$this->collectionFolder,
				description: ucfirst($word) . ': Thank you!'
			);
			exit();
		}

		$this->system->shutdown = true;
		$this->pageTitle = ucfirst($this->word) . '@' . $this->system->host;
		$this->htmlHead = '<script>let words = ' . json_encode(static::$words) . ';</script>';
		$this->addStyle('/css/Global.css');
		$this->addStyle('/css/Gift.css');
		$this->renderHtml();
		$this->system->responseType = 'text/html';
		$this->system->shutdown = true;
	}

	public function renderHtml(): void {
		ob_start();
		header('Content-type: text/html');
		require(__DIR__ . '/../../Html/Gift.php');
		$this->system->responseContent = ob_get_clean();
	}
}
