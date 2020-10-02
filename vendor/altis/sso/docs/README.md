# Single Sign On

![](./assets/banner-sso.png)

The SSO module allows you to delegate the authorization and user management of your site to an external service. This is not uncommon in organizations where user management is handled by a central server.

The SSO module provides built in support for popular authorization protocols. The implementation of the authorization client all work in mostly the same way. The SSO provider is used to authenticate a user, typically via a web redirect. Once the user has authenticated, they are redirected back to the Altis application, where their user record is imported into the CMS.

Once the CMS has a user record in the database, the user's session is authorized and logged in to that account. Any user operations from that point on are treating as regular CMS user operations, against the local "mirrored" user record.
