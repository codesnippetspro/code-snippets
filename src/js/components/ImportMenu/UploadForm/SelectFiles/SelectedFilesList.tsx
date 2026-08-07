import React from 'react'
import { __, sprintf } from '@wordpress/i18n'
import type { UploadedFile } from './UploadButton'

const FILE_SIZE_FRACTION_DIGITS = 2

const LOG_BYTES = 0
const LOG_KB = 1
const LOG_MB = 2
const LOG_GB = 3

const formatFileSize = (bytes: number): string => {
	if (0 === bytes) {
		// translators: %f: file size in bytes.
		return sprintf(__('%f Bytes', 'code-snippets'), 0)
	}

	const k = 1024
	const i = Math.min(Math.floor(Math.log(bytes) / Math.log(k)), LOG_GB)

	const n = parseFloat((bytes / Math.pow(k, i)).toFixed(FILE_SIZE_FRACTION_DIGITS))

	switch (i) {
		case LOG_BYTES:
			// translators: %f: file size in bytes.
			return sprintf(__('%f Bytes', 'code-snippets'), n)

		case LOG_KB:
			// translators: %f: file size in kilobytes.
			return sprintf(__('%f KB', 'code-snippets'), n)

		case LOG_MB:
			// translators: %f: file size in megabytes.
			return sprintf(__('%f MB', 'code-snippets'), n)

		default:
		case LOG_GB:
			// translators: %f: file size in gigabytes.
			return sprintf(__('%f GB', 'code-snippets'), n)
	}
}

export interface SelectedFilesListProps {
	files: UploadedFile[]
	onRemoveFile: (file: UploadedFile) => void
}

export const SelectedFilesList: React.FC<SelectedFilesListProps> = ({ files, onRemoveFile }) =>
	<div className="selected-files">
		<h3>
			{// translators: %d: number of selected files.
				sprintf(__('Selected files: (%d)', 'code-snippets'), files.length)}
		</h3>

		<div className="selected-files-list">
			{files.map(file =>
				<div key={file.id}>
					<div className="selected-file-details">
						<span className="file-icon" aria-hidden="true">📄</span>
						<div>
							<div><strong>{file.name}</strong></div>
							<div className="file-size">{formatFileSize(file.file.size)}</div>
						</div>
					</div>

					<button
						type="button"
						aria-label={
							sprintf(
								// translators: %s: file name.
								__('Remove file %s', 'code-snippets'),
								file.name
							)
						}
						onClick={event => {
							event.stopPropagation()
							onRemoveFile(file)
						}}
					>
						<span aria-hidden="true">✕</span>
					</button>
				</div>
			)}
		</div>
	</div>
