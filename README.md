# Lipupini

## Media File Organization System

### Version 4.x

---

[Status](#status)

[Features](#features)

[Lipupini Docker Setup](system/DEPLOY.md#deploying-with-docker)

[Starting Lipupini with PHP](#starting-the-php-webserver)

[Add Your Collection](#add-your-collection)

[ActivityPub Note](#activitypub-note)

[The Name](#the-name)

[Demo](#demo)

[Contributing](#contributing)

[Acknowledgements](#acknowledgements)

---

![Example UI Screenshot](https://github.com/lipupini/lipupini/assets/108841276/e6dd6f61-5e7d-4621-bff4-3ce33501acf8)


---

## Status

For displaying a media portfolio or posts on the Internet, 	despite currently limited ActivityPub support the [latest release of Lipupini](https://github.com/lipupini/lipupini/releases/latest) is considered to be **production-ready**. The [demo site](https://lipupini-demo.dup.bz/@example) is running the `demo` branch which is usually ahead of the latest release.

1) Install dependencies and clone the repository to the [latest release tag](https://github.com/lipupini/lipupini/releases/latest). While demo site has maintained since version `1.x`, avoid using the `demo` branch in production unless you are interested in keeping up with development and have a mechanism for emergency rollbacks.
2) Add and [initialize](#add-your-collection) your collection, customize `.lipupini/files.json` with captions, and delete the example collection.
3) Ensure that your files display. If they don't, convert them to supported formats.
4) Deploy to a PHP server.

Updating Lipupini can be as simple as running `git pull` from your environment depending on the setup.

## Features

- Support for the following media file formats: AVIF, FLAC, GIF, JPG, PNG, M4A, MP3, MP4, Markdown, OGG Vorbis
- Allows subscribing to your content collection via RSS2.0.
- Search Lipupini accounts from other Fediverse platforms via the ActivityPub protocol.
- Automatically fix image orientation and strip private metadata thanks to [Imagine](https://github.com/php-imagine/Imagine) library
- With [ffmpeg](https://ffmpeg.org) available and `useFfmpeg` [enabled](system/config/state.php), video thumbnails and audio waveforms can be generated automatically.
- Supports both `"hidden"` and `"unlisted"` options in [files.json](collection/README.md)
- Once dependencies are installed, Lipupini is designed to get up and running quickly.
- Media collections are self-contained, served as they are on your filesystem. Lipupini-specific collection files are stored in a special `.lipupini` folder, making account collections completely portable.
- Module system paves a way for collaborative development.
- Docker support. See [deployment instructions](system/DEPLOY.md#deploying-with-docker).
- Show an avatar PNG when searching from an external ActivityPub or RSS client.
- Lipupini manages to implement ActivityPub without a database. Certain inbox activities can be logged to your collection in raw JSON. See `system/config/state.php` for the option.
- Minimalist grid layout. Frontend is ready to be customized, or you can make an entirely new frontend module.
- On-demand caching system creates and serves static media files. Support for custom caching URL can facilitate the use of a CDN.
- [Public domain](LICENSE.md) source code is the most permissive license there is. You can do whatever you want with this thing. Please feel free to contribute back to upstream, post in discussions, etc. There is no obligation of any kind.

## Starting the PHP Webserver

Make sure all [dependencies are installed first](system/DEPLOY.md#installing-system-dependencies).

1) Clone the app and `cd` into the project root

```shell
git clone https://github.com/lipupini/lipupini.git
cd lipupini
```

2) Install Composer dependencies and go back to project root

```shell
cd module/Lipupini
composer install
cd ../..
```

3. Navigate to the webserver document root and start PHP's built-in webserver. See [module/Lukinview/README.md](module/Lukinview/README.md) for more information.

```shell
cd module/Lukinview/webroot
PHP_CLI_SERVER_WORKERS=2 php -S localhost:4000 index.php
```

4. Visit http://localhost:4000/@example

## Add Your Collection

Say you have a folder of awesome photos at `/home/sally/Pictures/AwesomePhotos`

Your Lipupini installation is at `/opt/webapp/lipupini`

1) Take the photos from `/home/sally/Pictures/AwesomeCollection` and put them into the collection directory `/opt/webapp/lipupini/collection/sally` either by copying them:

```shell
cp -R /home/sally/Pictures/AwesomeCollection /opt/webapp/lipupini/collection/sally
```

or symlinking them:

```shell
ln -s /home/sally/Pictures/AwesomeCollection /opt/webapp/lipupini/collection/sally
```

2) Initialize the `.lipupini` folder for the collection

```shell
cd /opt/webapp/lipupini
bin/generate-keys.php sally
bin/create-files-json.php sally
```

3) Save a file called `avatar.png` at `/opt/webapp/lipupini/collection/sally/.lipupini/avatar.png`

4) Edit the file at `/opt/webapp/lipupini/collection/sally/.lipupini/files.json` to add captions (this is optional)

5) Delete the example collection:

```shell
rm -r collection/example
```

6) Your collection should now be viewable at http://localhost:4000/@sally

In addition to copying or symlinking, see [collection/README.md#vision](collection/README.md#vision) for ideas on other ways to keep these directories in sync.

## ActivityPub Note

ActivityPub transactions should occur via HTTPS per spec. https://ngrok.com can be used to get up and running quickly for development.

For example, after starting Lipupini you can run this command:

```shell
ngrok http 4000
```

Continuing with the example above in "Add Your Collection," if your Ngrok URL becomes `https://f674-73.ngrok-free.app`, you should then be able to query `@sally@f674-73.ngrok-free.app` from another Fediverse client once the collection is initialized.

Using Ngrok, with an upgraded plan you can setup a fairly restrictive port firewall, configure it to run on startup, and reliably host any domain with HTTPS.

## The Name

"Lipupini" is a "word formed by combining other words" (portmanteau), and "lipu pini" in this context translates to "past document" in [Toki Pona core](https://zrajm.github.io/toki-pona-syllabics/dictionary/). Lipupini is for organizing computer files like images, videos, sounds and writings that you might want to display under your domain on the Internet.

## Demo

Here is what it can look like so far: https://lipupini-demo.dup.bz/@example

Though ActivityPub implementation is currently limited, the demo is searchable in the Fediverse `@example@lipupini-demo.dup.bz`

**NOTE:** Please use [activitypub.academy](https://activitypub.academy) Mastondon server for testing, as this is a test server.

The demo is the `demo` branch running on Apache2. If you already have Apache2 configured to serve PHP, you can install Composer dependencies and point the virtual host's `DocumentRoot` to `webroot` and it should "just work."

## Contributing

You are welcome to fork it, change it, add modules! Please don't hesitate to make a PR that includes your own modules - it could be shipped with or integrated into core.

I hope that the module architecture makes for a good workflow, especially being open to merging new modules. In theory, modules could just as easily be Composer packages and not have a `module` directory at all. The current architecture can still work seamlessly with the Composer pattern as well.

Email apps [at] dup.bz if you'd like a point of contact or post in [discussions](https://github.com/lipupini/lipupini/issues)! Please reach out if you begin to find any aspect frustrating or feel that it should be done in a different way.

If you want to use Lipupini for your artist portfolio or business website, I will support your effort.

## Acknowledgements

VanJS: https://vanjs.org

Markdown parser: https://parsedown.org

Image processor: https://github.com/php-imagine/Imagine

Arrow icons: https://www.svgrepo.com/author/Pictogrammers

ActivityPub inspiration: [@dansup@pixelfed.social](https://pixelfed.social/dansup) & [Landrok's ActivityPub library](https://github.com/landrok/activitypub)

## TODO

- Add browser-side caching
- Add option for favicon in collection `.lipupini` folder
- `bin/generate-files-json.php`
  - Make recursive
  - Do not overwrite entries, or add option
  - Read EXIF data if available for setting a default `date`
- Figure out something else besides exception when file in `files.json` does not exist in collection
- Create script to normalize file and directory user/group/permissions
- Output errors in layout
- Check on cross-platform compatibility, MacOS and Windows with and without Docker. While only tested on Linux, I believe it will work on all three OSes including `ffmpeg` interfaces as long as symlinking is supported.
- Update CLI commands for `ffmpeg` to take a collection folder name and collection path, determine output path automatically
- Look into:
  - https://indieweb.org/Webmention
  - https://indieweb.org/Microsub
  - https://indieweb.org/Micropub
  - https://atproto.com
  - https://micropub.rocks
- Make contributions to `landrok/activitypub`
- Detect `readline` support in a custom `confirm` routine. If `readline` is not available a 10-second timer will be indicated that can be cancelled with CTRL+C.
- Create a mechanism for writing to a collection's `files.json`
  - Could start with a CLI tool, e.g. `bin/caption.php <collection> <filename> <caption>`
  - Would like to try creating a desktop tool
- Do not let same account try to follow more than once when already logged previous follow
- If a photo is taking a while to upload and a browser pageload is triggered with media processor requests in the request queue, the thumbnail version will likely only show a partial image.
  - Removing all media processors from the HTTP request queue and only using `bin/process-media.php` after uploading is a solution.
  - File transfer clients that use temporary files during transfer are a solution.
  - Adding a file watcher daemon or transfer queue could help with an alternative solution.
  - I would like to know if there is a way using pure PHP to detect if a file is still uploading via SFTP.
