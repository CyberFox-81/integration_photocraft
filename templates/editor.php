<?php
/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="photocraft-editor" data-name="<?php p($_['name']); ?>">
	<noscript><?php p($l->t('PhotoCraft needs JavaScript and WebAssembly.')); ?></noscript>
</div>
