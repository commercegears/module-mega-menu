# CommerceGears Mega Menu for Magento 2

`commercegears/module-mega-menu` (Magento module `CommerceGears_MegaMenu`)

A mega menu for Luma and Hyvä that replaces the theme's top menu. Combine the automatic category tree with your own
items, arrange everything by drag and drop and give each dropdown links in columns, a CMS block or Page Builder
content and an image. The page is rendered once for every device: desktop and mobile layouts switch in CSS, so full
page cache and Varnish need no device split.

## Features

- **Menu Builder** (CommerceGears > Mega Menu > Menu Builder): a tree of items with drag and drop ordering and
  nesting (three levels: menu bar, column heading, link), per store view filter, unsaved changes bar.
- **Item types:** category tree (all top-level categories, or the subcategories of a chosen category, kept up to date
  automatically), category, CMS page, custom link (internal path or full URL, optional new tab) and heading without a link.
- **Dropdowns:** subcategory links in 1 to 6 columns, full width or fit content, CMS block, own content (Page Builder
  when installed, otherwise the editor), image with link and alternative text, content right, left or below the links.
- **Lazy panels:** per item, the dropdown content (CMS block, own content, image) can load on first hover or tap
  instead of with every page. Links always stay in the HTML for search engines and screen readers.
- **Labels and icons:** a short badge (for example New or Sale) with its own colours, a built-in line icon or an
  uploaded icon image (JPG, PNG, GIF, WebP).
- **Scheduling:** show an item or its label only between two dates (store time zone). A cron job refreshes the cached
  menu when a date starts or ends.
- **Desktop layouts:** horizontal bar, or hamburger mode (one button opens a vertical list with the dropdowns beside
  it) for large catalogues. Dropdowns open on hover or on click.
- **Mobile:** drill-down (each level slides in with a Back button) or accordion, in a drawer. Switch width 768 or
  1024 px. Items can be shown on desktop only or mobile only (hidden in CSS).
- **Sticky menu** option, **colours** in the settings (menu bar, dropdown, headings, hover, default label colours).
- **Accessibility:** a `nav` landmark, buttons with `aria-expanded`/`aria-controls`, arrow keys, Home/End, Escape
  returns focus, visible focus styles, no hover-only content.
- **Store view scope:** every item can be limited to store views; all settings per store view.
- **Cache:** the menu block is cached per store view and tagged; saving items or settings cleans the block and the
  full page cache for the menu.

## Configuration

Stores > Configuration > CommerceGears > Mega Menu (also CommerceGears > Mega Menu > Settings):

| Group | Settings |
| --- | --- |
| General | Enabled (default No), Subcategory Levels (1 to 3), Show "View All" Link |
| Layout and Behaviour | Desktop Layout, Hamburger Button Text, Open Dropdowns On, Mobile Menu Style, Mobile Menu Below, Sticky Menu |
| Colours | Menu Bar Background, Menu Bar Text, Menu Bar Hover and Open, Dropdown Background, Dropdown Text, Dropdown Headings, Dropdown Link Hover, Default Label Background, Default Label Text |

A store view without menu items shows its category tree automatically, so enabling the module is enough for a first look.

## How the theme menu is replaced

When the menu is enabled for the store view, an observer adds one layout handle:

- `commercegears_megamenu_luma` (Luma, Blank and RequireJS themes): removes `catalog.topnav` and adds the mega menu to
  `store.menu`, where Magento's menu was.
- `commercegears_megamenu_hyva` (themes that inherit from a `Hyva/*` theme): removes Hyvä's `topmenu_mobile` and
  `topmenu_desktop` and makes the mega menu the only child of `topmenu_generic`, so it renders exactly where Hyvä's
  menu was. The wrapper's block cache and ESI time-to-live are switched off; the mega menu block caches itself.

No theme file is changed. When the module is disabled for a store view, the theme's own menu is back. Custom themes
can reference the same handles to move the block (`commercegears.megamenu`).

## CLI

```bash
bin/magento commercegears:megamenu:list [--store=<code|id>]
bin/magento commercegears:megamenu:import-categories [--store=<code|id>] [--only-this-store]
```

`list` prints the menu as the storefront renders it today (schedules applied). `import-categories` adds one item per
top-level category so you can reorder and decorate them in the Menu Builder.

## Permissions (ACL)

CommerceGears > Mega Menu: Menu Builder (Add and Edit Items, Delete Items) and Settings.

## Compatibility

- Magento Open Source and Adobe Commerce 2.4.7 to 2.4.9, PHP 8.2 to 8.5
- Luma, Blank and their child themes; Hyvä 1.3+ (tested with 1.5)
- `commercegears/module-core` ^2.0
- Optional: Magento Page Builder for dropdown content

## Limitations

- One menu per store view (no separate menus per customer group, by design for full page cache).
- Three levels per dropdown (column heading, link, one more indented level for categories).
- No product sliders inside dropdowns; place a CMS block or widget there instead.
- Custom themes with a heavily changed header may need the block moved with a layout update.

## Documentation

- [User guide](docs/user-guide.md)
- [FAQ](docs/faq.md)
- [Changelog](CHANGELOG.md)
