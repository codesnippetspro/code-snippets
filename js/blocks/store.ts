import { registerStore } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const actions = {
	setSnippetsData(snippetsData) {
		return {
			type: 'SET_SNIPPETS_DATA',
			snippetsData,
		};
	},
	receiveSnippetsData(path) {
		return {
			type: 'RECEIVE_SNIPPETS_DATA',
			path,
		};
	},
};

registerStore('code-snippets/snippets-data', {
	reducer(state = { snippetsData: {} }, action) {
		if ('SET_SNIPPETS_DATA' === action.type) {
			return { ...state, snippetsData: action.snippetsData };
		}
		return state;
	},
	actions,
	selectors: {
		receiveSnippetsData(state) {
			const { snippetsData } = state;
			return snippetsData;
		},
	},
	controls: {
		RECEIVE_SNIPPETS_DATA(action) {
			return apiFetch({ path: action.path });
		},
	},
	resolvers: {
		* receiveSnippetsData() {
			const snippetsData = yield actions.receiveSnippetsData('/code-snippets/v1/snippets-info/');
			return actions.setSnippetsData(snippetsData);
		},
	},
});
