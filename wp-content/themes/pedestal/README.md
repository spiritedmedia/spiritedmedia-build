# pedestal

## Requirements

- PHP 5.5
- NodeJS
- Composer
- Ruby 2.2.2 or [RVM (Ruby Version Manager)](https://rvm.io/)
- MySQL
- [Bundler](http://bundler.io/)
- [EditorConfig](http://editorconfig.org/)

## Setup

```
$ git clone git@github.com:spiritedmedia/pedestal.git --recursive
$ cd pedestal
$ ./install
```

### Unit Tests

You will also need to install the unit testing suite, which must be done through your local MySQL server. In the case of Vagrant, this means running the install script via `vagrant ssh`.

If you are in fact running Vagrant, try this from the Pedestal root directory:

```
$ vagrant ssh
$ cd /path/to/pedestal/
$ grunt installTests
```

Before you run tests, you'll want to have MySQL installed and running locally. This is pretty easy to get set up for OS X:

```
$ brew install mysql
$ mysqladmin -u root password root
```

And just run `mysql.server start` to get it started. `mysql.server stop` will... you guessed it, stop MySQL.

### Icon Generation (Optional)

If you plan on generating SVG/PNG icons with [Font-Awesome-SVG-PNG](https://github.com/encharm/Font-Awesome-SVG-PNG), make sure to install `librsvg` on your system beforehand.

If you're using OSX Homebrew, just run `brew install librsvg`.

For Ubuntu/Debian:

```
sudo apt-get update -y
sudo apt-get install librsvg2-bin -y
```

Here's the setup process for the generator itself, from the project root:

```
$ cd lib/Font-Awesome-SVG-PNG && npm install && cd ../.. && grunt iconify
```

After that initial setup, you can just run `grunt iconify` to generate new icons.

### Local Environment (Optional)

You may want to check out [pedestal-bedrock](https://github.com/spiritedmedia/pedestal-bedrock), which is our WP installation. Follow the manual installation instructions there to get set up. Note that WP will be installed in a subdirectory e.g. `http://billypenn.dev/wp/wp-admin/`

## Contributing Guidelines

Before you begin work on Pedestal, please refer to [CONTRIBUTING.md](CONTRIBUTING.md).

Sorry to have to require this, but it's important to keep things moving smoothly. If anything seems like a particularly annoyingly tall order, feel free to contact @montchr with constructive criticisms.

## Deployments

For information on deployments, please refer to [DEPLOYMENTS.md](DEPLOYMENTS.md). This only applies to developers who have been handed the keys.
