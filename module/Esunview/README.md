Esunview Module
===============

Esunview is a donationware, open source [Lipupini](https://github.com/lipupini/lipupini) module that allows you to sell high resolution photographs using Stripe's Payment Links API.

An example is here: https://c.dup.bz/@gallery

You can use it unrestricted with no time limit, and a donation link is available here: https://buy.stripe.com/5kA7tY4KJ0hvcpO4h4

Hopefully you can make some kind of income with it! What it ends up creating on the Stripe side is a nice organization of digital products and orders:

![image](https://github.com/groovenectar/lipupini/assets/595446/df460045-f824-43b8-a009-e6e951c54cf1)


## Brief Rundown

- Needs a `composer install` in the Esunview folder to add Stripe SDK.
- The `stripeKey` setting in `system/config/state.php` is where your Stripe API secret key is stored.
- `Esunview` uses some custom requests specified in `system/config/state.php` that differ from default Lipupini.
- By adding a `watermark.png` to a collection's `.lipupini` folder, the system will know selling is enabled for the collection.
- Thumbnails and medium size images get watermarked in static cache. Large size is in a private cache location, and not statically served.
- Large images are inaccessible to public download without payment via Stripe.
- The bundled configuration is $1 minimum per photo, and visitors can add more if they will.
