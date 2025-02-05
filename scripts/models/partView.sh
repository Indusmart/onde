#!/bin/bash

export DISPLAY=:0.0;
python partPreview4.py $1;
unset DISPLAY;
cat $1_isometric.png | img2sixel -w 600
rm $1_isometric.png;

