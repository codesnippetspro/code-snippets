import { registerStore } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const TYPES = {
	SET: 'SET_SNIPPETS_DATA',
	RECEIVE: 'RECEIVE_SNIPPETS_DATA'
}

const DEFAULT_STATE = {
	snippetsData: [],
	loading: true,
}

const actions = {
	setSnippetsData: snippetsData => ({
		type: TYPES.SET,
		snippetsData,
	}),
	receiveSnippetsData: path => ({
		type: TYPES.RECEIVE,
		path,
	}),
};

registerStore('code-snippets/snippets-data', {
	reducer: (state = DEFAULT_STATE, action) => {
		if (TYPES.SET === action.type) {
			const { snippetsData } = action
			return { ...state, snippetsData, loading: false };
		}
		return state;
	},
	actions,
	selectors: {
		receiveSnippetsData: ({ snippetsData }) => snippetsData,
	},
	controls: {
		[TYPES.RECEIVE]: action => apiFetch({ path: action.path }),
	},
	resolvers: {
		receiveSnippetsData: function * () {
			const snippetsData = yield actions.receiveSnippetsData('/code-snippets/v1/snippets-info/');
			return actions.setSnippetsData(snippetsData);
		},
	},
});
