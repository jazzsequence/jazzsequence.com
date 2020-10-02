# WordPress

The SSO module allows using a WordPress site as the authentication provider for the Altis site. This works via OAuth 2.0, as such the WordPress site you want to use as the authentication provider must be using the [OAuth 2.0 WordPress Plugin](https://github.com/WP-API/OAuth2).

To enabled WordPress SSO, you must create an OAuth 2 application on the WordPress site, and set the Callback URL on the OAuth client to `$site_url/hm-delegated-auth-callback`, for example `https://my-site.altis.dev/hm-delegated-auth-callback`. Once you have published and approved the OAuth application on your WordPress site, take note of the Public Key for the client.

Provide the REST BASE of your WordPress site (usually `https://examples.com/wp-json/`), along with the OAuth client id (public key) obtained in the previous step. This is done via the `sso.wordpress` setting option:

```json
"sso": {
	"wordpress": {
		"server-rest-base": "https://examples.com/wp-json/",
		"oauth2-client-id": "XXXXXXXXXXX"
	}
}
```

You can optionally configure the WordPress SSO provider to synchronize user roles from the WordPress site. When the `wordpress.sync-roles` setting is set to `true`, the user's role on the WordPress site will be used when creating the user on the Altis site.
