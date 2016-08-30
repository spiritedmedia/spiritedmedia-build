# Deployments

## Releases / Deployment / Pantheon Specifics

The `master` branch is automatically synced with the dev environment at http://dev.billypenn.com/ .

This is made possible through DeployBot (managed by Wildbit in Philadelphia).

However, in order to automatically deploy to Pantheon, the dev environment must be set to SFTP mode, not Git mode. Then, because files are added manually through SFTP mode, Pantheon will notify you that there are n modified or untracked files.

This means lead developers must manually commit the deployed changes on Pantheon. It should have a descriptive commit message as described below.

### Compiled Assets

Thanks to the power of DeployBot, we can keep our compiled CSS and JS and dependencies out of source control.

DeployBot will execute the `./install` script upon first deployment, and `grunt build` for each fresh deployment after that.

This allows us to:

- Reduce the amount of dependency code in our repo
- Avoid merge conflicts caused by compiled assets
- Keep diffs to a minimum
- Ensure lint errors are fixed before successful deployment
- Stop worrying about whether a commit included the relevant compiled changes

### Releases

GitHub releases and tags represent deployments to live. The most recent release will always reflect the current state of the live site.

Releases do not include things like plugin updates and core updates, as these are not part of Pedestal.

In preparation for deployment from dev → test/staging → live, core developers should roll all recent changes into a release.

Releases should include:

- A Git tag named with the version number (version numbers described below)
- A GitHub release (more info below)

Thankfully, most of this process is now automatic. It used to take a long time.

### Version Numbers

Releases use the [Semantic Versioning](http://semver.org/) system for numbering, handled by `grunt-bump`.

> Given a version number MAJOR.MINOR.PATCH, increment the:
>
> - MAJOR version when you make incompatible API changes,
> - MINOR version when you add functionality in a backwards-compatible manner, and
> - PATCH version when you make backwards-compatible bug fixes.
>
> Additional labels for pre-release and build metadata are available as extensions to the MAJOR.MINOR.PATCH format.

### Scheduling

In order to keep a sensible degree of parity between the dev site and the live site, you should deploy to live at least once a week.

Our preferred timing for deployments is in the late evening or Friday. All of our millenial readers are out getting turnt up, so there aren't many eyes on the site. Also, we have all weekend to check for bugs.

### Ship

1. `git checkout master`

1. Make sure your working tree is clean

1. `grunt release`

    - By default, `grunt release` will increment the version number by one patch version e.g. from `1.0.0` to `1.0.1`.

    - If you'd like to increment by a single minor release version, run `grunt release:minor`

    - If you'd like to increment by a single major release version, run `grunt release:major`

1. `grunt publish`

    __Watch out!__

    _This command automatically commits all changes, tags the commit based on the new version number, then pushes everything to origin._

    So watch out, or else you'll have to draft a new release.

1. At this point, DeployBot will automatically deploy to dev.billypenn.com

1. Go to the [tags page](https://github.com/spiritedmedia/spiritedmedia/tags) on the GitHub repo and click "Add release notes".

    The title should be the version name, plus a short description: E.G. `v1.0.1 — reticulating splines`

    Add a link to the full changelog between this version and the last. The format should be `[Full Changelog](https://github.com/spiritedmedia/spiritedmedia/compare/v1.0.0...v1.0.1)`

    Look over the pull requests that were merged between this release and the last one, and list them here with a simple description and the PR number. For example:


		[Full Changelog](https://github.com/spiritedmedia/spiritedmedia/compare/v1.0.0...v1.0.1)

		## New Features

		- Add admin page to bulk subscribe email addresses to newsletter (#1354)
		- Add Story Type taxonomy (#1361)
		- Add field to media library for adding link to media credit (#1363)

		## Bug Fixes + Minor Improvements

		- Escape quotes in FIAS GA page titles (#1362)
		- Schedule daily newsletter subscriber count notification (#1359)
		- Lock non-dev dependencies to version (#1358)
		- Create Admin namespace (#1350)
		- Remove changelog and related `grunt release` tasks (#1349)

1. After deployment is successful, go to the Pantheon dashboard for the dev environment

1. You should see a message like "278 file changes are ready to commit to the dev environment":

    ![](http://cl.ly/image/2o2U1z3f2s3R/Image%202015-01-16%20at%2017%3A05%3A56.png)

1. Type a commit message with the version number (plus optional name) and the URL to the release on GitHub. like so:

    ```
    v1.0.1 — reticulating splines

    https://github.com/spiritedmedia/spiritedmedia/releases/tag/v1.0.1
    ```

1. Commit it.

1. You should see the option to deploy to Test. Do that.

    ![](http://cl.ly/image/2v0q2o0b2M2X/Image%202015-01-25%20at%2010%3A01%3A08.png)

1. When you're all set, click the Live tab and review the changes you're about to deploy.

    ![](http://cl.ly/image/1P182Y1f2K3a/Image%202015-01-25%20at%2010%3A29%3A24.png)

1. If there are any WordPress core or plugin updates, save these for another commit.

1. Deploy!

1. Breathe.

## Clone From Live

Sometimes you may want to work with a recent version of the Live database for development. It is incredibly important that the email addresses from the Live database do not point to real addresses. The steps below will walk you through pulling down a safe version of the Live database to work with.

1. Authenticate with Pantheon with `terminus auth login`

1. From the Pedestal root, run `./bin/clone-live-to-staging.sh` and confirm backups when prompted.

    This script does four things:

    1. Create a backup of the Live environment on Pantheon

    1. Clone the Live database to Test environment

    1. Neutralize the Test database, making email testing safe

    1. Create a backup of the Test database

1. (Optional) Begin downloading uploaded files from Live either by SFTP or from the Pantheon dashboard

1. In the Pantheon dashboard, go to the Test tab and clear caches

1. Log in to staging.billypenn.com (with your username, not your email address) and verify that all email addresses have in fact been neutralized

1. Download the most recent database backup from Test

1. `vagrant ssh` or your preferred method to use WP-CLI for database operations on your local environment &mdash; then navigate to the web root

    ```sh
    # If you want to backup your current database
    wp db export <file>.sql
    wp db reset
    redis-cli
        # From within redis-cli
        FLUSHALL
        exit
    # <file>.sql is the neutralized database from Test
    wp db import <file>.sql
    # <billypenn.dev> is the site URL of your local environment
    wp search-replace 'staging.billypenn.com' '<billypenn.dev>' --precise
    wp search-replace 'billypenn.com' '<billypenn.dev>' --precise
    ```

1. Login to your local site and verify once more that the users all have neutralized email addresses

1. 🍻
