import van from '/lib/van-1.2.1.min.js'

const { div, video, source } = van.tags

const Video = ({collection, filename, data}) => {
	return div({class: 'video'},
		video({controls: 'true', preload: 'metadata', loop: 'true', title: data.caption ?? filename},
			source({src: `/c/file/${collection}/large/${filename}#t=0.5`, type: 'video/mp4'}),
		),
	)
}

export { Video }