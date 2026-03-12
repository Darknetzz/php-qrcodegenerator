# Changelog

## [1.0.1]

- **QR background color fixed** — Changing the background color in the UI now correctly affects the generated QR code (PNG and SVG). Previously, only the foreground (data modules) color was customizable; light modules were always white, so custom backgrounds were not displayed. The generator now sets both foreground and background (dark and light modules) colors so your chosen background appears throughout the code as expected.
- **Update checker added** — The generator now includes an automatic update checker. When you open the page, it checks for the latest release on GitHub and displays a notification if a new version is available, helping you keep your QR code generator up to date more easily.

[1.0.1]: https://github.com/Darknetzz/php-qrcodegenerator/releases/tag/v1.0.1
