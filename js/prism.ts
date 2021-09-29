import * as Prism from 'prismjs';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-css';
import 'prismjs/plugins/line-numbers/prism-line-numbers';
import 'prismjs/plugins/toolbar/prism-toolbar';
import 'prismjs/plugins/show-language/prism-show-language';

document.onreadystatechange = () => {
	if ('complete' === document.readyState) {
		Prism.highlightAll();
	}
};
