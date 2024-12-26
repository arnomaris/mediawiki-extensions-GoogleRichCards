# GoogleRichCards</h1>

**GoogleRichCards** is an MediaWiki extension to generate Google Rich Cards metadata for article pages. It is forked from [Extension:GoogleRichCards](https://www.mediawiki.org/wiki/Extension:GoogleRichCards)

## Features
The extension adds Google Rich Card JSON-LD metadata to each "content page" of your MediaWiki installation.
Currently it supports the following types:

 * Article

## Requirements
* [MediaWiki](https://www.mediawiki.org/) 1.39 or later

## Installation
1. Download and place file(s) in a directory called `GoogleRichCards` in your `extensions/` folder.
```console
git clone https://github.com/arnomaris/mediawiki-extensions-GoogleRichCards.git GoogleRichCards
```
2. Add the following code at the bottom of your LocalSettings.php file:
```php
wfLoadExtension('GoogleRichCards');
```
3. **✔️Done** - Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

