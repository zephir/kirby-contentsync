# Kirby 3 Contentsync plugin

A Kirby 3 plugin to synchronize your content from a source (usually the server) to your local development environment.

> This plugin requires [Kirby 3](https://getkirby.com), [Kirby 3 CLI](https://github.com/getkirby/cli) and PHP 8 or higher to work.

## Table of Contents

- [Kirby 3 Contentsync plugin](#kirby-3-contentsync-plugin)
  - [Table of Contents](#table-of-contents)
  - [1. Installation](#1-installation)
    - [1.1 Composer](#11-composer)
    - [1.2 Download](#12-download)
    - [1.3 Git submodule](#13-git-submodule)
  - [2. Setup](#2-setup)
  - [3. Options](#3-options)
    - [3.1 enabledRoots](#31-enabledroots)
    - [3.2 Example config entry](#32-example-config-entry)
  - [4. Usage](#4-usage)
  - [5. How does it work](#5-how-does-it-work)
  - [6. Caveats](#6-caveats)

## 1. Installation

The recommended way of installing is by using Composer.

### 1.1 Composer

```
composer require zephir/kirby-cookieconsent
```

### 1.2 Download

Download and copy this repository to `/site/plugins/kirby-cookieconsent`.

### 1.3 Git submodule

```
git submodule add https://github.com/zephir/kirby-cookieconsent.git site/plugins/kirby-cookieconsent
```

## 2. Setup

After installation, you need to add the plugin options to your config.

## 3. Options

| Option       | Type   | Default                       | Required | Description                                                                                                                                      |
| ------------ | ------ | ----------------------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| source       | string | null                          | ✅       | Source of the content, normally the staging / prod server. URL of the host (e.g. https://getkirby.com)                                           |
| token        | string | null                          | ✅       | The authentication token, make sure this token is not accessible by the public. Either use an env file/variable or use a private git repository. |
| enabledRoots | array  | [see below](#31-enabledroots) | ❌       | The authentication token, make sure this token is not accessible by the public. Either use an env file/variable or use a private git repository. |

### 3.1 enabledRoots

The plugin supports synchronizing the `accounts' and `content' folders. To support different types of folder structures we use the `kirby()->roots()` function (hence the name of the option). You can enable/disable either the `accounts` or `content` roots.

Default:

```php
[
    'accounts' => true,
    'content' => true
]
```

### 3.2 Example config entry

```php
'zephir.contentsync' => [
    'source' => 'https://getkirby.com',
    'token' => 'abc123',
    'enabledRoots' => [ // Default, not required
        'accounts' => true,
        'content' => true
    ]
]
```

## 4. Usage

After installing, setting up options, and deploying the changes to the server, you can simply run the kirby command:

`kirby content:sync`

## 5. How does it work?

The plugin creates 2 endpoints (routes):

1. `/contentsync/files`: Returns a list of all files in the enabled roots.
2. `/contentsync/file/:fileId`: Returns the contents of the requested file.

> If you don't set-up a token the endpoints won't work.

Each file returned by `files` has an ID (sha1 hash of root name + path), a path (relative to kirby root), kirby root name and a checksum of the file contents.

The plugin compares the retrieved file list with a file list of local files. It automatically deletes local files that aren't in the server list, creates the ones that aren't local but in the server list, and updates the ones with different checksums.

It doesn't sync files that haven't changed.

## 6. Caveats

1. Since we download each file individually, it is possible that a WAF / Firewall will block the requests. You can add an exception for the endpoints.
2. Generating the checksum can put a bit of load on the server, especially for large files. But for "normal" websites it should be fine - even if you have several gigabytes of data / many files.

## License

MIT

## Credits

- [Zephir](https://zephir.ch)
- [Marc Stampfli](https://github.com/themaaarc)
