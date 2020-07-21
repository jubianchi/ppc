#!/usr/bin/env bash

DIR=$(cd $(dirname $0); pwd)
VENDOR=$DIR/../../vendor
DOCS=$DIR/../../docs
BINDIR=$VENDOR/bin
UPTODOCS=$BINDIR/uptodocs

for FILE in $DOCS/*.md
do
    echo "> $FILE"
    $UPTODOCS run --before $DIR/before.php $FILE
    echo
done

for FILE in $DOCS/**/*.md
do
    echo "> $FILE"
    $UPTODOCS run --before $DIR/before.php $FILE
    echo
done


# vendor/bin/uptodocs run --before tests/docs/before.php docs/tutorials/consuming-until-the-end-of-the-stream.md
