import React from 'react';
import { __ } from '@wordpress/i18n';
import { BlockControls } from '@wordpress/block-editor';
import { MenuItem } from '@wordpress/components';
import { withSelect } from '@wordpress/data';

export interface ResetButtonProps {
	onClick: () => void
}

export const ResetButton: React.FC<ResetButtonProps> = ({ onClick }) =>
	<BlockControls>
		<MenuItem icon="image-rotate" title={__('Choose a different snippet', 'code-snippets')} onClick={onClick} />
	</BlockControls>;

/**
 * Fetch a list of snippet information using the REST API.
 *
 * @return SnippetData[] List of snippets.
 */
export const fetchSnippets = () =>
	withSelect(select => ({ snippets: select('code-snippets/snippets-data').receiveSnippetsData() }));
