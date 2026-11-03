use criterion::{BenchmarkId, Criterion, criterion_group, criterion_main};
use nom::character::complete::char;
use nom::combinator::{not, peek};
use nom::error::Error;
use nom::multi::many0;
use nom::sequence::{preceded, terminated};
use nom::{IResult, Parser};
use std::hint::black_box;

const PEEK_INPUTS:[(&str, &str); 2] = [
    ("1 kB", include_str!("../../fixtures/peek/1kB.txt")),
    ("100 kB", include_str!("../../fixtures/peek/100kB.txt")),
];

const NEGATE_INPUTS: [(&str, &str); 2] = [
    ("1 kB", include_str!("../../fixtures/negate/1kB.txt")),
    ("100 kB", include_str!("../../fixtures/negate/100kB.txt")),
];

fn peeked(input: &str) -> IResult<&str, Vec<char>> {
    many0(preceded(peek(char::<&str, Error<&str>>('a')), char('a'))).parse(input)
}

fn negated(input: &str) -> IResult<&str, Vec<char>> {
    many0(terminated(char::<&str, Error<&str>>('a'), not(char('b')))).parse(input)
}

fn bench(
    criterion: &mut Criterion,
    group: &str,
    inputs: [(&str, &str); 2],
    parser: fn(&str) -> IResult<&str, Vec<char>>,
) {
    let mut group = criterion.benchmark_group(group);

    for (name, input) in inputs {
        let (rest, chars) = parser(input).expect("the parser should succeed");
        assert!(rest.is_empty() && chars.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| parser(black_box(input)))
        });
    }

    group.finish();
}

fn bench_peek(criterion: &mut Criterion) {
    bench(criterion, "peek", PEEK_INPUTS, peeked);
}

fn bench_negate(criterion: &mut Criterion) {
    bench(criterion, "negate", NEGATE_INPUTS, negated);
}

criterion_group!(benches, bench_peek, bench_negate);
criterion_main!(benches);
