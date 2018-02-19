# EvangelicalMagazinePlugin
Custom WordPress plugin for the [Evangelical Magazine website](https://www.evangelicalmagazine.com/). Provided here as a help to other developers who are creating WordPress sites for print magazines. This plugin adds Custom Post Types for articles, authors, issues, reviews, sections and series, plus with dozens of helpful methods. It's compatible with the [Instant Articles for WP](https://github.com/Automattic/facebook-instant-articles-wp) plugin, and is designed to be used in conjunction with the [EvangelicalMagazineTheme](https://github.com/markbarnes/EvangelicalMagazineTheme).

## Features
* The plugin supports articles, authors, issues, reviews, series and sections
* Articles can have multiple authors and be part of multiple sections
* Integrates with Google Analytics API to calculate trending articles
* Integrates with Facebook API to display article likes

## Google Analytics integration
The plugin (optionally) uses Google Analytics data to calculate the most viewed articles. To configure this:
1. Download the latest version of [`google-api-php-client.zip`](https://github.com/google/google-api-php-client/releases).
2. Extract the `src` and `vendor` folders, and place them in `libraries/google-api-client`
3. Open `google-api-credentials_sample.json` and follow the instructions there.

If Google Analytics is not configured, the plugin will default to its own simple statistics.

## Support
No support is offered for this plugin. It was not created with distribution in mind, has no user-configurable options and although it may work out of the box in another context (so long as you edit `classes/fb_access_tokens_sample.php`), it's intended to be forked. It's published here purely as a help to other developers (particularly those working for non-profits) who need something similar and want to minimize development time.

## License
This plugin is developed and copyrighted by [Mark Barnes](https://www.markbarnes.net) and licenced under [GPLv3](http://www.gnu.org/licenses/gpl.html).