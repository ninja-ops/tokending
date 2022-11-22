#!/bin/bash

if [ $# -eq 0 ]
  then
    echo "usage ./watchdog.sh file-to-watch script-to-execute"
    exit
fi

if [ -z "$2" ]
  then
    echo "no script to execute found"
    exit
fi

FILE=$1
CMD=$2

while :
do
  if [[ -f "$FILE" ]]; then
    rm $FILE
    logger -t watchdog "$FILE found, execute -> $CMD"
    $CMD
  fi
  sleep 1
done
