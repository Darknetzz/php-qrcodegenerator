# php-qrcodegenerator artwork

Dark flat-vector branding for README / GitLab avatar / social share.

**Palette:** accent `#06b6d4` · secondary `#67e8f9` · canvas `#06141a`

## Layouts

### Adb-keeper style (root)

| File | Size |
|------|------|
| `logo-{solid,gradient,transparent}.png` | 512×512 |
| `banner-{solid,gradient,transparent}.png` | 1280×640 |

### Code style

| Path | Contents |
|------|----------|
| `repo/` | Transparent 1×1 / 16×9 (+ label) |
| `bg-solid/repo/` | Solid dark canvas variants |
| `bg-gradient/repo/` | Gradient + glow variants |
| `og/php-qrcodegenerator.png` | Open Graph 1280×640 |
| `svg-src/` | SVG sources |
| `ai/` | Optional AI atmospheric banner |

## Regenerate

```bash
python batch_generate.py --only php-qrcodegenerator
```
