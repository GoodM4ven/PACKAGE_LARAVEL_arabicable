#!/usr/bin/env bash

set -euo pipefail

export ARABICABLE_FEATURE_QURAN=true

composer run prepareback
composer run preparefront
composer run publishfront

php vendor/bin/testbench serve --ansi
