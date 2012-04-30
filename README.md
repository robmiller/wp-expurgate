# wp-expurgate

As the web becomes increasingly SSL-ified, the possibilities for mixed content — serving non-HTTP resources in an HTTPS page — increases too. This fixes that.

## How does it work?

wp-expurgate adds support for [expurgate][] to WordPress. Expurgate is a script that allows you to filter non-HTTP resources (just images, for now) through an HTTPS URL. In the eyes of browsers, this scrubs them clean and stops them causing warnings.

[expurgate]: https://github.com/robmiller/expurgate

## Got an example?

Say you have a post with the following image in it:

	<img src="http://example.net/foo.jpg">

If a user visits your page over HTTPS, their browser will give them a warning — or in the case of IE, an ugly, scary alert box — telling them that the page isn't fully secure.

wp-expurgate will correct this to the following:

	<img src="https://example.com/[...]/expurgate.php?checksum=[checksum]&url=http://example.net/foo.jpg">

…which all browsers are happy with, since it's over HTTPS.