import van from '/lib/van-1.2.1.min.js'
import { Video } from '../FileType/Video.js'
import { Image } from '../FileType/Image.js'
import { Audio } from '../FileType/Audio.js'
import { Markdown } from '../FileType/Markdown.js'
import { Folder } from '../FileType/Folder.js'

const Document = ({collection, filename, data}) => {
	switch (filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2)) {
		case 'jpg':
		case 'png':
			van.add(document.getElementById('media-item'), Image({collection, filename, data, background: false}))
			break
		case 'mp4':
			van.add(document.getElementById('media-item'), Video({collection, filename, data}))
			break
		case 'mp3':
			van.add(document.getElementById('media-item'), Audio({collection, filename, data}))
			break
		case 'md':
			van.add(document.getElementById('media-item'), Markdown({collection, filename, data, load: true}))
			break
		case '':
			van.add(document.getElementById('media-item'), Folder({collection, filename, data}))
			break
		default:
			throw new Error('Unknown file extension')
	}
}

Document({ collection, filename, data: fileData })
