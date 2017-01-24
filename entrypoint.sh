#!/bin/bash

Xvfb :2 -screen 0 1600x1200x24 &
export DISPLAY=:2

exec "$@"