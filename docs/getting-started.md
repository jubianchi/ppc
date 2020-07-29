# Getting started 

> [!WARNING]
> This is the documentation for the `master` branch.
> Things may change or not be complete.
>
> Head over to the [latest stable version](/1.0.0/) for an up-to-date documentation.

## Introduction

> In computer programming, a parser combinator is a higher-order function that accepts several parsers as input and 
> returns a new parser as its output. In this context, a parser is a function accepting strings as input and returning 
> some structure as output [...].
>
> — [Wikipedia](https://en.wikipedia.org/wiki/Parser_combinator)

PPC stands for **P**HP **P**arser **C**ombinator. What an obvious name for such a library!

As its name tells us, PPC is just another parser combinator library with a clear goal: make writing efficient parsers a 
breeze. Writing parser with PPC does not require you to know how parser combinators works internally nor it requires you 
to learn a complex object-oriented API.

PPC is a set of functions which you will love to compose to build complex parsers!   

### Main goals

<!-- panels:start -->

<!-- div:one-third-panel -->

#### Simplicity

One of the primary goal of the library is to make writing parsers an effortless task: it has to be **simple to use**! As
all libraries, it enforces simplicity through a limited, or might I say, an opinionated API. This API is focused on the 
most tasks so at some point you will be something something this is why PPC has to be **simple to extend**.

<!-- div:one-third-panel -->

#### Efficiency

Parser combinators have many drawbacks. You probably heard they are not be memory-efficient or time-efficient. PPC does 
its best to solve these issues and tries to lower its footprint as much as possible. Ensuring good performance is a 
prerequiste to every new feature or bugfix.

<!-- div:one-third-panel -->

#### Reliability

PPC aims at being a very stable library giving you an high level of confidence when writing your parsers. This is 
mainly enforced by a strict testing strategy, coding standards and a well-written code which takes advantages of 
everything PHP can do fos us. 

<!-- panels:end -->

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
