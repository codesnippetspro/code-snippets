import { registerBlockType } from '@wordpress/blocks'
import { SOURCE_BLOCK, SourceBlock } from './source'
import { CONTENT_BLOCK, ContentBlock } from './content'

registerBlockType(SOURCE_BLOCK, SourceBlock)
registerBlockType(CONTENT_BLOCK, ContentBlock)
