#!/bin/bash

export DISPLAY=:0.0;
python partPreview3.py $1;
unset DISPLAY;
cat $1_transparent.png | img2sixel -w 600
rm $1_transparent.png;

