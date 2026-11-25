import { __, _x, sprintf } from '@wordpress/i18n'
import React, { useEffect, useRef, useState } from 'react'
import { buildUrl } from '../../utils/urls'
import type { MouseEvent } from 'react'

const BODY_CLASS = 'code-snippets-has-promo-banner'
const SIZE_PROPERTY = '--cs-promo-banner-block-size'

const SECOND_MS = 1000
const SECONDS_PER_MINUTE = 60
const MINUTES_PER_HOUR = 60
const HOURS_PER_DAY = 24
const MINUTE_MS = SECONDS_PER_MINUTE * SECOND_MS
const HOUR_MS = MINUTES_PER_HOUR * MINUTE_MS
const DAY_MS = HOURS_PER_DAY * HOUR_MS
const UNIT_DIGITS = 2

const padUnit = (value: number): string =>
	String(value).padStart(UNIT_DIGITS, '0')

interface PromoCountdownProps {
	remaining: number
}

const PromoCountdown: React.FC<PromoCountdownProps> = ({ remaining }) => {
	const units = {
		// translators: %s: number of days left in the offer.
		days: sprintf(_x('%sd', 'countdown days', 'code-snippets'), padUnit(Math.floor(remaining / DAY_MS))),
		// translators: %s: number of hours left in the offer.
		hours: sprintf(_x('%sh', 'countdown hours', 'code-snippets'), padUnit(Math.floor(remaining % DAY_MS / HOUR_MS))),
		// translators: %s: number of minutes left in the offer.
		minutes: sprintf(_x('%sm', 'countdown minutes', 'code-snippets'), padUnit(Math.floor(remaining % HOUR_MS / MINUTE_MS))),
		// translators: %s: number of seconds left in the offer.
		seconds: sprintf(_x('%ss', 'countdown seconds', 'code-snippets'), padUnit(Math.floor(remaining % MINUTE_MS / SECOND_MS)))
	}

	return (
		<span className="code-snippets-promo-banner-countdown" role="timer" aria-label={__('Time left in this offer', 'code-snippets')}>
			{Object.entries(units).map(([unit, text]) => <span key={unit}>{text}</span>)}
		</span>
	)
}

const usePromoTimeLeft = (endsAtValue: string | null | undefined) => {
	const endsAt = endsAtValue ? Date.parse(endsAtValue) : NaN
	const hasEnd = Number.isFinite(endsAt)

	const [now, setNow] = useState(Date.now)
	const isExpired = hasEnd && now >= endsAt

	useEffect(() => {
		if (!hasEnd || isExpired) {
			return undefined
		}

		const timer = window.setInterval(() => setNow(Date.now()), SECOND_MS)
		return () => window.clearInterval(timer)
	}, [hasEnd, isExpired])

	return { hasEnd, isExpired, remaining: endsAt - now }
}

// The toolbar sits above the page content, which is pushed down by the banner's height. The server reserves an
// estimate through a body class; the banner then reports its real height, which grows when its text wraps, and
// gives the space back once it is no longer shown.
const useReservedSpace = (isShown: boolean) => {
	const ref = useRef<HTMLElement>(null)

	useEffect(() => {
		const banner = ref.current

		if (!isShown || !banner) {
			document.body.classList.remove(BODY_CLASS)
			return undefined
		}

		const observer = new ResizeObserver(() =>
			document.body.style.setProperty(SIZE_PROPERTY, `${banner.offsetHeight}px`))

		observer.observe(banner)

		return () => {
			observer.disconnect()
			document.body.style.removeProperty(SIZE_PROPERTY)
		}
	}, [isShown])

	return ref
}

export const PromoBanner = () => {
	const promo = window.CODE_SNIPPETS_MANAGE?.promoBanner
	const { hasEnd, isExpired, remaining } = usePromoTimeLeft(promo?.endsAt)
	const ref = useReservedSpace(!!promo && !isExpired)

	if (!promo || isExpired) {
		return null
	}

	// The screen rewrites its own query string as people filter and page through it, so the dismissal
	// returns them to where they are now rather than to the address the page was first loaded with.
	const dismiss = (event: MouseEvent<HTMLAnchorElement>) => {
		event.preventDefault()
		window.location.assign(buildUrl(window.location.href, promo.dismissArgs))
	}

	return (
		<section
			ref={ref}
			className={`code-snippets-promo-banner code-snippets-${promo.key}-promo`}
			aria-label={'' === promo.label ? __('Promotion', 'code-snippets') : promo.label}
		>
			{'' !== promo.label && <span className="code-snippets-promo-banner-label">{promo.label}</span>}

			{/* The server limits the feed text to post HTML before it reaches the page. */}
			<span className="code-snippets-promo-banner-text" dangerouslySetInnerHTML={{ __html: promo.text }} />

			{hasEnd && <PromoCountdown remaining={remaining} />}

			{'' !== promo.actionUrl && '' !== promo.actionLabel && (
				<a
					className="code-snippets-promo-banner-action"
					href={promo.actionUrl}
					target="_blank"
					rel="noopener noreferrer"
				>
					{promo.actionLabel}
				</a>
			)}

			{'' !== promo.secondary && (
				<span className="code-snippets-promo-banner-secondary" dangerouslySetInnerHTML={{ __html: promo.secondary }} />
			)}

			<a className="code-snippets-promo-banner-dismiss" href={promo.dismissUrl} onClick={dismiss}>
				<span className="dashicons dashicons-no-alt" aria-hidden="true"></span>
				<span className="screen-reader-text">{__('Dismiss', 'code-snippets')}</span>
			</a>
		</section>
	)
}
