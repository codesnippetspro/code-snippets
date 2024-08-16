import { rm, mkdir, stat } from 'fs/promises'
import { createWriteStream, createReadStream } from 'fs'
import { join, dirname } from 'path'
import { glob } from 'glob'

export const resolve = (...parts: string[]): string =>
	join(__dirname, '..', '..', ...parts)

export const cleanup = (...paths: string[]): Promise<Awaited<void>[]> =>
	Promise.all(paths.map(filename =>
		rm(resolve(filename), { force: true, recursive: true })
	))

export const copy = async (patterns: string[], dest: string): Promise<void> => {
	const filenames = glob(patterns)
	await rm(dest, { force: true, recursive: true })
	await mkdir(dest, { recursive: true })

	for (const filename of await filenames) {
		const stats = await stat(resolve(filename))

		if (!stats.isDirectory()) {
			console.log(`copying ${filename}`)
			const destPath = resolve(dest, filename)

			await mkdir(dirname(destPath), { recursive: true })
			const out = createWriteStream(destPath, 'utf8')
			createReadStream(filename).pipe(out)
		}
	}
}
