#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_cache_file="${root_dir}/vendor/pest-plugins.json"
backup_file=""

cleanup() {
    if [[ -n "${backup_file}" && -f "${backup_file}" ]]; then
        mv "${backup_file}" "${plugin_cache_file}"
    fi
}

trap cleanup EXIT INT TERM

if [[ -f "${plugin_cache_file}" ]]; then
    backup_file="${plugin_cache_file}.backup.$$"
    cp "${plugin_cache_file}" "${backup_file}"
    sed -i.bak '/"Pest\\\\Browser\\\\Plugin"/d' "${plugin_cache_file}"
    rm -f "${plugin_cache_file}.bak"
fi

export PEST_ENABLE_BROWSER_PLUGIN=0

cd "${root_dir}"
vendor/bin/pest --exclude-group=browser "$@"
