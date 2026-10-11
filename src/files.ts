/**
 * SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileAction, INode } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { DefaultType, FileType, Permission, registerFileAction } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { isPublicShare } from '@nextcloud/sharing/public'
import { appIcon } from './icon.ts'

const openByDefault = loadState<boolean>('integration_photocraft', 'openByDefault', false)

/** The file types PhotoCraft opens; keep in sync with Application::MIME_TYPES. */
const MIME_TYPES = new Set([
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
])

/** Extensions PhotoCraft opens whatever their MIME type; keep in sync with Application::EXTENSIONS. */
const EXTENSIONS = new Set(['pcraft', 'psd', 'psb', 'psdt', 'pdn', 'ora', 'exr', 'hdr', 'qoi', 'pbm', 'pgm', 'ppm', 'pam', 'pfm', 'tga', 'afphoto'])

/**
 * A file PhotoCraft opens, in the user's own file tree (not the trash bin, versions or a
 * public share).
 *
 * @param node - The file to check
 */
function isEditableFile(node: INode | undefined): node is INode {
	return node !== undefined
		&& node.type === FileType.File
		&& (MIME_TYPES.has(node.mime ?? '') || EXTENSIONS.has((node.extension ?? '').replace(/^\./, '').toLowerCase()))
		&& node.fileid !== undefined
		&& (node.permissions & Permission.READ) !== 0
		&& node.root?.startsWith('/files/') === true
}

const editAction: IFileAction = {
	id: 'photocraft-edit',
	displayName: () => t('integration_photocraft', 'Edit in PhotoCraft'),
	iconSvgInline: () => appIcon,

	enabled({ nodes }) {
		return getCurrentUser() !== null && !isPublicShare() && nodes.length === 1 && isEditableFile(nodes[0])
	},

	async exec({ nodes }) {
		const fileId = nodes[0].fileid
		if (fileId === undefined) {
			return false
		}
		window.location.assign(generateUrl('/apps/integration_photocraft/edit/{fileId}', { fileId }))
		return null
	},

	// Ahead of the Viewer's and office apps' default actions when PhotoCraft is the user's default.
	...(openByDefault ? { default: DefaultType.DEFAULT, order: -1 } : { order: 20 }),
}

registerFileAction(editAction)
