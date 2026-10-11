<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\PhotoCraftIntegration\Controller;

use OCA\PhotoCraftIntegration\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

/**
 * The page inside the editor's iframe: a canvas and the PhotoCraft WebAssembly app. Its policy
 * allows compiling WebAssembly, which the editor page itself does not need.
 */
class FrameController extends Controller {
	public function __construct(
		IRequest $request,
		private IURLGenerator $urlGenerator,
		private IFactory $l10nFactory,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/frame')]
	public function index(): TemplateResponse {
		$response = new TemplateResponse(Application::APP_ID, 'frame', [
			'bootUrl' => $this->bootUrl(),
			'language' => $this->l10nFactory->findLanguage(Application::APP_ID),
		], TemplateResponse::RENDER_AS_BLANK);

		$csp = new ContentSecurityPolicy();
		$csp->allowEvalWasm(true);
		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * web/boot.js, versioned by its content. It imports the PhotoCraft module and wasm by
	 * content-hashed URLs, so upgrades are never served from a stale cache.
	 */
	private function bootUrl(): string {
		$url = $this->urlGenerator->linkTo(Application::APP_ID, 'web/boot.js');
		$file = __DIR__ . '/../../web/boot.js';
		$hash = is_file($file) ? md5_file($file) : false;
		return $hash === false ? $url : $url . '?v=' . substr($hash, 0, 12);
	}
}
