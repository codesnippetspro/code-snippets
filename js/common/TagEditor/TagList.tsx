import React from 'react'

export interface TagListProps {
	tags: string[]
	onRemove: (tag: string) => void
}

export const TagList: React.FC<TagListProps> = ({ tags, onRemove }) =>
	<>
		{tags.map(tag =>
			<li key={tag}>
				<span>
					<span className="label">{tag}</span>
					<a href=".#" className="close" onClick={event => {
						onRemove(tag)
						event.preventDefault()
					}}>×</a>
				</span>
			</li>
		)}
	</>
