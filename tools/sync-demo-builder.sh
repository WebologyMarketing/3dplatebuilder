#!/bin/sh
# Copy the builder's front-end files from plugin/ (the master copy) into the marketing site's live demo.
# Leaves out images: the demo's car badge lives in site/demo-assets/.
set -e
cd "$(dirname "$0")/.."
rm -rf site/plateboss-builder/assets
mkdir -p site/plateboss-builder/assets
cp -R plugin/assets/css plugin/assets/fonts plugin/assets/js site/plateboss-builder/assets/
echo "Demo builder synced from plugin/assets."
