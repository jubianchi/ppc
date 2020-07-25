#!/usr/bin/env bash

DIR=$(cd $(dirname $0); pwd)
VENDOR=$(cd $DIR/../../vendor; pwd)
DOCS=$(cd $DIR/../../docs; pwd)
BINDIR=$VENDOR/bin
UPTODOCS=$BINDIR/uptodocs
FAILURE=0
REPORT=

function run() {
    echo -ne "> \033[34mRUN\033[0m $1"
    OUTPUT=$($UPTODOCS run --before $DIR/before.php $1)
    STATUS=$?

    if [ "$STATUS" -eq 1 ]
    then
        echo -e "\r> \033[31mKO \033[0m $1"
        FAILURE=$(( FAILURE + 1 ))
        REPORT="$REPORT\n\n\033[97;41m$FILE\033[0m\n$(echo $OUTPUT | sed 's/The code/\\n> \\033[31mThe code/g' | sed 's/failed./failed.\\033[0m\\n>> /g')"
    else
        echo -e "\r> \033[32mKO \033[0m $1"
    fi
}

for FILE in $DOCS/*.md
do
    run $FILE
done

for FILE in $DOCS/**/*.md
do
    run $FILE
done

if [ -n "$REPORT" ]
then
    echo -e "$REPORT\n"
fi

exit $FAILURE
