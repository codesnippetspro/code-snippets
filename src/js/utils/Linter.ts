/**
 * Based on work licensed under the BSD 3-Clause license.
 *
 * Copyright (c) 2017, glayzzle
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

import { Engine } from 'php-parser'
import CodeMirror from 'codemirror'
import type { Block, Location, Node } from 'php-parser'

export interface Annotation {
	message: string
	severity: string
	from: CodeMirror.Position
	to: CodeMirror.Position
}

export interface Identifier extends Node {
	name: string
}

export interface Declaration extends Node {
	name: Identifier | string
}

export class Linter {
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
		const parser = new Engine({
			parser: {
				suppressErrors: true,
				version: 800
			},
			ast: {
				withPositions: true
			}
		})

		try {
			const program = parser.parseEval(this.code)

			if (0 < program.errors.length) {
				for (const error of program.errors) {
					this.annotate(error.message, error.loc)
				}
			}

			this.visit(program)
		} catch (error) {
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
			const block = <Block> node
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
		const decl = <Declaration> node
		const ident = <Identifier> decl.name

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
	annotate(message: string, location: Location | null, severity = 'error') {
		const [start, end] = location
			? location.end.offset < location.start.offset ? [location.end, location.start] : [location.start, location.end]
			: [{ line: 0, column: 0 }, { line: 0, column: 0 }]

		this.annotations.push({
			message,
			severity,
			from: CodeMirror.Pos(start.line - 1, start.column),
			to: CodeMirror.Pos(end.line - 1, end.column)
		})
	}
}
