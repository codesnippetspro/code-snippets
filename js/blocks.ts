import { registerBlockType } from '@wordpress/blocks'
import { SOURCE_BLOCK, SourceBlock } from './blocks/source'
import { CONTENT_BLOCK, ContentBlock } from './blocks/content'

registerBlockType(SOURCE_BLOCK, SourceBlock)
registerBlockType(CONTENT_BLOCK, ContentBlock)
