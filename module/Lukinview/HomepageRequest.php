<?php

namespace Module\Lukinview;

use Module\Lipupini\Request\Http;

class HomepageRequest extends Http {
	public string $pageTitle = '';

	public function initialize(): void  {
		if ($_SERVER['REQUEST_URI'] !== $this->system->baseUriPath) {
			return;
		}

		if (!$this->validateRequestMimeTypes('HTTP_ACCEPT', ['text/html'])) {
			return;
		}

		$this->pageTitle = 'Homepage@' . $this->system->host;

		header('Content-type: text/html');
		$this->renderHtml();

		$this->system->shutdown = true;
	}

	public function renderHtml(): void {
		ob_start();
		header('Content-type: text/html');
		require(__DIR__ . '/Html/Homepage.php');
		$this->system->responseContent = ob_get_clean();
	}

	public function getLocalCollections() {
		$dir = new \DirectoryIterator($this->system->dirCollection);
		$localCollections = [];
		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDir() || $fileinfo->isDot() || $fileinfo->getFilename()[0] === '.') {
				continue;
			}

			if (!is_dir($fileinfo->getPathname() . '/.lipupini')) {
				continue;
			}

			$localCollections[] = $fileinfo->getFilename();
		}

		return $localCollections;
	}
}