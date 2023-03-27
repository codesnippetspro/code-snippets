import { registerBlockType } from '@wordpress/blocks'
import { SOURCE_BLOCK, SourceBlock } from './SourceBlock'
import { CONTENT_BLOCK, ContentBlock } from './ContentBlock'

registerBlockType(SOURCE_BLOCK, SourceBlock)
registerBlockType(CONTENT_BLOCK, ContentBlock)
