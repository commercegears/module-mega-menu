# Mega Menu for Magento 2: FAQ

**Does it work with Hyvä?**
Yes. Hyvä support is in the same package, there is no extra module to buy or install. The module recognises themes
based on a `Hyva/*` theme and replaces Hyvä's menu in the header (tested with Hyvä 1.5). Luma, Blank and their child
themes are supported too.

**Do I have to change my theme?**
No. The theme's top menu is replaced through layout only, and only for the store views where the menu is enabled.
Switch it off and the theme's own menu is back. A heavily customised header may need one layout line to move the
block `commercegears.megamenu`; the user guide explains the layout handles.

**Do I have to build the menu by hand?**
No. A store view without menu items shows its category tree. To decorate the categories, click **Add Top-Level
Categories** in the Menu Builder (or run `bin/magento commercegears:megamenu:import-categories`): each top-level category
becomes an item you can reorder and give content, labels and icons. A "Category tree" item keeps all top-level
categories, or the subcategories of one category, up to date automatically.

**What can I put into a dropdown?**
Links in 1 to 6 columns (subcategories and your own child items), a CMS block, your own content (with Page Builder
when it is installed, otherwise the editor) and an image with a link. The content can sit right of, left of or below
the links, and the dropdown can be full width or fit its content.

**Will heavy dropdown content slow my pages down?**
Not if you switch on **Load Content When Opened** for that item. The CMS block, content and image are then fetched
on the first hover or tap and cached, instead of being part of every page. The links stay in the page for search
engines and screen readers.

**Does it work with full page cache and Varnish?**
Yes. The menu is the same for every visitor of a store view and is rendered once for all devices: desktop and mobile
layouts switch in CSS at the width you choose, so Varnish needs no device split. Saving items or settings refreshes
the cached menu and the pages that contain it.

**Can I show an item only for a sale period?**
Yes. Every item has **Show From** and **Show Until** dates, and every label has its own dates, so a "Sale" label can
end on its own. A cron job checks every 10 minutes whether something started or ended and refreshes the menu.

**How does the mobile menu work?**
Below 768 or 1024 px (your choice) the menu becomes a drawer. Choose **Drill-down**, where each level slides in with
a Back button, or **Accordion**, where levels open below their parent. Items can also be shown on desktop only or on
mobile only.

**Is the menu accessible?**
It is built for keyboard and screen reader use: a navigation landmark, buttons that report whether their dropdown is
open, arrow keys, Home and End inside the menu, Escape to close a dropdown and return focus, and visible focus styles.
Nothing is reachable by hover only.

**My catalogue has many top-level categories. Will they fit?**
Use the **Hamburger** desktop layout: one button opens a vertical list of all top-level items with each dropdown
beside it.

**Can each store view have a different menu?**
Yes. Every item can be limited to store views, and all settings (layout, colours, levels) can be set per store view.

**Can I show different menus to different customer groups?**
No. The menu is the same for every visitor of a store view, which keeps it fully cacheable.

**Which permissions are there?**
Menu Builder (with separate rights to add and edit items and to delete items) and Settings, under CommerceGears >
Mega Menu in the admin user roles.
