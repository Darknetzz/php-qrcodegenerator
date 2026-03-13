# Changelog

## [1.1.0]

- **Admin panel reorganized** — The admin page is now split into three sections with pill navigation: **Updates** (repo, IP allowlist, allow app from any IP), **Authentication** (login options, upgrade secret, admin secret), and **Modules** (custom modules and default preset visibility/order).
- **Custom modules in admin** — Admins can define custom QR modules (e.g. Phone with `tel:%s`) in Admin → Modules. These appear in the main app for all users. Add via the **+ Add** button (opens a modal) or edit/delete from the list. Server-defined modules are read-only in the app; users can still add their own in the main app (stored in the browser).
- **Reorder custom and default modules** — In Admin → Modules you can change the order of custom modules and of default preset tabs (URL, Wi‑Fi, Text, etc.) using ↑ and ↓. Order is saved in config and applied app-wide.
- **Default preset order config** — A new config key `preset_order` stores the tab order for built-in presets. The main app uses this order for both the tab bar and the content panels.

[1.1.0]: https://github.com/Darknetzz/php-qrcodegenerator/releases/tag/v1.1.0

## [1.0.1]

- **QR background color fixed** — Changing the background color in the UI now correctly affects the generated QR code (PNG and SVG). Previously, only the foreground (data modules) color was customizable; light modules were always white, so custom backgrounds were not displayed. The generator now sets both foreground and background (dark and light modules) colors so your chosen background appears throughout the code as expected.
- **Update checker added** — The generator now includes an automatic update checker. When you open the page, it checks for the latest release on GitHub and displays a notification if a new version is available, helping you keep your QR code generator up to date more easily.

[1.0.1]: https://github.com/Darknetzz/php-qrcodegenerator/releases/tag/v1.0.1
