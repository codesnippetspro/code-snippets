import React from 'react'

export interface SuggestionListProps {
	id: string
	suggestions: string[]
	onSelect: (item: string) => void
}

export const SuggestionList: React.FC<SuggestionListProps> = ({ id, suggestions, onSelect }) =>
	<div className="tagger-completion">
		<datalist id={id}>
			{suggestions.map(suggestion =>
				<option
					key={suggestion}
					value={suggestion}
					onClick={() => onSelect(suggestion)}
				/>
			)}
		</datalist>
	</div>
