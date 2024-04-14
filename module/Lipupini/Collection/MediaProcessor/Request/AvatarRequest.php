<?php

namespace Module\Lipupini\Collection\MediaProcessor\Request;

use Module\Lipupini\Collection;
use Module\Lipupini\Collection\MediaProcessor\Avatar;

class AvatarRequest extends MediaProcessorRequest {
	public function initialize(): void {
		$avatarMimeTypes = [
			'png' => 'image/png',
		];

		if (!preg_match('#^' . preg_quote(static::relativeStaticCachePath($this->system)) . '([^/]+)/avatar\.(' . implode('|', array_keys($avatarMimeTypes)) . ')$#', $_SERVER['REQUEST_URI'], $matches)) {
			return;
		}

		// If the URL has matched, we're going to shutdown after this module returns no matter what
		$this->system->shutdown = true;

		$collectionName = $matches[1];
		$extension = $matches[2];

		(new Collection\Utility($this->system))->validateCollectionName($collectionName);

		$avatarPath = $this->system->dirCollection . '/' . $collectionName . '/.lipupini/avatar.png';

		$this->serve(
			Avatar::cacheSymlinkAvatar($this->system, $collectionName, $avatarPath),
			$avatarMimeTypes[$extension]
		);
	}
}
