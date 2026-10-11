<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\PhotoCraftIntegration\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\PhotoCraftIntegration\Listener\LoadFilesScripts;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'integration_photocraft';

	/** User and app config key: open images in PhotoCraft when they are clicked in Files. */
	public const OPEN_BY_DEFAULT = 'open_by_default';

	/** The file types PhotoCraft opens: photo, Photoshop or PhotoCraft. Keep in sync with src/files.ts. */
	public const MIME_TYPES = [
		'image/png',
		'image/jpeg',
		'image/tiff',
		'image/webp',
		'image/gif',
		'image/bmp',
		'application/x-photoshop',
		'image/vnd.adobe.photoshop',
		'image/heic',
		'image/heif',
		'image/x-dcraw',
		'image/tga',
		'image/x-icon',
	];

	/**
	 * Extensions PhotoCraft opens whatever MIME type Nextcloud gives them (formats Nextcloud doesn't
	 * know show up as application/octet-stream). Lower case, without the dot.
	 */
	public const EXTENSIONS = [
		'pcraft',
		'psd',
		'psb',
		'psdt',
		'pdn',
		'ora',
		'exr',
		'hdr',
		'qoi',
		'pbm',
		'pgm',
		'ppm',
		'pam',
		'pfm',
		'tga',
		'afphoto',
	];

	/** Whether PhotoCraft opens a file with this name and MIME type. */
	public static function opens(string $name, string $mimeType): bool {
		return in_array($mimeType, self::MIME_TYPES, true)
			|| in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
	}

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadFilesScripts::class);
	}

	public function boot(IBootContext $context): void {
	}
}
