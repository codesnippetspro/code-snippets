import React from 'react'
import { __, sprintf } from '@wordpress/i18n'

const FILE_SIZE_FRACTION_DIGITS = 2

const formatFileSize = (bytes: number): string => {
	if (0 === bytes) {
		// translators: %f: file size in bytes.
		return sprintf(__('%f Bytes', 'code-snippets'), 0);
	}

	const k = 1024
	const i = Math.floor(Math.log(bytes) / Math.log(k))

	const n = parseFloat((bytes / Math.pow(k, i)).toFixed(FILE_SIZE_FRACTION_DIGITS))

	switch (i) {
		case 0:
			// translators: %f: file size in bytes.
			return sprintf(__('%f Bytes', 'code-snippets'), n);

		case 1:
			// translators: %f: file size in kilobytes.
			return sprintf(__('%f KB', 'code-snippets'), n);

		case 2:
			// translators: %f: file size in megabytes.
			return sprintf(__('%f MB', 'code-snippets'), n);

		case 3:
		default:
			// translators: %f: file size in gigabytes.
			return sprintf(__('%f GB', 'code-snippets'), n);
	}
}

export interface SelectedFilesListProps {
	files: FileList
	onRemoveFile: (index: number) => void
}

export const SelectedFilesList: React.FC<SelectedFilesListProps> = ({ files, onRemoveFile }) =>
	<div className="selected-files">
		<h3>
			{sprintf(
				// translators: %d: number of selected files.
				__('Selected files: (%d)', 'code-snippets'),
				files.length
			)}
		</h3>

		<div className="selected-files-list">
			{Array.from(files).map((file, index) =>
					<div key={`${file.name}-${file.size}-${file.lastModified}`}>
						<div className="selected-file-details">
							<span className="file-icon">📄</span>
							<div>
								<div><strong>{file.name}</strong></div>
								<div className="file-size">{formatFileSize(file.size)}</div>
							</div>
						</div>

						<button
							type="button"
							title={__('Remove file', 'code-snippets')}
							onClick={event => {
								event.stopPropagation()
								onRemoveFile(index)
							}}
						>
							✕
						</button>
					</div>
			)}
		</div>
	</div>
