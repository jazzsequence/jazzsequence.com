# Translating Content

The Altis multilingual architecture uses a site for each language. This enables great flexibility in publishing workflows, as each language can have it's own information architecture, content, editorial team and even features.

## Create a site for a language

Creating a new site is done via the admin interface. Navigate to the My Sites -> Network Admin -> Sites -> Add Site page to add your site for the new language. By convention, you can use the [ISO 639](https://en.wikipedia.org/wiki/ISO_639) two letter language code, followed by the [ISO 3166](https://en.wikipedia.org/wiki/ISO_3166) country code as the site URL. For example, to create a French site use the URL `/fr-fr`.

Once you have created the site for your localized version of the primary site, you can set the language in the dashboard for that site. Head to the General Settings page of your newly created site and select the Site Language field. Only languages that you have already installed will show in this list. See [Installing Languages](installing-languages.md) for instructions.

Setting the site's language will set the administrative interface's language and also any localized strings that show on the front end. Any user can optionally set their preferred language by editing their profile. This acts as a per-user override for the admin interface.
