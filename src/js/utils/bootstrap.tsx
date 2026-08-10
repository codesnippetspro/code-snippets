import React, { createContext, useContext } from 'react'
import { createRoot } from 'react-dom/client'
import type { Context, FunctionComponent } from 'react'

export const loadComponent = (containerId: string, Component: FunctionComponent): void => {
	const container = document.getElementById(containerId)

	if (container) {
		const root = createRoot(container)
		root.render(<Component />)
	} else {
		console.error(`Could not find element #${containerId}.`)
	}
}

export const createContextHook = <T, >(hookName: string): [
	Context<T | undefined>,
	() => T
] => {
	const Context = createContext<T | undefined>(undefined)

	const useContextHook = (): T => {
		const value = useContext(Context)

		if (value === undefined) {
			throw Error(`${hookName} can only be used within a corresponding context provider.`)
		}

		return value
	}

	return [Context, useContextHook]
}
