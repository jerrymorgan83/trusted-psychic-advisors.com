# Trusted Psychic Advisors, WordPress theme

Implementation of the approved homepage design. Warm paper, grotesk headlines, serif body, single accent #2F6F62. One responsive layout: the desktop grid at 1440 collapses to the 390 mobile treatment.

## Install

1. Zip the folder `trusted-psychic-advisors` or upload it to `wp-content/themes/`.
2. Appearance, Themes, activate.
3. Settings, Reading, Your homepage displays, A static page, and pick any page. `front-page.php` takes over.
4. Settings, Permalinks, Save, so `/networks/` and `/reading-type/` resolve.

## Content model

Networks live in the **Networks** post type. The Published terms box holds one field per value, and each field name states its provenance:

| Field | Holds |
| --- | --- |
| Published rate per minute | The rate the network advertises |
| Published intro offer | The offer the network advertises |
| Stated refund policy | The wording in the network terms |
| Public advisor count | The count the network publishes |
| Network's own published review score | The score shown on the network site |
| Our score | Built from published terms and public review data |
| Source URL, Date read | Attribution shown next to the values |

Empty fields render an obvious placeholder such as "Network name" and "published rate", so a half-filled entry never reads as a fact.

Reading types are the `tpa_reading_type` taxonomy. The term description becomes the card subline.

## Copy that carries compliance meaning

Appearance, Customize, Directory copy holds the banner, hero, disclosure and footer legal text, with the approved wording as the default. Two checkboxes there toggle the top banner and the logo strip.

Editorial rules baked into the defaults: no firsthand testing claim, no invented statistic, no fabricated brand or rating, no em dash. Keep them when you edit.

## Menus

Primary, plus three footer menus: Directory, Reading types, About. Register them under Appearance, Menus.

## Files

- `style.css` theme header and the full stylesheet, tokens in `:root`
- `functions.php` setup, assets, field and source helpers
- `inc/network-cpt.php` Networks post type, taxonomy, meta box
- `inc/customizer.php` copy settings
- `front-page.php` homepage, first screen and full landing page
- `template-parts/entry-card.php`, `template-parts/comparison-row.php`
- `single-tpa_network.php`, `archive-tpa_network.php`, `index.php`, `page.php`, `single.php`
- `assets/js/nav.js` mobile menu toggle
