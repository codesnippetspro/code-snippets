/*
Based on work distributed under the BSD 3-Clause License (https://rawgit.com/glayzzle/codemirror-linter/master/LICENSE)
*/

import Parser from 'php-parser/src/index';


(function (CodeMirror) {
	'use strict';

	class Linter {

		constructor(code) {
			this.code = code;
			this.annotations = [];

			this.function_names = new Set();
			this.class_names = new Set();
		}

		lint() {
			const parser = new Parser({
				parser: {
					suppressErrors: true,
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
						this.annotate(ast.errors[i].message, ast.errors[i].loc);
					}
				}

				this.visit(ast);

			} catch (error) {
				this.annotate(error.message, error);
			}
		}

		/**
		 * Visit nodes recursively
		 * @param node
		 */
		visit(node) {

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
		validate(node) {

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
		 * Add a new lint annotation
		 * @param {string} message
		 * @param {Location|SyntaxError} position
		 * @param {string} [severity]
		 */
		annotate(message, position, severity) {
			let start, end;

			if (position.lineNumber && position.columnNumber) {
				start = CodeMirror.Pos(position.lineNumber - 1, position.columnNumber - 1);
				end = CodeMirror.Pos(position.lineNumber - 1, position.columnNumber);

			} else if (position.start && position.end) {
				if (position.end.offset < position.start.offset) {
					end = CodeMirror.Pos(position.start.line - 1, position.start.column);
					start = CodeMirror.Pos(position.end.line - 1, position.end.column);
				} else {
					start = CodeMirror.Pos(position.start.line - 1, position.start.column);
					end = CodeMirror.Pos(position.end.line - 1, position.end.column);
				}
			}

			if (start && end) {
				severity = severity ? severity : 'error';
				this.annotations.push({message: message, severity: severity, from: start, to: end});
			}
		}
	}

	CodeMirror.registerHelper('lint', 'php', function (text, options) {
		const linter = new Linter(text);
		linter.lint();

		return linter.annotations;
	});

})(wp.CodeMirror);
