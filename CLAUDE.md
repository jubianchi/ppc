# CLAUDE.md

Read these first, they are not repeated here:

- [README.md](README.md#concepts): the four kinds of functions;
- [CONTRIBUTING.md](CONTRIBUTING.md): the structure, the parser contract, the rules of each kind, the PHPStan
  limitations and the workflow.

## Commands

The commands to run before committing are listed in [CONTRIBUTING.md](CONTRIBUTING.md#workflow). While working:

```bash
vendor/bin/phpunit --filter ChainTest                       # a single test class or method
vendor/bin/phpunit --testsuite Unit                         # only the unit tests (also Examples, Documentation)
vendor/bin/phpstan analyse --no-progress --error-format=raw # one error per line, with its identifier
php tools/check-coverage.php tmp/clover.xml                 # the uncovered lines of the last composer cov run
vendor/bin/phpbench run benchmarks/JSON.php --report=aggregate --filter=bench_json_ppc
```

`composer cov` needs the pcov extension; it enables it itself, so pcov can stay disabled (`pcov.enabled=0`) to keep the
tests and benchmarks fast.

## Code coverage

The coverage of `src/` is 100% of the lines, and `composer cov` fails the build otherwise.

- It is measured with the `Unit` suite only: the example parsers and the documentation examples run many functions at
  once, and would hide the ones without a test of their own.
- An uncovered line is either a missing test or dead code: write the test, or remove the code.
- Never exclude code from the coverage (`@codeCoverageIgnore`, `<exclude>` in `phpunit.xml`).
- Error cases count: every exception thrown has a test expecting its message.

## Types

The types are as strict as possible.

- Use native PHP types first: parameter, return and property types, `readonly`, `final`, union types, `never`.
- Use PHPDoc only for what PHP cannot express: templates, closure signatures, `list<>` and array shapes, `non-empty-*`,
  integer ranges.
- Never widen a type to silence PHPStan: no `mixed` when a narrower type is known, no `@phpstan-ignore`, no baseline,
  no inline `@var` overriding an inferred type.

## Benchmarks

Each parser, combinator and modifier has a benchmark comparing PPC with at least
[Parsica](https://github.com/parsica-php/parsica) and [nom](https://github.com/rust-bakery/nom), on the same inputs.
When a library has no equivalent, the benchmark says so.

The per-function harness, with the nom crate, is not in place yet: until then, a new function comes with a PHPBench
benchmark in `benchmarks/`, compared with Parsica when it has an equivalent.

A change to an existing function is compared with `master` (`composer bm:ref` on `master`, then `composer bmc`), and a
slowdown is reported in the pull request.
