(function (wp) {
	const {registerStore} = wp.data;

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
		reducer(state = {snippetsData: {}}, action) {
			if (action.type === 'SET_SNIPPETS_DATA') {
				return {...state, snippetsData: action.snippetsData};
			}
			return state;
		},
		actions,
		selectors: {
			receiveSnippetsData(state) {
				const {snippetsData} = state;
				return snippetsData;
			},
		},
		controls: {
			RECEIVE_SNIPPETS_DATA(action) {
				return wp.apiFetch({path: action.path});
			},
		},
		resolvers: {
			* receiveSnippetsData(state) {
				const snippetsData = yield actions.receiveSnippetsData('/code-snippets/v1/snippets-info/');
				return actions.setSnippetsData(snippetsData);
			},
		},
	});

}(window.wp));
