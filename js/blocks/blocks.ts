import { registerBlockType } from '@wordpress/blocks';
import { SourceBlock } from './source';
import { ContentBlock } from './content';

registerBlockType('code-snippets/source', SourceBlock);
registerBlockType('code-snippets/content', ContentBlock);
