use criterion::{BenchmarkId, Criterion, criterion_group, criterion_main};
use nom::bytes::complete::{tag, take_till, take_until, take_while};
use nom::character::complete::{none_of, one_of, satisfy};
use nom::error::Error;
use nom::multi::many0;
use nom::sequence::terminated;
use nom::{IResult, Parser};
use std::hint::black_box;

fn take_while_a(input: &str) -> IResult<&str, &str> {
    take_while::<_, &str, Error<&str>>(|char| char == 'a')(input)
}

fn bench_take_while(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("takeWhile");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/takeWhile/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/takeWhile/100kB.txt")),
    ] {
        let (rest, taken) = take_while_a(input).expect("take_while() should succeed");
        assert!(rest.is_empty() && taken.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| take_while_a(black_box(input)))
        });
    }

    group.finish();
}

fn take_until_end_of_comment(input: &str) -> IResult<&str, &str> {
    terminated(take_until::<_, &str, Error<&str>>("*/"), tag("*/")).parse(input)
}

fn bench_take_until(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("takeUntil");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/takeUntil/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/takeUntil/100kB.txt")),
    ] {
        let (rest, taken) = take_until_end_of_comment(input).expect("take_until() should succeed");
        assert!(rest.is_empty() && taken.len() == input.len() - 2, "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| take_until_end_of_comment(black_box(input)))
        });
    }

    group.finish();
}

fn take_till_end_of_comment(input: &str) -> IResult<&str, &str> {
    terminated(take_till::<_, &str, Error<&str>>(|char| char == '*'), tag("*/")).parse(input)
}

fn bench_take_until_predicate(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("takeUntilPredicate");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/takeUntil/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/takeUntil/100kB.txt")),
    ] {
        let (rest, taken) = take_till_end_of_comment(input).expect("take_till() should succeed");
        assert!(rest.is_empty() && taken.len() == input.len() - 2, "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| take_till_end_of_comment(black_box(input)))
        });
    }

    group.finish();
}

fn satisfy_a(input: &str) -> IResult<&str, Vec<char>> {
    many0(satisfy::<_, &str, Error<&str>>(|char| char == 'a')).parse(input)
}

fn bench_satisfy(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("satisfy");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/satisfy/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/satisfy/100kB.txt")),
    ] {
        let (rest, chars) = satisfy_a(input).expect("satisfy() should succeed");
        assert!(rest.is_empty() && chars.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| satisfy_a(black_box(input)))
        });
    }

    group.finish();
}

fn bench_many0(
    criterion: &mut Criterion,
    group: &str,
    inputs: [(&str, &str); 2],
    parser: for<'a> fn(&'a str) -> IResult<&'a str, Vec<char>>,
) {
    let mut group = criterion.benchmark_group(group);

    for (name, input) in inputs {
        let (rest, chars) = parser(input).expect("many0() should succeed");
        assert!(rest.is_empty() && chars.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| parser(black_box(input)))
        });
    }

    group.finish();
}

fn digits(input: &str) -> IResult<&str, Vec<char>> {
    many0(satisfy::<_, &str, Error<&str>>(|char| char.is_ascii_digit())).parse(input)
}

fn letters(input: &str) -> IResult<&str, Vec<char>> {
    many0(satisfy::<_, &str, Error<&str>>(char::is_alphabetic)).parse(input)
}

fn spaces(input: &str) -> IResult<&str, Vec<char>> {
    many0(satisfy::<_, &str, Error<&str>>(char::is_whitespace)).parse(input)
}

fn signs(input: &str) -> IResult<&str, Vec<char>> {
    many0(one_of::<_, _, Error<&str>>("+-")).parse(input)
}

fn unescaped(input: &str) -> IResult<&str, Vec<char>> {
    many0(none_of::<_, _, Error<&str>>("\"\\")).parse(input)
}

fn bench_char_classes(criterion: &mut Criterion) {
    bench_many0(
        criterion,
        "digit",
        [
            ("1 kB", include_str!("../../fixtures/digit/1kB.txt")),
            ("100 kB", include_str!("../../fixtures/digit/100kB.txt")),
        ],
        digits,
    );
    bench_many0(
        criterion,
        "letter",
        [
            ("1 kB", include_str!("../../fixtures/letter/1kB.txt")),
            ("100 kB", include_str!("../../fixtures/letter/100kB.txt")),
        ],
        letters,
    );
    bench_many0(
        criterion,
        "space",
        [
            ("1 kB", include_str!("../../fixtures/space/1kB.txt")),
            ("100 kB", include_str!("../../fixtures/space/100kB.txt")),
        ],
        spaces,
    );
    bench_many0(
        criterion,
        "oneOf",
        [
            ("1 kB", include_str!("../../fixtures/oneOf/1kB.txt")),
            ("100 kB", include_str!("../../fixtures/oneOf/100kB.txt")),
        ],
        signs,
    );
    bench_many0(
        criterion,
        "noneOf",
        [
            ("1 kB", include_str!("../../fixtures/noneOf/1kB.txt")),
            ("100 kB", include_str!("../../fixtures/noneOf/100kB.txt")),
        ],
        unescaped,
    );
}

criterion_group!(
    benches,
    bench_satisfy,
    bench_char_classes,
    bench_take_while,
    bench_take_until,
    bench_take_until_predicate
);
criterion_main!(benches);
