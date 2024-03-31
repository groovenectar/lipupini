import van from '/lib/van-1.5.0.min.js'

const { a, div, object } = van.tags

const Text = ({collection, baseUri, filename, data, load = false}) => {
	return div({class: 'text'},
		load ?
			object({type: 'text/html', data: `${baseUri}${collection}/text/${filename + '.html'}`}) :
			a({href: `/@${collection}/${filename}.html`},
				div(data.caption ?? filename.split(/[\\\/]/).pop()),
			)
	)
}

export { Text }
