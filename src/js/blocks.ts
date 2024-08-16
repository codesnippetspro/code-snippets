import { registerBlockType } from '@wordpress/blocks'
import { CONTENT_BLOCK, ContentBlock } from './blocks/ContentBlock'
import { SOURCE_BLOCK, SourceBlock } from './blocks/SourceBlock'

registerBlockType(SOURCE_BLOCK, SourceBlock)
registerBlockType(CONTENT_BLOCK, ContentBlock)
