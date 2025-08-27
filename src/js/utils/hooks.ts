import { createContext, useContext } from 'react'
import type { Context } from 'react'

export const createContextHook = <T>(name: string): [
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
