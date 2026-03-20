#!/usr/bin/env bash

set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

export ARABICABLE_FEATURE_QURAN=true

cd "${root_dir}"

composer run prepareback
composer run preparefront
composer run publishfront
composer run lint
composer run test
