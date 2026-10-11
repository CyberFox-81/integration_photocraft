<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\PhotoCraftIntegration\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\PhotoCraftIntegration\AppInfo\Application;
use OCA\PhotoCraftIntegration\Service\Preferences;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Util;

/**
 * Adds the "Edit in PhotoCraft" action to the Files app.
 *
 * @template-implements IEventListener<LoadAdditionalScriptsEvent>
 */
class LoadFilesScripts implements IEventListener {
	public function __construct(
		private IAppManager $appManager,
		private IUserSession $userSession,
		private IInitialState $initialState,
		private Preferences $preferences,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}
		$user = $this->userSession->getUser();
		if ($user === null || !$this->appManager->isEnabledForUser(Application::APP_ID, $user)) {
			return;
		}

		$this->initialState->provideInitialState('openByDefault', $this->preferences->openByDefault($user->getUID()));
		Util::addInitScript(Application::APP_ID, Application::APP_ID . '-files');
	}
}
