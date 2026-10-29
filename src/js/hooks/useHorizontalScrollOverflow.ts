import { useCallback, useEffect, useRef, useState } from 'react'

interface HorizontalScrollOverflow {
	atStart: boolean
	atEnd: boolean
	scrollRef: React.RefObject<HTMLElement>
}

const EDGE_TOLERANCE = 1

export const useHorizontalScrollOverflow = (): HorizontalScrollOverflow => {
	const scrollRef = useRef<HTMLElement>(null)
	const [position, setPosition] = useState({ atStart: true, atEnd: true })

	const updatePosition = useCallback(() => {
		const element = scrollRef.current

		if (!element) {
			return
		}

		const hasOverflow = element.scrollWidth - element.clientWidth > EDGE_TOLERANCE
		const atStart = !hasOverflow || element.scrollLeft <= EDGE_TOLERANCE
		const atEnd = !hasOverflow || element.scrollLeft + element.clientWidth >= element.scrollWidth - EDGE_TOLERANCE

		setPosition(previous => previous.atStart === atStart && previous.atEnd === atEnd
			? previous
			: { atStart, atEnd })
	}, [])

	useEffect(() => {
		const element = scrollRef.current

		if (!element) {
			return
		}

		updatePosition()
		element.addEventListener('scroll', updatePosition, { passive: true })

		const observer = new ResizeObserver(updatePosition)
		observer.observe(element)
		observer.observe(element.firstElementChild ?? element)

		return () => {
			element.removeEventListener('scroll', updatePosition)
			observer.disconnect()
		}
	}, [updatePosition])

	return { ...position, scrollRef }
}
