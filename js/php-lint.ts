/*
Based on work distributed under the BSD 3-Clause License (https://rawgit.com/glayzzle/codemirror-linter/master/LICENSE)
*/

import Parser, {Block, Location, Node} from 'php-parser';
import './globals';
import {Position} from 'codemirror';

(function (CodeMirror) {
	'use strict';

	type Annotation = { message: string, severity: string, from: Position, to: Position };

	interface Identifier extends Node {
		name: string;
	}

	interface Declaration extends Node {
		name: Identifier | string;
	}

	class Linter {
		private readonly code: string;
		private function_names: Set<string>;
		private class_names: Set<string>;

		public readonly annotations: Annotation[];

		/**
		 * Constructor.
		 * @param code
		 */
		constructor(code: string) {
			this.code = code;
			this.annotations = [];

			this.function_names = new Set();
			this.class_names = new Set();
		}

		/**
		 * Lint the provided code.
		 */
		lint() {
			const parser = new Parser({
				parser: {
					suppressErrors: true,
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore types file has not been updated to support this
					version: 800
				},
				ast: {
					withPositions: true
				}
			});

			try {
				const ast = parser.parseEval(this.code);

				// process any errors caught by the parser.
				if (ast.errors && ast.errors.length > 0) {
					for (let i = 0; i < ast.errors.length; i++) {
						this.annotate(ast.errors[i].message as string, ast.errors[i].loc);
					}
				}

				// visit each node to perform additional checks.
				this.visit(ast);

			} catch (error) {
				console.log(error);
			}
		}

		/**
		 * Visit nodes recursively.
		 * @param node
		 */
		visit(node: Node) {

			if (node.kind) {
				this.validate(node);
			}

			if ('children' in node) {
				for (const child of (node as Block).children) {
					this.visit(child);
				}
			}
		}

		/**
		 * Perform additional validations on nodes.
		 * @param node
		 */
		validate(node: Node) {
			const decl = node as Declaration;
			const ident = decl.name as Identifier;

			if (!('name' in decl && 'name' in ident) || 'identifier' !== ident.kind) {
				return;
			}

			if ('function' === node.kind) {
				if (this.function_names.has(ident.name)) {
					this.annotate(`Cannot redeclare function ${ident.name}()`, ident.loc);
				} else {
					this.function_names.add(ident.name);
				}

			} else if ('class' === node.kind) {
				if (this.class_names.has(ident.name)) {
					this.annotate(`Cannot redeclare class ${ident.name}`, ident.loc);
				} else {
					this.class_names.add(ident.name);
				}
			}
		}

		/**
		 * Create a lint annotation.
		 * @param message
		 * @param location
		 * @param severity
		 */
		annotate(message: string, location: Location, severity = 'error') {
			if (!location.start || !location.end) return;

			const [start, end] = location.end.offset < location.start.offset ?
				[location.end, location.start] : [location.start, location.end];

			this.annotations.push({
				message: message,
				severity: severity,
				from: CodeMirror.Pos(start.line as number - 1, start.column as number),
				to: CodeMirror.Pos(end.line as number - 1, end.column as number)
			});
		}
	}

	CodeMirror.registerHelper('lint', 'php', (text: string) => {
		const linter = new Linter(text);
		linter.lint();

		return linter.annotations;
	});

})(window.wp.CodeMirror);
