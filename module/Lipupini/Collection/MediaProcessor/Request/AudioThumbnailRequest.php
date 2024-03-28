<?php

namespace Module\Lipupini\Collection\MediaProcessor\Request;

use Module\Lipupini\Collection;

class AudioThumbnailRequest extends MediaProcessorRequest {
	use Collection\MediaProcessor\Trait\CacheSymlink;

	public function initialize(): void {
		if (!preg_match('#^/c/([^/]+)/thumbnail/(.+\.(' . implode('|', array_keys($this->system->mediaType['audio'])) . ')\.(' . implode('|', array_keys($this->system->mediaType['image'])) . '))$#', $_SERVER['REQUEST_URI'], $matches)) {
			return;
		}

		// If the URL has matched, we're going to shutdown after this module returns no matter what
		$this->system->shutdown = true;

		$collectionFolderName = $matches[1];
		$thumbnailPath = urldecode($matches[2]);
		$thumbnailExtension = $matches[4];
		$audioPath = preg_replace('#\.' . $thumbnailExtension . '$#', '', $thumbnailPath);

		(new Collection\Utility($this->system))->validateCollectionFolderName($collectionFolderName);

		$this->serve(
			Collection\MediaProcessor\AudioThumbnail::cacheSymlinkAudioThumbnail($this->system, $collectionFolderName, $audioPath),
			$this->system->mediaType['image'][$thumbnailExtension]
		);
	}
}
