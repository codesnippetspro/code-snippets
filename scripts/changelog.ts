#!/usr/bin/env ts-node
import fs from 'fs'
import { execSync } from 'child_process'
import { load as loadYaml } from 'js-yaml'

const MIN_ARGS = 3
if (MIN_ARGS > process.argv.length) {
	console.error('Usage: ts-node changelog.ts <template-yml-path>')
	process.exit(1)
}

const TEMPLATE_PATH_ARG_INDEX = 2
const templatePath = process.argv[TEMPLATE_PATH_ARG_INDEX]
const template = <{
	variables?: Record<string, string>
	template?: string
	prompt?: string
	task?: string
	target?: string
}>loadYaml(fs.readFileSync(templatePath, 'utf8'))

// 1. Gather variables
const variables: Record<string, string> = {}
for (const [key, cmd] of Object.entries(template.variables ?? {})) {
	let resolvedCmd = cmd
	for (const [k, v] of Object.entries(variables)) {
		resolvedCmd = resolvedCmd.replace(new RegExp(`<${k}>`, 'g'), v)
	}
	try {
		variables[key] = execSync(resolvedCmd, { encoding: 'utf8' }).trim()
	} catch (e) {
		console.error(`Error running command for variable ${key}: ${resolvedCmd}`)
		throw e
	}
}

// 2. Render template and prompt
const render = (str?: string): string => {
	if (!str) { return '' }
	return str.replace(/<variables\.(?:\w+)>/g, (_: string, k: string) => variables[k] ?? '')
		.replace(/<template>/g, template.template ?? '')
		.replace(/<task>/g, template.task ?? '')
}

const renderedPrompt = render(template.prompt)
const renderedTemplate = render(template.template)
const renderedTask = render(template.task)
const target = template.target ?? ''

// 3. Centralized output logic
const outputChangelog = (
	outputFn: (line: string) => void,
	renderedPrompt: string,
	renderedTemplate: string,
	renderedTask: string,
	target: string
) => {
	outputFn(`prompt<<EOF\n${renderedPrompt}\nEOF`)
	outputFn(`target=${target}`)
	outputFn(`template<<EOF\n${renderedTemplate}\nEOF`)
	outputFn(`task<<EOF\n${renderedTask}\nEOF`)
}

// Output to console
outputChangelog(console.log, renderedPrompt, renderedTemplate, renderedTask, target)

// Output to GitHub Actions if applicable
if (process.env.GITHUB_OUTPUT) {
	const githubOutput = process.env.GITHUB_OUTPUT
	if (githubOutput) {
		const append = (line: string) => fs.appendFileSync(githubOutput, `${line}\n`)
		outputChangelog(append, renderedPrompt, renderedTemplate, renderedTask, target)
	}
}
