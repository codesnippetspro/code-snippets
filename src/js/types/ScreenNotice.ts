import type { ReactNode } from 'react'

export type ScreenNotice = ['error' | 'warning' | 'updated', ReactNode, ReactNode?, string?]
