type PaginationStatus = 'active' | 'inactive'
type PaginationAction = 'first' | 'prev' | 'next' | 'last'

type SnippetResponseItem = {
	id: number
	type: string
	name?: string
}

type AdminBarConfig = {
	restUrl: string
	nonce: string
	perPage: number
	isNetwork: boolean
	excludeTypes: string[]
	snippetPlaceholder: string
	editUrlBase: string
	activeNodeId: string
	inactiveNodeId: string
}

declare const CODE_SNIPPETS_ADMIN_BAR: AdminBarConfig | undefined

const config = typeof CODE_SNIPPETS_ADMIN_BAR !== 'undefined' ? CODE_SNIPPETS_ADMIN_BAR : undefined

const getMenuNode = (status: PaginationStatus): HTMLElement | null => {
	if (!config) return null
	const nodeId = status === 'active' ? config.activeNodeId : config.inactiveNodeId
	return document.getElementById(nodeId)
}

const getPaginationControls = (status: PaginationStatus): HTMLElement | null => {
	const menuNode = getMenuNode(status)
	if (!menuNode) return null

	return menuNode.querySelector<HTMLElement>(`.code-snippets-pagination-controls[data-status="${status}"]`)
}

const getPaginationState = (controls: HTMLElement): { page: number; totalPages: number } => {
	const page = Number.parseInt(controls.dataset.page ?? '1', 10)
	const totalPages = Number.parseInt(controls.dataset.totalPages ?? '1', 10)

	return {
		page: Number.isFinite(page) && page > 0 ? page : 1,
		totalPages: Number.isFinite(totalPages) && totalPages > 0 ? totalPages : 1
	}
}

const setLoading = (controls: HTMLElement, loading: boolean) => {
	controls.dataset.loading = loading ? 'true' : 'false'
}

const buildRequestUrl = (status: PaginationStatus, page: number): string => {
	if (!config) return ''

	const url = new URL(config.restUrl)
	url.searchParams.set('status', status)
	url.searchParams.set('page', String(page))
	url.searchParams.set('per_page', String(config.perPage))
	url.searchParams.set('orderby', 'display_name')
	url.searchParams.set('order', 'asc')

	if (config.isNetwork) {
		url.searchParams.set('network', '1')
	}

	for (const excluded of config.excludeTypes ?? []) {
		url.searchParams.append('exclude_types[]', excluded)
	}

	return url.toString()
}

const fetchSnippetsPage = async (status: PaginationStatus, page: number) => {
	if (!config) {
		throw new Error('Missing CODE_SNIPPETS_ADMIN_BAR config')
	}

	const response = await fetch(buildRequestUrl(status, page), {
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': config.nonce
		}
	})

	if (!response.ok) {
		throw new Error(`Failed to fetch snippets (${response.status})`)
	}

	const totalPagesHeader = response.headers.get('X-WP-TotalPages') ?? '1'
	const totalPages = Number.parseInt(totalPagesHeader, 10) || 1

	const snippets = (await response.json()) as SnippetResponseItem[]

	return { snippets, totalPages }
}

const buildEditUrl = (snippetId: number): string => {
	if (!config) return '#'

	try {
		const url = new URL(config.editUrlBase, window.location.href)
		url.searchParams.set('id', String(snippetId))
		return url.toString()
	} catch {
		return '#'
	}
}

const buildSnippetPlaceholder = (snippetId: number): string => {
	if (!config?.snippetPlaceholder) return `Snippet #${snippetId}`

	return config.snippetPlaceholder.replace(/%(\d+\$)?d/, String(snippetId))
}

const formatSnippetTitle = (snippet: SnippetResponseItem): string => {
	const typeLabel = (snippet.type || '').toUpperCase()
	const name = snippet.name?.trim()
	const title = name ? name : buildSnippetPlaceholder(snippet.id)
	return `(${typeLabel}) ${title}`
}

const updatePaginationControls = (controls: HTMLElement, page: number, totalPages: number) => {
	controls.dataset.page = String(page)
	controls.dataset.totalPages = String(totalPages)

	const pageLabel = controls.querySelector<HTMLElement>('.code-snippets-pagination-page')
	if (pageLabel) {
		pageLabel.textContent = pageLabel.textContent?.replace(/\(\d+\/\d+\)/, `(${page}/${totalPages})`) ?? pageLabel.textContent
	}

	const disableFirstPrev = page <= 1
	const disableNextLast = page >= totalPages

	const setDisabled = (action: PaginationAction, disabled: boolean) => {
		const link = controls.querySelector<HTMLAnchorElement>(`a[data-action="${action}"]`)
		if (link) {
			link.setAttribute('aria-disabled', disabled ? 'true' : 'false')
		}
	}

	setDisabled('first', disableFirstPrev)
	setDisabled('prev', disableFirstPrev)
	setDisabled('next', disableNextLast)
	setDisabled('last', disableNextLast)

	const queryArg = controls.dataset.queryArg
	if (queryArg) {
		const firstLink = controls.querySelector<HTMLAnchorElement>('a[data-action="first"]')
		const baseHref = firstLink?.href

		if (baseHref) {
			const buildHref = (targetPage: number) => {
				const url = new URL(baseHref)

				if (targetPage <= 1) {
					url.searchParams.delete(queryArg)
				} else {
					url.searchParams.set(queryArg, String(targetPage))
				}

				return url.toString()
			}

			const getLink = (action: PaginationAction) =>
				controls.querySelector<HTMLAnchorElement>(`a[data-action="${action}"]`)

			const first = getLink('first')
			if (first) first.href = buildHref(1)

			const prev = getLink('prev')
			if (prev) prev.href = buildHref(Math.max(1, page - 1))

			const next = getLink('next')
			if (next) next.href = buildHref(Math.min(totalPages, page + 1))

			const last = getLink('last')
			if (last) last.href = buildHref(totalPages)
		}
	}
}

const replaceSnippetItems = (status: PaginationStatus, snippets: SnippetResponseItem[]) => {
	const menuNode = getMenuNode(status)
	if (!menuNode) return

	const subMenu = menuNode.querySelector<HTMLUListElement>('ul.ab-submenu')
	if (!subMenu) return

	subMenu.querySelectorAll('li.code-snippets-snippet-item').forEach(node => node.remove())

	const insertAfterId = status === 'active'
		? 'wp-admin-bar-code-snippets-active-pagination'
		: 'wp-admin-bar-code-snippets-inactive-pagination'

	const insertAfter = subMenu.querySelector<HTMLLIElement>(`#${insertAfterId}`)
	const fragment = document.createDocumentFragment()

	for (const snippet of snippets) {
		const li = document.createElement('li')
		li.id = `wp-admin-bar-code-snippets-snippet-${snippet.id}`
		li.className = 'code-snippets-snippet-item'

		const a = document.createElement('a')
		a.className = 'ab-item'
		a.href = buildEditUrl(snippet.id)
		a.textContent = formatSnippetTitle(snippet)

		li.appendChild(a)
		fragment.appendChild(li)
	}

	if (insertAfter && insertAfter.parentNode === subMenu) {
		subMenu.insertBefore(fragment, insertAfter.nextSibling)
	} else {
		subMenu.appendChild(fragment)
	}
}

const navigateToPage = async (status: PaginationStatus, targetPage: number) => {
	const controls = getPaginationControls(status)
	if (!controls) return

	const { totalPages: currentTotalPages } = getPaginationState(controls)
	const page = Math.max(1, Math.min(targetPage, currentTotalPages))

	setLoading(controls, true)

	try {
		const { snippets, totalPages } = await fetchSnippetsPage(status, page)
		updatePaginationControls(controls, page, totalPages)
		replaceSnippetItems(status, snippets)
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error(error)
	} finally {
		setLoading(controls, false)
	}
}

const handlePaginationClick = (event: MouseEvent) => {
	const target = event.target as Element | null
	if (!target) return

	const link = target.closest<HTMLAnchorElement>('.code-snippets-pagination-controls a[data-action]')
	if (!link) return

	const controls = link.closest<HTMLElement>('.code-snippets-pagination-controls')
	if (!controls) return
	if (controls.dataset.loading === 'true') return

	const status = controls.dataset.status as PaginationStatus | undefined
	const action = link.dataset.action as PaginationAction | undefined

	if (!status || !action) return

	event.preventDefault()
	event.stopPropagation()
	event.stopImmediatePropagation()

	const { page, totalPages } = getPaginationState(controls)

	let targetPage = page
	switch (action) {
		case 'first':
			targetPage = 1
			break
		case 'prev':
			targetPage = page - 1
			break
		case 'next':
			targetPage = page + 1
			break
		case 'last':
			targetPage = totalPages
			break
	}

	if (targetPage === page || targetPage < 1 || targetPage > totalPages) {
		return
	}

	void navigateToPage(status, targetPage)
}

if (config) {
	document.addEventListener('click', handlePaginationClick, true)
}
