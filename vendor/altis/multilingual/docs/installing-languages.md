# Installing Languages

You should install language files for any language you want to support in your project. The language files contain translations for all static codebase strings that appear either in the admin interface or on the front end of your website. To install a language, first get the language code. This is the [ISO 639](https://en.wikipedia.org/wiki/ISO_639) two letter language code, followed by the [ISO 3166](https://en.wikipedia.org/wiki/ISO_3166) country code. For example, Brazilian Portuguese would be `pr_BR`.

Once you know the language codes you want to install, use the following CLI commands to download and setup the language files:

```sh
wp language core install pt_BR
wp language plugin install --all pt_BR
```

This will download the language files in to the `content/languages` directory. These files should be committed to version control and deployed.
