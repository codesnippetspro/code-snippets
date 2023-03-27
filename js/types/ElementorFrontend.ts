export interface ElementorFrontend {
	hooks: {
		addAction: (action: string, callback: (...args: unknown[]) => void, priority?: number, context?: unknown) => void
	}
}
