import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import 'highlight.js/styles/stackoverflow-dark.min.css';

hljs.registerLanguage('json', json);
hljs.highlightAll();
