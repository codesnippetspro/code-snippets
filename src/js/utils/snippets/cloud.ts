import { isLicensed } from '../screen'
import { isProSnippet } from './snippets'
import type { CloudSnippetSchema } from '../../types/schema/CloudSnippetSchema'

export const isCloudSnippetDownloadable = (
	snippet: Pick<CloudSnippetSchema, 'scope' | 'local_id'>
): boolean =>
	!snippet.local_id && (!isProSnippet(snippet) || isLicensed())
