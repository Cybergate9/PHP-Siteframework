# PHP Siteframework
This is the 2022, version 2, renamed, version of [this old gem](https://github.com/Cybergate9/phpSiteFramework)..

## Version 2

Version 2 adds:

* mainconfig and (newly created) localconfig separate some concerns making it easier to update mainconfig on remote host

* `_SF_modules/admin/index.html` shows config for sanity checks

* ability to deliver markdown content (requires [Parsedown](https://parsedown.org/) and [Parsedown Extra](https://github.com/erusev/parsedown-extra))

	* include simple yaml front matter processing, all processed into global $SF_commands[] making them available 'everywhere'

* fully configurable caching, into hash directory tree if desired, with no external dependencies

* {{shortcode}} processor, enabled by front matter shortcode:yes on page by page basis. 

	* all shortcodes will have matching scf_* PHP function to process 

	* currently available:

		* scf_vb (verbatim, wrap everything in htmlspecialchars),

		* scf_img (do html image rendering out of standard images/web500 etc)

		* scf_lbgallery (do a javascript, lightbox'ed image gallery)








