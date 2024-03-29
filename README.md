# PHP Siteframework
This is the 2022, refactored, improved, renamed, version of [this old gem](https://github.com/Cybergate9/phpSiteFramework)..

Change log in [SF_changelog.md](https://github.com/Cybergate9/PHP-Siteframework/blob/master/_SF_modules/SF_changelog.md)

## Version 2022.xx.xx

Version 2022.xx.xx adds:

* mainconfig and (newly created) localconfig separate some concerns making it easier to update mainconfig on remote host

* `_SF_modules/admin/index.html` shows config for sanity checks

* ability to deliver markdown content (requires [Parsedown](https://parsedown.org/) and [Parsedown Extra](https://github.com/erusev/parsedown-extra))

	* via simple yaml front matter processing, all processed into global $SF_commands[] making them available 'everywhere' (shortcodes: yes, summary: xx, lightbox:yes, htmx: yes, hypersript: yes, implemented as functionality as opposed to meta)

* fully configurable caching, into hash directory tree if desired, with no external dependencies, now fully tested

* {{shortcode}} processor, enabled by front matter shortcode:yes on a page by page basis. 

	* all shortcodes will have matching scf_* PHP function to process 

	* currently available:

		* scf_vb (verbatim, wrap everything in htmlspecialchars),

		* scf_img (do html image rendering out of standard images/web500 dir etc)

		* scf_lbimg (do a javascript, lightbox image rendering out of standard images/web500 dir etc, req. yaml lightbox:yes, shortcodes: yes)

		* scf_lbgallery (do a javascript, lightbox'ed image gallery with exif out of standard images/web500 dir etc. req. yaml lightbox:yes, shortcodes: yes)

* SF_urlpreviews.php provides ability to look up metadata for 'refurl's, store them, and display them

* javascript libraries ([venobox](https://veno.es/venobox/), [htmx](https://htmx.org/), [hyperscript](https://hyperscript.org/)) can easily be included via defaultheader.html by setting in yaml front matter, or SF_commands (assumed their installation is via Composer)

* defaultheader and defaultfooter now tidied up and shortened, but still have some specifics from my website build for time being as it provides decent examples of 'real world use'

* [et-book](https://github.com/edwardtufte/et-book/tree/gh-pages/et-book) added (in extras) and becomes default font through SF_default.css

* [PHPDocumentor](https://phpdoc.org/) generated docs (for what they're worth :-P) in docs/ directory








