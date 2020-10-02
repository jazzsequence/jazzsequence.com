# Code Internationalization

When a project is intended to be localized to multiple languages, all the strings in the codebase must also be translated. Any language strings that are "hardcoded" in PHP or JavaScript files must follow the code internationalization practices to ensure they can be translated into any language you want to support.

This is done by passing all strings that your custom code outputs through the translation API functions. This is used to both swap your strings for their translated variant at runtime depending on the language of the site, and also be used as markers for generating translation template files (POT files).

Once you have generated a POT file for you custom code, you can provide your translators with these files. Typically programs such as [poedit](https://poedit.net/) are used to author translations.

## Translation API Functions

Whenever you output a string in code, you must use a translation function. Altis uses the [WordPress Translation API](https://codex.wordpress.org/I18n_for_WordPress_Developers) to provide code level internationalization (i18n). Translations in code are tied to a "Text Domain" which is an identifier for which translation file should be used for a given translation. It's recommended you use a single translation domain for all your project's custom code. This will mean you only need to get a single POT file translated.

Whenever you "hardcode" strings in PHP, you should wrap them in the appropriate translation function for the context. For example, consider the following code.

```php
<h2>Search Results</h2>
```

To make this translatable, it must be wrapped in a translation function, so the string can be swapped at runtime. In this case the translation function `esc_html_e` (`esc`ape for `html`, `e`cho). It takes the string and text domain, translates it, and escapes the output for use between HTML tags.

```php
<h2><?php esc_html_e( 'Search Results', 'my-text-domain' ) ?></h2>
```

Whenever strings are directly outputted, always use the appropriate escaping translation function. Below is a reference for the translation function that should be used in which context.

|Function|Context|
|-|-|
|`esc_html_e`|Output a translated string between HTML tags|
|`esc_attr_e`|Output a translated string in an HTML element attribute.|
|`esc_html__`|Return a translated string intended for use between HTML tags|
|`esc_attr__`|Return a translated string intended for use in an HTML element attribute.|

When strings are used for further processing, function calls and or more advanced translation usages, the general class of translation functions should be used.

|Function|Description|
|-|-|
|`__( $string, 'my-text-domain' )`|Return the translated string for a given text domain.|
|`_n( $singular_string, $plural_string, $count, 'my-text-domain' )`|Return a the translated string for a singular / plural variation.|
|`_x( $string, $context, 'my-text-domain' )`| Return the translated for for the text domain, specifying a context string to be provided to translators.|

### Dynamic Strings

In situations when the strings contain dynamic data, use placeholders as part of the translation string, and pass the result to `sprintf` (and to an escaping function when outputting.)

```php
<?php
echo esc_html(
	sprintf(
		__( 'We found %d search results.', 'my-text-domain' ),
		$results_count
	)
);
```

See [Translatable Strings](https://codex.wordpress.org/I18n_for_WordPress_Developers#Translatable_strings) for details on the functions you should use, depending on context.

## Generating POT files

Once you have ensured all the strings in your custom code are using translation functions, you must generated a POT file from the codebase. This is done use the cli command `wp i18n make-pot`. If you want to generate a POT file for your whole project:

```sh
wp i18n make-pot content --domain=<your-text-domain> <your-text-domain>.pot
```

This will place a new file at the path `<your-text-domain>.pot`.

## Installing Translated Files

The POT files you create should be passed to your translation team. Their translations should be exported as `.mo` files. Each language will have it's own translated file. Copy the `.mo` files to `content/languages/plugins/<text-domain>-<language-code>-<country-code>.mo`.
