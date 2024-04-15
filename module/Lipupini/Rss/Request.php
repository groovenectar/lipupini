<?php

namespace Module\Lipupini\Rss;

use Module\Lipupini\Collection;
use Module\Lipupini\Request\Incoming\Http;

class Request extends Http {
	public function initialize(): void {
		if (empty($this->system->request[Collection\Request::class]->name)) {
			return;
		}

		if (!preg_match('#^/rss/([^/]+)/([^/]+)-feed\.rss$#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $matches)) {
			return;
		}

		if ($matches[1] !== $matches[2]) {
			throw new Exception('RSS URL mismatch');
		}

		$this->renderRss();
		$this->system->shutdown = true;
	}

	protected function renderRss(): void {
		$dom = new \DOMDocument('1.0','UTF-8');
		$dom->formatOutput = true;
		$rss = $dom->createElement('rss');
		$rss->setAttribute('version', '2.0');
		$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		$rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
		$rss->setAttribute('xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
		$rss->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

		$channel = $dom->createElement('channel');
		$rss->appendChild($channel);
		$dom->appendChild($rss);

		$collectionName = $this->system->request[Collection\Request::class]->name;

		$channel->appendChild($dom->createElement('title', htmlentities($collectionName . '@' . $this->system->host)));
		$channel->appendChild($dom->createElement('description', htmlentities($collectionName . '@' . $this->system->host)));

		$linkSelf = $dom->createElement('atom:link');
		$linkSelf->setAttribute('rel', 'self');
		$linkSelf->setAttribute('href', $this->system->baseUri . 'rss/' . $collectionName . '/' . $collectionName . '-feed.rss');
		$linkSelf->setAttribute('type', 'application/rss+xml');
		$channel->appendChild($linkSelf);

		$link = $dom->createElement('link', htmlentities($this->system->baseUri . '@' . $collectionName));
		$channel->appendChild($link);

		$image = $dom->createElement('image');
		$image->appendChild($dom->createElement('url', htmlentities($this->system->staticMediaBaseUri . $collectionName . '/avatar.png')));
		$image->appendChild($dom->createElement('title', htmlentities($collectionName . '@' . $this->system->host)));
		$image->appendChild($dom->createElement('link', htmlentities($this->system->baseUri . '@' . $collectionName)));
		$channel->appendChild($image);

		$this->renderRssItems($dom, $channel, $collectionName);

		$this->system->responseType = 'application/rss+xml';
		$this->system->responseContent = $dom->saveXML();
	}

	public function renderRssItems(\DOMDocument $dom, \DOMElement $channel, string $collectionName): void {
		$collectionData = (new Collection\Utility($this->system))->getCollectionDataRecursive($collectionName);
		foreach ($collectionData as $filePath => &$metaData) {
			if (empty($metaData['date'])) {
				$metaData['date'] = (new \DateTime)
					->setTimestamp(filemtime($this->system->dirCollection . '/' . $collectionName . '/' . $filePath))
					->format(\DateTime::RSS);
			} else {
				$metaData['date'] = (new \DateTime($metaData['date']))
					->format(\DateTime::ISO8601);
			}
		} unset($metaData);

		$items = [];
		foreach ($collectionData as $filePath => $metaData) {
			$extension = pathinfo($filePath, PATHINFO_EXTENSION);

			if (in_array($extension, array_keys($this->system->mediaType['image']))) {
				$metaData['medium'] = 'image';
				$metaData['mime'] = $this->system->mediaType['image'][$extension];
				$metaData['cacheUrl'] = $this->system->staticMediaBaseUri . $collectionName . '/image/large/' . $filePath;
				$metaData['content'] = 	'<p>' . htmlentities($metaData['caption'] ?? $filePath) . '</p>' . "\n"
					. '<img src="' . $metaData['cacheUrl'] . '" alt="' . $filePath . '"/>';
			} else if (in_array($extension, array_keys($this->system->mediaType['video']))) {
				$metaData['medium'] = 'video';
				$metaData['mime'] = $this->system->mediaType['video'][$extension];
				$metaData['cacheUrl'] = $this->system->staticMediaBaseUri . $collectionName . '/video/' . $filePath;
				$thumbnail = !empty($metaData['thumbnail']) ? ' thumbnail="' . htmlentities($metaData['thumbnail']) . '"' : '';
				$metaData['content'] = 	'<p>' . htmlentities($metaData['caption'] ?? $filePath) . '</p>' . "\n"
					. '<video controls loop' . $thumbnail . '><source src="' . $metaData['cacheUrl'] . '" type="' . $metaData['mime'] . '"/></video>';
			} else if (in_array($extension, array_keys($this->system->mediaType['audio']))) {
				$metaData['medium'] = 'audio';
				$metaData['mime'] = $this->system->mediaType['audio'][$extension];
				$metaData['cacheUrl'] = $this->system->staticMediaBaseUri . $collectionName . '/audio/' . $filePath;
				$metaData['content'] = 	'<p>' . htmlentities($metaData['caption'] ?? $filePath) . '</p>' . "\n"
					. '<audio controls><source src="' . $metaData['cacheUrl'] . '" type="' . $metaData['mime'] . '"/></audio>';
			} else if (in_array($extension, array_keys($this->system->mediaType['text']))) {
				$metaData['medium'] = 'document';
				$metaData['mime'] = $this->system->mediaType['text'][$extension];
				$metaData['cacheUrl'] = $this->system->staticMediaBaseUri . $collectionName . '/text/' . $filePath . '.html';
				$metaData['content'] = 	'<p><a href="' . $metaData['cacheUrl'] . '">' . htmlentities($metaData['caption'] ?? $filePath) . '</a></p>';
			} else {
				throw new Exception('Unexpected file extension: ' . $extension);
			}

			$item = $dom->createElement('item');
			$item->appendChild($dom->createElement('guid', htmlentities($this->system->baseUri . '@' . $collectionName . '/' . $filePath . '.html')));
			$item->appendChild($dom->createElement('title', htmlentities($filePath)));

			$link = $dom->createElement('link', htmlentities($this->system->baseUri . '@' . $collectionName . '/' . $filePath . '.html'));
			$item->appendChild($link);

			$item->appendChild($dom->createElement('description', htmlentities($filePath)));

			$content = $dom->createElement('content:encoded');
			$content->appendChild($dom->createCDATASection($metaData['content']));
			$item->appendChild($content);

			$media = $dom->createElement('media:content');
			$media->setAttribute('url', $metaData['cacheUrl']);
			$media->setAttribute('type', $metaData['mime']);
			$media->setAttribute('medium', $metaData['medium']);
			$item->appendChild($media);

			$enclosure = $dom->createElement('enclosure');
			$enclosure->setAttribute('url', $metaData['cacheUrl']);
			$enclosure->setAttribute('length', '0');
			$enclosure->setAttribute('type', $metaData['mime']);
			$item->appendChild($enclosure);

			$item->appendChild($dom->createElement('pubDate', htmlentities($metaData['date'])));
			$items[] = $item;
		}

		foreach ($items as $item) {
			$channel->append($item);
		}
	}
}
