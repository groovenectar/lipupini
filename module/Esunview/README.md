Esunview Module
===============

Esunview is a donationware, open source [Lipupini](https://github.com/lipupini/lipupini) module that allows you to sell high resolution photographs using Stripe's Payment Links API.

An example is here: https://c.dup.bz/@gallery

You can use it unrestricted with no time limit, and a donation link is available here: https://buy.stripe.com/5kA7tY4KJ0hvcpO4h4

Hopefully you can make some kind of income with it!

## Brief Rundown

- Needs a `composer install` in the Esunview folder to add Stripe SDK
- By adding a `watermark.png` to a collection's `.lipupini` folder, the system will know selling is enabled for the collection.
- Thumbnails and medium size images get watermarked in static cache. Large size is in a private cache location, and not statically served.
- Large images are inaccessible to public download without payment via Stripe.
- The bundled configuration is $1 minimum per photo, and visitors can add more if they will.
