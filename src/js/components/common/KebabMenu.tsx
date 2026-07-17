import classnames from 'classnames'
import React, { createContext, useCallback, useContext, useEffect, useId, useLayoutEffect, useRef, useState } from 'react'
import type { Dispatch, PropsWithChildren, ReactNode, RefObject, SetStateAction } from 'react'

const FOCUSABLE_SELECTOR = [
	'button:not(:disabled)',
	'[href]',
	'input:not(:disabled)',
	'select:not(:disabled)',
	'textarea:not(:disabled)',
	'[tabindex]:not([tabindex="-1"])'
].join(', ')

interface KebabMenuContextValue {
	closeMenu: () => void
}

const KebabMenuContext = createContext<KebabMenuContextValue>({ closeMenu: () => undefined })

/**
 * Retrieve the close handler of the containing kebab menu, allowing custom
 * rows to dismiss the popover once their action has completed.
 */
export const useKebabMenu = (): KebabMenuContextValue => useContext(KebabMenuContext)

export interface KebabMenuItemProps {
	onSelect?: () => void
	destructive?: boolean
	keepOpen?: boolean
	disabled?: boolean
	className?: string
}

export const KebabMenuItem: React.FC<PropsWithChildren<KebabMenuItemProps>> = ({
	onSelect,
	destructive,
	keepOpen,
	disabled,
	className,
	children
}) => {
	const { closeMenu } = useKebabMenu()

	return (
		<li role="presentation">
			<button
				type="button"
				role="menuitem"
				className={classnames('kebab-menu-item', { 'kebab-menu-item-destructive': destructive }, className)}
				disabled={disabled}
				onClick={() => {
					onSelect?.()

					if (!keepOpen) {
						closeMenu()
					}
				}}
			>
				{children}
			</button>
		</li>
	)
}

export const KebabMenuDivider: React.FC = () =>
	<li role="separator" aria-orientation="horizontal" className="kebab-menu-divider" />

export interface KebabMenuRowProps {
	className?: string
}

export const KebabMenuRow: React.FC<PropsWithChildren<KebabMenuRowProps>> = ({ className, children }) =>
	<li role="presentation" className={classnames('kebab-menu-row', className)}>
		{children}
	</li>

const KebabIcon: React.FC = () =>
	<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		<circle cx="9" cy="3.5" r="1.6" fill="currentColor" />
		<circle cx="9" cy="9" r="1.6" fill="currentColor" />
		<circle cx="9" cy="14.5" r="1.6" fill="currentColor" />
	</svg>

interface PopoverBehaviourOptions {
	isOpen: boolean
	setIsOpen: Dispatch<SetStateAction<boolean>>
	closeMenu: () => void
	containerRef: RefObject<HTMLDivElement>
	triggerRef: RefObject<HTMLButtonElement>
	popoverRef: RefObject<HTMLUListElement>
}

// Flip the popover above the trigger when it would overflow the viewport,
// and move focus to its first menu item on open. Focus falls back to the
// popover itself rather than the first focusable element, so embedded form
// fields with blur handlers are never focused without user intent.
const usePopoverPlacement = ({ isOpen, triggerRef, popoverRef }: PopoverBehaviourOptions): boolean => {
	const [isFlipped, setIsFlipped] = useState(false)

	useLayoutEffect(() => {
		if (isOpen && popoverRef.current) {
			const popover = popoverRef.current.getBoundingClientRect()
			const trigger = triggerRef.current?.getBoundingClientRect()
			setIsFlipped(popover.bottom > window.innerHeight && (trigger?.top ?? 0) > popover.height)

			const firstItem = popoverRef.current.querySelector<HTMLElement>('[role="menuitem"]:not(:disabled)')
			;(firstItem ?? popoverRef.current).focus()
		} else {
			setIsFlipped(false)
		}
	}, [isOpen, triggerRef, popoverRef])

	return isFlipped
}

const handlePopoverKeyDown = (
	event: KeyboardEvent,
	{ closeMenu, popoverRef }: Pick<PopoverBehaviourOptions, 'closeMenu' | 'popoverRef'>
) => {
	if ('Escape' === event.key) {
		event.preventDefault()
		closeMenu()
		return
	}

	if (!popoverRef.current) {
		return
	}

	const focusable = Array.from(popoverRef.current.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR))
	const active = document.activeElement
	const currentIndex = active instanceof HTMLElement ? focusable.indexOf(active) : -1

	if ('ArrowDown' === event.key || 'ArrowUp' === event.key || 'Home' === event.key || 'End' === event.key) {
		if (0 < focusable.length) {
			event.preventDefault()
			const offset = 'ArrowDown' === event.key ? currentIndex + 1 : currentIndex - 1
			const target = 'Home' === event.key
				? 0
				: 'End' === event.key ? focusable.length - 1 : (offset + focusable.length) % focusable.length
			focusable[target].focus()
		}

		return
	}

	if ('Tab' !== event.key) {
		return
	}

	if (0 === focusable.length) {
		event.preventDefault()
	} else if (event.shiftKey && 0 >= currentIndex) {
		event.preventDefault()
		focusable[focusable.length - 1].focus()
	} else if (!event.shiftKey && (-1 === currentIndex || currentIndex === focusable.length - 1)) {
		event.preventDefault()
		focusable[0].focus()
	}
}

// Close the open popover on outside clicks or Escape, and trap Tab focus
// within it while it remains open.
const usePopoverDismissal = ({ isOpen, setIsOpen, closeMenu, containerRef, popoverRef }: PopoverBehaviourOptions) => {
	useEffect(() => {
		if (!isOpen) {
			return
		}

		const handlePointerDown = (event: MouseEvent) => {
			if (event.target instanceof Node && !containerRef.current?.contains(event.target)) {
				setIsOpen(false)
			}
		}

		const handleKeyDown = (event: KeyboardEvent) =>
			handlePopoverKeyDown(event, { closeMenu, popoverRef })

		document.addEventListener('mousedown', handlePointerDown)
		document.addEventListener('keydown', handleKeyDown)

		return () => {
			document.removeEventListener('mousedown', handlePointerDown)
			document.removeEventListener('keydown', handleKeyDown)
		}
	}, [isOpen, setIsOpen, closeMenu, containerRef, popoverRef])
}

export interface KebabMenuProps {
	label: string
	className?: string
	children: ReactNode
}

/**
 * Generic "more actions" menu: a square trigger button that opens a popover
 * of actions anchored to its bottom-end corner, flipping above the trigger
 * when there is no room below. The popover stays open for clicks inside it
 * and closes on outside clicks, Escape, or when an item action completes.
 */
export const KebabMenu: React.FC<KebabMenuProps> = ({ label, className, children }) => {
	const [isOpen, setIsOpen] = useState(false)
	const containerRef = useRef<HTMLDivElement>(null)
	const triggerRef = useRef<HTMLButtonElement>(null)
	const popoverRef = useRef<HTMLUListElement>(null)
	const menuId = useId()

	const closeMenu = useCallback(() => {
		setIsOpen(false)
		triggerRef.current?.focus()
	}, [])

	const behaviourOptions = { isOpen, setIsOpen, closeMenu, containerRef, triggerRef, popoverRef }
	const isFlipped = usePopoverPlacement(behaviourOptions)
	usePopoverDismissal(behaviourOptions)

	return (
		<div ref={containerRef} className={classnames('kebab-menu', className)}>
			<button
				ref={triggerRef}
				type="button"
				className="kebab-menu-trigger"
				aria-label={label}
				aria-haspopup="true"
				aria-expanded={isOpen}
				aria-controls={menuId}
				onClick={() => setIsOpen(open => !open)}
			>
				<KebabIcon />
			</button>

			{isOpen
				? <KebabMenuContext.Provider value={{ closeMenu }}>
					<ul
						ref={popoverRef}
						id={menuId}
						role="menu"
						tabIndex={-1}
						aria-label={label}
						className={classnames('kebab-menu-popover', { 'kebab-menu-popover-top': isFlipped })}
					>
						{children}
					</ul>
				</KebabMenuContext.Provider>
				: null}
		</div>
	)
}
