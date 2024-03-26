<?php

namespace Module\Lipupini\Collection\MediaProcessor;

use Module\Lipupini\Collection\Cache;
use Module\Lipupini\State;

class VideoPoster {
	public static function cacheSymlinkVideoPoster(State $systemState, string $collectionFolderName, string $videoPath, bool $echoStatus = false): void {
		$cache = new Cache($systemState, $collectionFolderName);
		$posterPath = $videoPath . '.png';
		$posterPathFull = $systemState->dirCollection . '/' . $collectionFolderName . '/.lipupini/video-poster/' . $posterPath;
		$fileCachePath = $cache->path() . '/video-poster/' . $posterPath;

		$cache::webrootCacheSymlink($systemState, $collectionFolderName, $echoStatus);

		// One tradeoff with doing this first is that the file can be deleted from the collection's `video-poster` folder but still show if it stays in `cache`
		// The benefit is that it won't try to use `ffmpeg` and grab the frame if it hasn't yet, so it's potentially faster to check this way
		if (file_exists($fileCachePath)) {
			return;
		}

		if ($echoStatus) {
			echo 'Symlinking video poster to cache for `' . $posterPath . '`...' . "\n";
		}

		if (!is_dir(pathinfo($fileCachePath, PATHINFO_DIRNAME))) {
			mkdir(pathinfo($fileCachePath, PATHINFO_DIRNAME), 0755, true);
		}

		static::saveMiddleFramePng($systemState, $collectionFolderName, $videoPath, $posterPath, $echoStatus);

		// After grabbing the middle frame, `$posterPathFull` should exist
		// And if `$fileCachePath` is already there then we don't need to create it so return
		if (!file_exists($posterPathFull) || file_exists($fileCachePath)) {
			return;
		}

		// Link the poster path to the collection's cache
		$cache::createSymlink(
			$posterPathFull,
			$fileCachePath
		);
	}

	public static function saveMiddleFramePng(State $systemState, string $collectionFolderName, string $videoPath, string $posterPath, bool $echoStatus = false) {
		if (!static::hasFfmpeg()) {
			return false;
		}

		$collectionPath = $systemState->dirCollection . '/' . $collectionFolderName;
		$posterPathFull = $systemState->dirCollection . '/' . $collectionFolderName . '/.lipupini/video-poster/' . $posterPath;

		if (file_exists($posterPathFull)) {
			return true;
		}

		if (!is_dir(pathinfo($posterPathFull, PATHINFO_DIRNAME))) {
			mkdir(pathinfo($posterPathFull, PATHINFO_DIRNAME), 0755, true);
		}

		if ($echoStatus) {
			echo 'Saving video poster for `' . $videoPath . '`... ';
		}

		exec($systemState->dirRoot . '/bin/ffmpeg-video-poster.php ' . escapeshellarg($collectionPath . '/' . $videoPath) . ' ' . escapeshellarg($posterPathFull) . ' > /dev/null 2>&1', $output, $returnCode);

		if ($returnCode !== 0) {
			if ($echoStatus) {
				echo 'ERROR';
			}
			return false;
		}

		return true;
	}

	// https://beamtic.com/if-command-exists-php
	public static function hasFfmpeg() {
		$commandName = 'ffmpeg';
		$testMethod = (false === stripos(PHP_OS, 'win')) ? 'command -v' : 'where';
		return null !== shell_exec($testMethod . ' ' . $commandName);
	}
}