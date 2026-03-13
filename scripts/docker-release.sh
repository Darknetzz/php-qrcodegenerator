#!/usr/bin/env bash
# Build and push Docker image to Docker Hub and GHCR.
# Usage: ./scripts/docker-release.sh [VERSION]
#   VERSION defaults to the first line of VERSION.
# Requires: DOCKERHUB_IMAGE and GHCR_IMAGE set (e.g. myuser/php-qrcodegenerator, ghcr.io/myorg/php-qrcodegenerator).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
  VERSION=$(sed -n '1p' VERSION | tr -d '\r\n' | sed 's/^v//')
fi
if [[ -z "$VERSION" ]]; then
  echo "Usage: $0 [VERSION] (or set first line of VERSION)" >&2
  exit 1
fi

: "${DOCKERHUB_IMAGE:?Set DOCKERHUB_IMAGE (e.g. myuser/php-qrcodegenerator)"}"
: "${GHCR_IMAGE:?Set GHCR_IMAGE (e.g. ghcr.io/myorg/php-qrcodegenerator)"}"

TAG="v${VERSION#v}"
echo "Building and pushing $TAG to Docker Hub and GHCR..."

docker build -t "$DOCKERHUB_IMAGE:$TAG" -t "$DOCKERHUB_IMAGE:$VERSION" \
  -t "$GHCR_IMAGE:$TAG" -t "$GHCR_IMAGE:$VERSION" .

docker push "$DOCKERHUB_IMAGE:$TAG"
docker push "$DOCKERHUB_IMAGE:$VERSION"
docker push "$GHCR_IMAGE:$TAG"
docker push "$GHCR_IMAGE:$VERSION"

echo "Pushed $DOCKERHUB_IMAGE:$TAG and $GHCR_IMAGE:$TAG"
