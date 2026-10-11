<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\PhotoCraftIntegration\Service;

use OCA\PhotoCraftIntegration\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;

/**
 * Whether clicking a file in Files opens it in PhotoCraft. A user's own choice wins; otherwise the
 * admin default applies (`occ config:app:set integration_photocraft open_by_default --value=true --type=boolean`).
 */
class Preferences {
	public function __construct(
		private IUserConfig $userConfig,
		private IAppConfig $appConfig,
	) {
	}

	public function openByDefault(string $userId): bool {
		$fallback = $this->appConfig->getValueBool(Application::APP_ID, Application::OPEN_BY_DEFAULT);
		if (!$this->userConfig->hasKey($userId, Application::APP_ID, Application::OPEN_BY_DEFAULT)) {
			return $fallback;
		}
		return $this->userConfig->getValueBool($userId, Application::APP_ID, Application::OPEN_BY_DEFAULT, $fallback);
	}

	public function setOpenByDefault(string $userId, bool $enabled): void {
		$this->userConfig->setValueBool($userId, Application::APP_ID, Application::OPEN_BY_DEFAULT, $enabled);
	}
}
