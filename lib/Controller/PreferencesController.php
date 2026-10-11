<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\PhotoCraftIntegration\Controller;

use OCA\PhotoCraftIntegration\AppInfo\Application;
use OCA\PhotoCraftIntegration\Service\Preferences;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class PreferencesController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private Preferences $preferences,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Whether clicking a file in Files opens it in PhotoCraft (for the current user).
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/preferences')]
	public function update(bool $openByDefault): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse([], Http::STATUS_UNAUTHORIZED);
		}
		$this->preferences->setOpenByDefault($user->getUID(), $openByDefault);
		return new JSONResponse(['openByDefault' => $openByDefault]);
	}
}
