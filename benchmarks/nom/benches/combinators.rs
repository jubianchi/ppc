use criterion::{BenchmarkId, Criterion, criterion_group, criterion_main};
use nom::character::complete::char;
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

criterion_group!(benches, bench_many);
criterion_main!(benches);
