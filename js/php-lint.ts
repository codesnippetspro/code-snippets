/*
Based on work distributed under the BSD 3-Clause License (https://rawgit.com/glayzzle/codemirror-linter/master/LICENSE)
*/

import Parser, {Location} from 'php-parser';
import './globals';
import {Position} from 'codemirror';

(function (CodeMirror) {
	'use strict';

	type Annotation = { message: string, severity: string, from: Position, to: Position };

	class Linter {
		private readonly code: string;
		private function_names: Set<string>;
		private class_names: Set<string>;

		public readonly annotations: Annotation[];

		constructor(code: string) {
			this.code = code;
			this.annotations = [];

			this.function_names = new Set();
			this.class_names = new Set();
		}

		lint() {
			const parser = new Parser({
				parser: {
					suppressErrors: true,
					// @ts-ignore types file has not been updated to support this
					version: 800
				},
				ast: {
					withPositions: true
				}
			});

			try {
				let ast = parser.parseEval(this.code);

				if (ast.errors && ast.errors.length > 0) {
					for (let i = 0; i < ast.errors.length; i++) {
						this.annotate(ast.errors[i].message as string, ast.errors[i].loc);
					}
				}

				this.visit(ast);

			} catch (error) {
				console.log(error);
			}
		}

		/**
		 * Visit nodes recursively
		 * @param node
		 */
		visit(node: any) {

			if (node.hasOwnProperty('kind')) {
				this.validate(node);
			}

			if (node.hasOwnProperty('children')) {

				for (const child of node.children) {
					this.visit(child);
				}
			}
		}

		/**
		 * Perform additional validations on nodes
		 * @param node
		 */
		validate(node: any) {

			if (('function' === node.kind || 'class' === node.kind) && node.hasOwnProperty('name') && 'identifier' === node.name.kind) {

				if ('function' === node.kind) {
					if (this.function_names.has(node.name.name)) {
						this.annotate(`Cannot redeclare function ${node.name.name}()`, node.name.loc);
					} else {
						this.function_names.add(node.name.name);
					}
				} else if ('class' === node.kind) {
					if (this.class_names.has(node.name.name)) {
						this.annotate(`Cannot redeclare class ${node.name.name}`, node.name.loc);
					} else {
						this.class_names.add(node.name.name);
					}
				}
			}
		}

		/**
		 * Create a lint annotation.
		 * @param message
		 * @param location
		 * @param severity
		 */
		annotate(message: string, location: Location, severity: string = 'error') {

			if (!location.start || !location.end) {
				return;
			}

			let [start, end] = location.end.offset < location.start.offset ?
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
