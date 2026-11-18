use criterion::{BenchmarkId, Criterion, criterion_group, criterion_main};
use nom::bytes::complete::{tag, take_until, take_while};
use nom::error::Error;
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

criterion_group!(benches, bench_take_while, bench_take_until);
criterion_main!(benches);
