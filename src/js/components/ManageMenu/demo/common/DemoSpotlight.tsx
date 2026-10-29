import React, { useCallback, useEffect, useState } from 'react'
import type { CSSProperties } from 'react'

interface Rect {
	top: number
	left: number
	width: number
	height: number
}

export interface DemoSpotlightProps {
	/** CSS selector for the element to leave uncovered. */
	target?: string
	/** Breathing room left around the target, in pixels. */
	padding?: number
}

const DEFAULT_PADDING = 8

/** Padding is applied to both sides of each axis. */
const BOTH_SIDES = 2

const measure = (target: string, padding: number): Rect | undefined => {
	const element = document.querySelector(target)

	if (!element) {
		return undefined
	}

	const { top, left, width, height } = element.getBoundingClientRect()

	return {
		top: top - padding,
		left: left - padding,
		width: width + BOTH_SIDES * padding,
		height: height + BOTH_SIDES * padding
	}
}

/**
 * Dims and blurs the page around one element, so the commentary has something
 * unambiguous to point at.
 *
 * The cutout is made from four panels around the target rather than a masked
 * overlay: the gap between them is the hole, which keeps the blur honest and
 * avoids masking support becoming a rendering variable.
 */
export const DemoSpotlight: React.FC<DemoSpotlightProps> = ({ target, padding = DEFAULT_PADDING }) => {
	const [rect, setRect] = useState<Rect>()

	const remeasure = useCallback(() => {
		setRect(target ? measure(target, padding) : undefined)
	}, [padding, target])

	useEffect(() => {
		remeasure()

		if (!target) {
			return
		}

		// The page scrolls and reflows underneath the spotlight, so the cutout
		// is re-measured rather than positioned once.
		const observer = new ResizeObserver(remeasure)
		observer.observe(document.body)
		window.addEventListener('scroll', remeasure, { passive: true })
		window.addEventListener('resize', remeasure)

		return () => {
			observer.disconnect()
			window.removeEventListener('scroll', remeasure)
			window.removeEventListener('resize', remeasure)
		}
	}, [remeasure, target])

	if (!rect) {
		return null
	}

	const { top, left, width, height } = rect

	const panels: CSSProperties[] = [
		{ insetBlockStart: 0, insetInlineStart: 0, inlineSize: '100%', blockSize: `${Math.max(0, top)}px` },
		{ insetBlockStart: `${top + height}px`, insetInlineStart: 0, inlineSize: '100%', insetBlockEnd: 0 },
		{ insetBlockStart: `${top}px`, insetInlineStart: 0, inlineSize: `${Math.max(0, left)}px`, blockSize: `${height}px` },
		{ insetBlockStart: `${top}px`, insetInlineStart: `${left + width}px`, insetInlineEnd: 0, blockSize: `${height}px` }
	]

	const ring: CSSProperties = {
		insetBlockStart: `${top}px`,
		insetInlineStart: `${left}px`,
		inlineSize: `${width}px`,
		blockSize: `${height}px`
	}

	return (
		<div className="demo-spotlight" aria-hidden="true">
			{panels.map((style, index) =>
				<div key={index} className="demo-spotlight__panel" style={style} />)}

			<div className="demo-spotlight__ring" style={ring} />
		</div>
	)
}
