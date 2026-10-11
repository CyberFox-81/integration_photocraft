# SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
# SPDX-License-Identifier: AGPL-3.0-or-later

app_name := integration_photocraft
version := $(shell sed -n 's:.*<version>\(.*\)</version>.*:\1:p' appinfo/info.xml)
dist := build/dist

.PHONY: all js wasm dist clean

all: js

# The Files action and the editor page (js/)
js:
	npm ci
	npm run build

# PhotoCraft itself (web/): needs Rust, see scripts/build-photocraft-web.sh
wasm:
	scripts/build-photocraft-web.sh

# An installable archive: build/dist/integration_photocraft-<version>.tar.gz
dist:
	@test -f web/boot.js || { echo "web/boot.js missing: run 'make wasm' first"; exit 1; }
	@test -f js/$(app_name)-editor.mjs || { echo "js/ missing: run 'make js' first"; exit 1; }
	rm -rf $(dist)/$(app_name)
	mkdir -p $(dist)/$(app_name)
	cp -r appinfo css img js lib templates web LICENSE README.md CHANGELOG.md $(dist)/$(app_name)/
	find $(dist)/$(app_name)/js -name '*.map' -delete
	tar -czf $(dist)/$(app_name)-$(version).tar.gz -C $(dist) $(app_name)
	@echo "wrote $(dist)/$(app_name)-$(version).tar.gz"

clean:
	rm -rf build js
