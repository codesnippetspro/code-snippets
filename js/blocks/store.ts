import { createReduxStore, register, select, StoreConfig } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { SnippetData } from '../types';

export const STORE_KEY = 'code-snippets/snippets-data'

const TYPES = {
	SET: 'SET_SNIPPETS_DATA',
	RECEIVE: 'RECEIVE_SNIPPETS_DATA'
}

const DEFAULT_STATE = {
	snippetsData: [],
	loading: true,
}

interface Store {
	snippetsData: SnippetData[]
}

const actions: StoreConfig<Store>['actions'] = {
	setSnippetsData: snippetsData => ({
		type: TYPES.SET,
		snippetsData,
	}),
	receiveSnippetsData: path => ({
		type: TYPES.RECEIVE,
		path,
	}),
}

const config: StoreConfig<Store> = {
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
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const snippetsData = yield actions.receiveSnippetsData('/code-snippets/v1/snippets-info/');
			return actions.setSnippetsData(snippetsData);
		},
	},
}

const store = createReduxStore(STORE_KEY, config);
register(store);
