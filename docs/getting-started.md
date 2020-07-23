# Getting started 

> [!WARNING]
> This is the documentation for the `master` branch.
> Things may change or not be complete.
>
> Head over to the [latest stable version](/1.0.0/) for an up-to-date documentation.

## Introduction

!> **TODO** write this part

## Installation

PPC requires you to have at least PHP `7.4.0` and the [Multibyte String](https://www.php.net/manual/en/book.mbstring.php) 
(`mbstring`) extension enabled. You may want to check if your setup is correct using the following script: 

```bash
#!/usr/bin/env bash

echo "PHP version: $(php -v | head -n1 | grep -qE '7.([4-9]|1[0-9]).(0|[1-9][0-9]*)' && echo '✅' || echo '❌')"
echo "Multibyte String extension: $(php -m | grep -qE 'mbstring' && echo '✅' || echo '❌')"
```

Once everything is correct, choose the installation method that best feets your needs:

<!-- tabs:start -->

### ** Composer (CLI) **

```bash
composer require "jubianchi/ppc" "dev-master"
```

### ** Composer (JSON) **

```json
{
    "require": {
        "jubianchi/ppc": "dev-master"
    }
}
```

### ** Git **

```bash
git clone "https://github.com/jubianchi/ppc.git"
git checkout "master"
```

<!-- tabs:end -->
