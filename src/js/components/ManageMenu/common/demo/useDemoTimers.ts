import { useCallback, useEffect, useRef } from 'react'

interface TimersState {
	addTimer: (handler: VoidFunction, timeout: number) => void
	clearTimers: VoidFunction
}

export const useDemoTimers = (): TimersState => {
	const timers = useRef<number[]>([])

	const addTimer = useCallback((handler: VoidFunction, timeout: number) => {
		timers.current.push(window.setTimeout(handler, timeout))
	}, [])

	const clearAll = useCallback(() => {
		timers.current.forEach(window.clearTimeout)
		timers.current = []
	}, [])

	useEffect(() => {
		timers.current.forEach(window.clearTimeout)
	}, [])

	return { addTimer, clearTimers: clearAll }
}
