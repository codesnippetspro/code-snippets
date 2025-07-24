import { createContext, useContext } from 'react'
import { createRoot } from 'react-dom/client'
import type { Context, FunctionComponent } from 'react'
import React from 'react'

export const loadComponent = (containerId: string, Component: FunctionComponent): void => {
	const container = document.getElementById('snippets-table-container')

	if (container) {
		const root = createRoot(container)
		root.render(<Component />)
	} else {
		console.error(`Could not find ${containerId.replace(/-_/, ' ')}.`)
	}
}

export const createContextHook = <T, >(name: string): [
	Context<T | undefined>,
	() => T
] => {
	const contextValue = createContext<T | undefined>(undefined)

	const useContextHook = (): T => {
		const value = useContext(contextValue)

		if (value === undefined) {
			throw Error(`use${name} can only be used within a ${name} context provider.`)
		}

		return value
	}

	return [contextValue, useContextHook]
}
