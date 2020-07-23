#!/usr/bin/env bash

DIR=$(cd $(dirname $0); pwd)
VENDOR=$DIR/../../vendor
DOCS=$DIR/../../docs
BINDIR=$VENDOR/bin
UPTODOCS=$BINDIR/uptodocs
FAILURE=0

for FILE in $DOCS/*.md
do
    echo "> $FILE"
    $UPTODOCS run --before $DIR/before.php $FILE
    FAILURE=$(( FAILURE + $? ))
    echo
done

for FILE in $DOCS/**/*.md
do
    echo "> $FILE"
    $UPTODOCS run --before $DIR/before.php $FILE
    FAILURE=$(( FAILURE + $? ))
    echo
done

exit $FAILURE
