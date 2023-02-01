#!/bin/bash

grep -v '^#' "lang/flags.txt" | \
while read code src; do
  if [ ! -z "$src" ]; then
    wget "$src" -O "flags/$code.svg"
  fi
done

svgo flags/*.svg
