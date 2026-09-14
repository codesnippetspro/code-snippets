import { readFileSync } from 'fs'
import { join } from 'path'
import { expect, test } from '@playwright/test'
import { compile } from 'sass'

const root = join(__dirname, '..', '..')

const compiledCss = compile(join(root, 'src', 'css', 'manage', 'blueprints', '_gallery.scss')).css

const galleryPath = ['src', 'js', 'components', 'ManageMenu', 'Blueprints', 'BlueprintGallery']

const gallerySource = ['BlueprintGalleryBody.tsx', 'BlueprintGalleryHeader.tsx']
	.map(file => readFileSync(join(root, ...galleryPath, file), 'utf8'))
	.join('\n')

const styledClasses = new Set(
	[...compiledCss.matchAll(/\.(?<name>blueprint[\w-]*)/g)].map(match => match.groups?.name ?? '')
)

const renderedClasses = new Set(
	[...gallerySource.matchAll(/className="(?<names>[^"]+)"/g)]
		.flatMap(match => (match.groups?.names ?? '').split(/\s+/))
		.filter(name => name.startsWith('blueprint'))
)

test.describe('blueprint gallery selector parity', () => {
	test('renders at least the card chassis classes', () => {
		expect([...renderedClasses]).toContain('blueprint-card')
		expect([...renderedClasses]).toContain('blueprint-card-grid')
	})

	test('every rendered gallery class has a compiled style rule', () => {
		const unstyled = [...renderedClasses].filter(name => !styledClasses.has(name))
		expect(unstyled).toEqual([])
	})

	test('compiled gallery styles target only rendered classes', () => {
		const orphaned = [...styledClasses].filter(name => !renderedClasses.has(name))
		expect(orphaned).toEqual([])
	})
})
