# jazzsequence.com
[![Test scripts](https://github.com/jazzsequence/jazzsequence.com/actions/workflows/test-scripts.yml/badge.svg)](https://github.com/jazzsequence/jazzsequence.com/actions/workflows/test-scripts.yml)
![jazzsequence.com 10 December, 2020](https://sfo2.digitaloceanspaces.com/cdn.jazzsequence/wp-content/uploads/2020/12/10120020/Screen-Shot-2020-12-10-at-11.59.56-AM.png)

## Workflow

* `dev` is the default branch.
* New features branch off `dev` or are pushed straight to `dev`.
* `main` is the production branch.
* When a release is ready, `dev` merges into `main`.
* `main` auto-deploys to production.
* 🎉

## Technologies
[jazzsequence.com](https://jazzsequence.com) is powered by the following technologies:

* [WordPress](https://wordpress.org)
* [Altis DXP](https://altis-dxp.com)
* [Composer](https://getcomposer.org/)
* [Shellcheck](https://www.shellcheck.net/)
* [Bats](https://bats-core.readthedocs.io/en/stable/)
* [GitHub Actions](https://docs.github.com/en/actions)
* [Open LiteSpeed](https://openlitespeed.org/)
* [Digital Ocean](https://m.do.co/c/36c3e7160e43)
* [Deploy HQ](https://www.deployhq.com/r/8hnhpr)

<!-- TODO -->
<!--
* Move my plugins that are composerized to packagist so I don't need to use them as composer repositories
* composerize plugins that arne't already so we can move them to packagist
* move the deploy workflow off of DeployHQ and onto GitHub Actions
* change the dashboard "home" link to point back to the regular dashboard rather than the altis dashboard
* use a pantheon sandbox to test the actual site before deploying to production
-->
