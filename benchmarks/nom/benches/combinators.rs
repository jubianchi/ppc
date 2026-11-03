use criterion::{BenchmarkId, Criterion, criterion_group, criterion_main};
use nom::character::complete::char;
use nom::combinator::flat_map;
use nom::error::Error;
use nom::multi::many0;
use nom::{IResult, Parser};
use std::hint::black_box;

fn many(input: &str) -> IResult<&str, Vec<char>> {
    many0(char::<&str, Error<&str>>('a')).parse(input)
}

fn bench_many(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("many");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/many/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/many/100kB.txt")),
    ] {
        let (rest, chars) = many(input).expect("many() should succeed");
        assert!(rest.is_empty() && chars.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| many(black_box(input)))
        });
    }

    group.finish();
}

fn bind(input: &str) -> IResult<&str, Vec<char>> {
    many0(flat_map(char::<&str, Error<&str>>('a'), char)).parse(input)
}

fn bench_bind(criterion: &mut Criterion) {
    let mut group = criterion.benchmark_group("bind");

    for (name, input) in [
        ("1 kB", include_str!("../../fixtures/bind/1kB.txt")),
        ("100 kB", include_str!("../../fixtures/bind/100kB.txt")),
    ] {
        let (rest, chars) = bind(input).expect("bind() should succeed");
        assert!(rest.is_empty() && 2 * chars.len() == input.len(), "nom does not consume the whole input");

        group.bench_with_input(BenchmarkId::new("nom", name), input, |bencher, input| {
            bencher.iter(|| bind(black_box(input)))
        });
    }

    group.finish();
}

criterion_group!(benches, bench_many, bench_bind);
criterion_main!(benches);
