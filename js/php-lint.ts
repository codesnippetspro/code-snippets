/**
 * Based on work distributed under the BSD 3-Clause License (https://rawgit.com/glayzzle/codemirror-linter/master/LICENSE)
 */

import Parser, { Block, Location, Node } from 'php-parser'
import * as CodeMirror from 'codemirror'

type Annotation = { message: string, severity: string, from: CodeMirror.Position, to: CodeMirror.Position }

interface Identifier extends Node {
	name: string
}

interface Declaration extends Node {
	name: Identifier | string
}

class Linter {
	private readonly code: string

	private readonly function_names: Set<string>

	private readonly class_names: Set<string>

	public readonly annotations: Annotation[]

	/**
	 * Constructor.
	 * @param code
	 */
	constructor(code: string) {
		this.code = code
		this.annotations = []

		this.function_names = new Set()
		this.class_names = new Set()
	}

	/**
	 * Lint the provided code.
	 */
	lint() {
		const parser = new Parser({
			parser: {
				suppressErrors: true,
				// @ts-expect-error types file has not been updated to support version key.
				version: 800
			},
			ast: {
				withPositions: true
			}
		})

		try {
			const ast = parser.parseEval(this.code)

			// Process any errors caught by the parser.
			if (ast.errors && 0 < ast.errors.length) {
				for (const error of ast.errors) {
					this.annotate(error.message as string, error.loc)
				}
			}

			// Visit each node to perform additional checks.
			this.visit(ast)

		} catch (error) {
			// eslint-disable-next-line no-console
			console.error(error)
		}
	}

	/**
	 * Visit nodes recursively.
	 * @param node
	 */
	visit(node: Node) {

		if (node.kind) {
			this.validate(node)
		}

		if ('children' in node) {
			const block = node as Block
			for (const child of block.children) {
				this.visit(child)
			}
		}
	}

	/**
	 * Check whether a given identifier has already been defined, creating an annotation if so.
	 * @param identifier
	 * @param registry
	 * @param label
	 */
	checkDuplicateIdentifier(identifier: Identifier, registry: Set<string>, label: string) {
		if (registry.has(identifier.name)) {
			this.annotate(`Cannot redeclare ${label} ${identifier.name}()`, identifier.loc)
		} else {
			registry.add(identifier.name)
		}
	}

	/**
	 * Perform additional validations on nodes.
	 * @param node
	 */
	validate(node: Node) {
		const decl = node as Declaration
		const ident = decl.name as Identifier

		if (!('name' in decl && 'name' in ident) || 'identifier' !== ident.kind) {
			return
		}

		if ('function' === node.kind) {
			this.checkDuplicateIdentifier(ident, this.function_names, 'function')

		} else if ('class' === node.kind) {
			this.checkDuplicateIdentifier(ident, this.class_names, 'class')
		}
	}

	/**
	 * Create a lint annotation.
	 * @param message
	 * @param location
	 * @param severity
	 */
	annotate(message: string, location: Location, severity = 'error') {
		if (!location.start || !location.end) return

		const [start, end] = location.end.offset < location.start.offset ?
			[location.end, location.start] :
			[location.start, location.end]

		this.annotations.push({
			message,
			severity,
			from: CodeMirror.Pos(start.line as number - 1, start.column as number),
			to: CodeMirror.Pos(end.line as number - 1, end.column as number)
		})
	}
}

CodeMirror.registerHelper('lint', 'php', (text: string) => {
	const linter = new Linter(text)
	linter.lint()

	return linter.annotations
})
