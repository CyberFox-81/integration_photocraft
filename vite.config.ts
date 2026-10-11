/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'node:path'

export default createAppConfig({
	files: join(import.meta.dirname, 'src', 'files.ts'),
	editor: join(import.meta.dirname, 'src', 'editor.ts'),
}, {
	inlineCSS: { relativeCSSInjection: true },
})
