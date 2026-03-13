# Maintainer / release notes

Notes for maintainers and AI agents working on this repo.

## Releasing (e.g. 1.0.1)

1. **Set the version**  
   Put the new version on the first line of `VERSION`:
   ```text
   1.0.1
   ```

2. **Commit and tag**  
   ```bash
   git add VERSION
   git commit -m "Release 1.0.1"
   git tag -s v1.0.1 -m "Release 1.0.1"
   ```

3. **Push**  
   ```bash
   git push origin main
   git push origin v1.0.1
   ```

4. **Create the GitHub release** (optional)  
   On GitHub: **Releases → New release**, choose tag `v1.0.1`, add notes, then **Publish**.  
   To offer a zip: **Generate zip from tag** and attach it, or build it yourself (e.g. `git archive -o qr-1.0.1.zip v1.0.1`) and upload it.

5. **Docker images** (automatic if CI is configured)  
   Pushing a version tag (e.g. `git push origin v1.0.1`) triggers the [Docker release workflow](.github/workflows/docker-release.yml): the image is built and pushed to **Docker Hub** (`darknetz/php-qrcodegenerator`) and **GHCR** (`ghcr.io/darknetz/php-qrcodegenerator`).  
   Add repo secrets: `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN` (Docker Hub access token). GHCR uses `GITHUB_TOKEN` automatically.  
   To build and push manually: set `DOCKERHUB_IMAGE=darknetz/php-qrcodegenerator` and `GHCR_IMAGE=ghcr.io/darknetz/php-qrcodegenerator`, then run `./scripts/docker-release.sh [VERSION]` (script is in `scripts/`, make executable with `chmod +x` if needed).
