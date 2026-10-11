#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2026 Britton Couchman <brittondcouchman@gmail.com>
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Code-sign the app and produce the release archive and App Store signature.
#
# Usage:
#   scripts/sign-release.sh --key KEY --cert CERT --docker CONTAINER [--archive UNSIGNED.tar.gz]
#   scripts/sign-release.sh --key KEY --cert CERT --occ "sudo -u www-data php /var/www/nextcloud/occ"
#
#   KEY        your app's private key (integration_photocraft.key); never commit or share it
#   CERT       the certificate Nextcloud signed for you (integration_photocraft.crt)
#   CONTAINER  a running Nextcloud container to borrow `occ integrity:sign-app` from
#   --archive  sign an already built archive instead of building one from this checkout
#
# Without --archive, run `make js` and `make wasm` first; output goes to build/dist/. With
# --archive, output goes to ./signed/. Either way you get:
#   integration_photocraft-<version>.tar.gz      the release archive (with appinfo/signature.json)
#   integration_photocraft-<version>.tar.gz.sig  its signature, to paste into the App Store release form
set -euo pipefail

APP_ID=integration_photocraft
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
KEY="" CERT="" CONTAINER="" OCC="" SOURCE_ARCHIVE=""

while [ $# -gt 0 ]; do
	case "$1" in
		--key) KEY="$2"; shift 2 ;;
		--cert) CERT="$2"; shift 2 ;;
		--docker) CONTAINER="$2"; shift 2 ;;
		--occ) OCC="$2"; shift 2 ;;
		--archive) SOURCE_ARCHIVE="$2"; shift 2 ;;
		*) echo "unknown option: $1" >&2; exit 2 ;;
	esac
done
if [ -z "$KEY" ] || [ -z "$CERT" ] || { [ -z "$CONTAINER" ] && [ -z "$OCC" ]; }; then
	sed -n '5,19p' "${BASH_SOURCE[0]}" | sed 's/^# \{0,1\}//'
	exit 2
fi
[ -f "$KEY" ] || { echo "error: key $KEY not found" >&2; exit 1; }
[ -f "$CERT" ] || { echo "error: certificate $CERT not found" >&2; exit 1; }

# The certificate must belong to this key and this app.
if [ "$(openssl x509 -noout -pubkey -in "$CERT" | openssl sha256)" != "$(openssl pkey -pubout -in "$KEY" | openssl sha256)" ]; then
	echo "error: $CERT doesn't match $KEY" >&2
	exit 1
fi
if ! openssl x509 -noout -subject -in "$CERT" | grep -q "CN *= *$APP_ID"; then
	echo "error: $CERT isn't issued for $APP_ID" >&2
	exit 1
fi

if [ -n "$SOURCE_ARCHIVE" ]; then
	[ -f "$SOURCE_ARCHIVE" ] || { echo "error: archive $SOURCE_ARCHIVE not found" >&2; exit 1; }
	DIST="$(mktemp -d)"
	OUT="$PWD/signed"
	tar -xzf "$SOURCE_ARCHIVE" -C "$DIST"
	[ -f "$DIST/$APP_ID/appinfo/info.xml" ] || { echo "error: $SOURCE_ARCHIVE doesn't contain $APP_ID/appinfo/info.xml" >&2; exit 1; }
else
	# A fresh copy of exactly what ships.
	make -C "$APP_DIR" dist >/dev/null
	DIST="$APP_DIR/build/dist"
	OUT="$DIST"
fi
STAGE="$DIST/$APP_ID"
rm -f "$STAGE/appinfo/signature.json"
VERSION="$(sed -n 's:.*<version>\(.*\)</version>.*:\1:p' "$STAGE/appinfo/info.xml")"
mkdir -p "$OUT"
ARCHIVE="$OUT/$APP_ID-$VERSION.tar.gz"

if [ -n "$CONTAINER" ]; then
	WORK="/tmp/$APP_ID-sign-$$"
	docker exec "$CONTAINER" mkdir -p "$WORK"
	# The key leaves the container again whatever happens.
	trap 'docker exec "$CONTAINER" rm -rf "$WORK"' EXIT
	docker cp "$STAGE" "$CONTAINER:$WORK/$APP_ID"
	docker cp "$KEY" "$CONTAINER:$WORK/app.key"
	docker cp "$CERT" "$CONTAINER:$WORK/app.crt"
	docker exec "$CONTAINER" chown -R www-data "$WORK"
	docker exec -u www-data "$CONTAINER" php occ integrity:sign-app \
		--privateKey="$WORK/app.key" --certificate="$WORK/app.crt" --path="$WORK/$APP_ID"
	docker cp "$CONTAINER:$WORK/$APP_ID/appinfo/signature.json" "$STAGE/appinfo/signature.json"
else
	$OCC integrity:sign-app --privateKey="$(realpath "$KEY")" --certificate="$(realpath "$CERT")" --path="$STAGE"
fi
[ -s "$STAGE/appinfo/signature.json" ] || { echo "error: signing produced no signature.json" >&2; exit 1; }

rm -f "$ARCHIVE"
tar -czf "$ARCHIVE" -C "$DIST" "$APP_ID"
openssl dgst -sha512 -sign "$KEY" "$ARCHIVE" | openssl base64 > "$ARCHIVE.sig"

echo
echo "Release archive:   $ARCHIVE"
echo "App Store signature (paste into the release form):"
cat "$ARCHIVE.sig"
