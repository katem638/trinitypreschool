# WordPress content-editability plan

Status: implemented locally on 2026-08-17, including the v4 follow-up for Get Involved, Meet Our Director, and Meet The Teachers. This file preserves the audited roadmap and acceptance criteria; see `docs/wordpress-editor-handoff.md` for the operating workflow and remaining production decisions.

Theme-hardening follow-up completed locally on 2026-08-30: migrated Pages now use a registered Designed Page template that preserves their content-owned H1/hero, while the default Page template supplies an automatic branded Post Title for new Pages. The follow-up also adds styled starter content, 404/search/archive/single fallbacks, a named Primary Navigation entity, curated block-lock permissions, centralized brand tokens, and release-audit coverage for those contracts.

## Follow-up migration for the remaining P2 pages

The initial migration put these three pages in `post_content`, but its generic curation pass did not complete the page-specific work in this plan. It left broad `contentOnly` locks on the Director profile, every teacher card, and several Get Involved components; repeated generic List View names; a decorative Custom HTML block on Meet The Teachers; and empty Media Library alt text for the Director portrait.

The guarded v4 follow-up preserves the existing words, links, anchors, card order, and front-end classes while it:

1. removes the broad content-only locks so editors can select, add, remove, and reorganize native blocks;
2. gives the page roots, involvement sections/cards, classrooms, teacher cards, biographies, and Director components meaningful List View names;
3. replaces the Teachers Custom HTML divider with a native Separator block;
4. keeps the Director section wrapper lightly removal-locked while leaving its headings, biography, and portrait editable;
5. supplies the Director portrait alt text in both the Image block and Media Library; and
6. updates the Teacher Card inserter pattern and release audit so the same standards remain enforceable.

The migration is an in-place structural transform: it creates Page revisions and does not replace editor-authored copy or links.

## Outcome

Keep the current public design, but make ordinary page content editable from **Pages** in WordPress. The durable ownership model should be:

| Concern | Canonical owner | Who edits it |
| --- | --- | --- |
| Page words, images, links, cards, FAQs, files, and section order | Page `post_content` made from blocks | Editor |
| Event title, date, type, image, excerpt, and body | `tp_event` records | Editor |
| Form fields, recipient, validation, and mail template | Contact Form 7 | Administrator or designated form owner |
| Menu | WordPress Navigation entity | Administrator or designated menu owner |
| Header, footer, page shell, design tokens, CSS, block styles, and starter patterns | Version-controlled theme files | Developer |
| Event content model and dynamic event block | A small site-functionality plugin | Developer |

Templates should supply only the site shell: Header, `<main>`, Post Content, and Footer. Visible page sections should live in the page body. The theme should supply section patterns and block styles that preserve the established design without making the pattern file the live source of page copy.

This is consistent with WordPress's block-theme model: templates control document structure, Post Content renders page content, patterns provide reusable starting layouts, and a saved template in the database takes precedence over the corresponding theme file. See the official [template introduction](https://developer.wordpress.org/themes/templates/introduction-to-templates/), [template hierarchy](https://developer.wordpress.org/themes/templates/template-hierarchy/), [pattern introduction](https://developer.wordpress.org/themes/patterns/introduction-to-patterns/), and [starter page patterns](https://developer.wordpress.org/themes/patterns/starter-patterns/).

## Findings that drive the plan

- There are 17 published Pages. Nine already render their database page body through the generic or a content-bearing template. Eight show all or most visible content from a slug-specific template or a database template override instead.
- The active database-saved Front Page template, post ID 66, renders the entire home page and contains no Post Content block. The substantial Home page body, page ID 23, is therefore hidden/stale.
- The Header has a database-saved template-part override, post ID 79. The Front Page and Header database versions take precedence over `templates/front-page.html` and `parts/header.html`; a developer can edit those files and see no public change.
- Schedule a Tour and Events have slug-specific templates with no Post Content. Their saved page bodies are older hidden versions.
- Drop-off & Pick-up, Lunch Bunch, Registration Forms, and Schedule a Tour are chiefly large Custom HTML regions in their templates. Extended Days Program is a large Custom HTML region in its page body. These are the main block-conversion projects.
- Tuition Plans & Pricing and Get Involved already live in page content as native blocks. Meet the Teachers is also native-block content, including 14 Details blocks. These pages need curation, not wholesale rebuilding.
- Contact and Home embed Contact Form 7 with Shortcode blocks; Schedule a Tour hard-codes its form in its template. CF7 has an official selector block that is a better editor experience.
- `tp_event` registration, Pie Calendar integration, and the server-rendered `trinity-preschool/weekly-events` block all live in the theme's `functions.php`. The event block has no editor registration/preview. The event type should survive a theme change, so this belongs in a plugin.
- `scripts/setup-trinity-site.php` upserts and overwrites page bodies and CF7 form/mail definitions. It is safe only as bootstrap code; rerunning it after editors begin working would destroy editorial changes.
- The database contains two orphan `archive-events` template rows (IDs 40 and 41) with no active-theme taxonomy. They appear to be remnants, not active public templates. Do not remove them before a backup and verification.

## Recommended component and editing model

### 1. Native blocks first

Use Group, Columns/Grid, Heading, Paragraph, Image, Buttons, List, Details, File, Separator, and the relevant plugin blocks. Keep the existing `tp-*` CSS classes initially so the visual output remains stable. Add human-readable block `metadata.name` values such as “Hero”, “Program card: Threes”, and “Office details” so List View is understandable.

Custom HTML should be limited to genuinely unavailable semantics, not normal headings, paragraphs, cards, tables, FAQs, links, or images. Decorative marks should normally be CSS generated content or a tightly scoped presentational block rather than mixed into editable copy.

### 2. Patterns are scaffolding, not hidden content stores

Create small theme patterns for recurring designed sections: page hero, family-link card, teacher card, schedule card, tuition card, document row, contact CTA, and event-section shell. Register full-page starter patterns for new Pages with `Block Types: core/post-content` and `Post Types: page` where useful. Theme patterns are nonsynced copies after insertion, so later PHP edits will not unexpectedly overwrite existing page copy.

Use a synced pattern only when one piece of content must truly change everywhere, such as a universal notice or one global CTA. Pattern overrides may allow per-instance copy with shared structure, but that extra database-managed layer is not justified for most of this small site.

Official references: [patterns](https://developer.wordpress.org/themes/patterns/introduction-to-patterns/) and [starter patterns](https://developer.wordpress.org/themes/patterns/starter-patterns/).

### 3. Protect design section by section

Apply content-only editing or block locking to the inner structure of high-design sections while leaving intended text, media, URLs, and approved section order editable. Do not lock one giant wrapper around the whole page. Editors should be able to change content without accidentally deleting required wrappers/classes; Administrators should retain a documented way to modify or detach a section.

WordPress 7.0 changes the editing experience for unsynced patterns toward content-only editing, but role behavior and detach/modify behavior must still be tested. If stronger role control is required, use `block_editor_settings_all` and capabilities rather than relying on visual instructions alone. See [block locking](https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/) and [WordPress 7.0 developer changes](https://developer.wordpress.org/news/2026/03/whats-new-for-developers-march-2026/).

### 4. Keep code and database responsibilities explicit

- Version control: theme templates, template parts, `theme.json`, CSS, block styles, and starter patterns.
- Database: page bodies, media attachments, Navigation, event posts, CF7 forms, and any deliberately synced patterns.
- Before each release, list active-theme `wp_template` and `wp_template_part` database forks. Export/diff them before changing the corresponding files.
- Never deploy a bootstrap script that overwrites existing page or form content. Replace it with explicit, versioned, one-time migrations that refuse to overwrite content unless a specific migration prerequisite matches.

Use Appearance > Editor's export and reset/clear-customization controls rather than deleting template rows with ad hoc SQL. See [Site Editor export](https://wordpress.org/documentation/article/site-editor/) and [template customization controls](https://wordpress.org/documentation/article/template-editor/).

## Complete page inventory and migration plan

“Page body” below means the editable `post_content` stored on that Page. “Template content” means visible copy is currently outside the Page editor.

| Priority | Public page | Current rendering source and editability | Target and required work |
| --- | --- | --- | --- |
| P0 | **Home** `/` (ID 23) | Active database Front Page template ID 66 renders the full page and has no Post Content. The file template also contains a full page design. The 9,163-character Page body is hidden/stale. Includes the dynamic weekly-events block and CF7 form ID 21. | Export and diff DB template 66, file template, hidden Page body, and public output; select the public output as truth. Rebuild/copy its sections into the Home Page body with native named blocks. Keep weekly-events as a dynamic block placed between editable heading/intro/action blocks. Replace the CF7 shortcode with the CF7 selector block. Change the front-page template to shell + Post Content only, verify parity, then reset the known DB template fork. Move theme-asset images used as content into Media Library. |
| P1 | **Parent Corner** `/parent-corner/` (ID 71) | Empty Page body; `page-parent-corner.html` contains the visible native-block hero and seven family-link cards, with no Post Content. | Copy the exact block tree into the Page body, add section/card names and appropriate section locks, then use the generic Page shell. Turn the family-link card into an unsynced inserter pattern. Preserve card order, featured/gold variants, copy, and links. |
| P1 | **Events** `/events/` (ID 28) | `page-events.html` contains the visible heading, intro, and Pie Calendar block and has no Post Content. The 577-character Page body is hidden/stale. | Move the visible template blocks into the Page body: editable intro plus `piecal/calendar`. Use the generic Page shell. Retain Events as the owner of `/events/` and keep `tp_event` without its own archive to avoid a rewrite collision. |
| P1 | **Schedule a Tour** `/schedule-a-tour/` (ID 25) | Slug template has no Post Content. Visible hero, visit cards, and raw `<details>` FAQ live in Custom HTML split around a CF7 shortcode. The 2,179-character Page body is hidden/stale. | Rebuild the visible design in the Page body with Groups/Grid, Headings, Paragraphs, core Details blocks, and the CF7 selector block. Use small patterns for visit cards and FAQ items. Retain CF7 rather than moving to the experimental core Form block. Replace “book” promises with **Request a tour** unless real date/time inventory and confirmation are implemented. Make the optional-sibling interaction form-scoped, progressive, and usable without JavaScript; do not load it solely by page slug. Then remove the slug template. |
| P1 | **Drop-off & Pick-up** `/drop-off-pick-up/` (ID 50) | Visible hero, four age/program bands, schedule cards, and office/questions section are one Custom HTML block in `page-drop-off-pick-up.html`; the Page body is a hidden one-paragraph placeholder. | Recreate in the Page body using named section Groups, program-copy Groups, and schedule-card Groups/Columns. Create unsynced schedule/program-card patterns, preserve semantic heading order and the current coral/blue/gold/navy variants, and lock only each card's structural wrapper. Remove the slug template after visual validation. |
| P1 | **Lunch Bunch** `/lunch-bunch/` (ID 51) | Visible hero, three program articles, at-a-glance definition data, alert, and fee card are one Custom HTML block in `page-lunch-bunch.html`; Page body is hidden placeholder copy. | Rebuild in the Page body with Groups, Headings, Paragraphs, and a small details grid/list. Preserve the alert and fee variants as block styles. A simple two-column section plus card patterns is sufficient; no custom dynamic block is needed. Remove the slug template after parity checks. |
| P1 | **Registration Forms** `/registration-forms/` (ID 53) | Visible hero, featured registration form, health form, two seasonal forms, return instructions, and inline SVG icons live in one Custom HTML block in `page-registration-forms.html`; Page body is hidden placeholder copy. | Create a native Document Row pattern using Groups + File/Button link + editable title/description/status/page count. Store files in Media Library and select them through blocks rather than typing upload URLs. Use CSS or a controlled icon treatment for decoration. Keep required/seasonal grouping and download behavior. Confirm every file exists and is accessible before retiring the slug template. |
| P1 | **Extended Days Program** `/extended-days-program/` (ID 24) | Template correctly renders Post Content, but the Page body is an 8,035-character Custom HTML block. A theme pattern duplicates a similar raw-HTML design. Visible content includes three stay options, bundle details, Yoga/Little Bakers/Little Builders/STEAM/Music/Karate rows, and an hours/rest note. | Convert the Page body's HTML to native named blocks while retaining existing classes. Create option-card and class-row unsynced patterns. Replace the raw-HTML pattern with block markup after the live Page is migrated, or remove it if it has no future insertion use. Keep the existing content-bearing template until the page can use the generic shell without layout change. |
| P2 | **Contact** `/contact/` (ID 29) | Generic template + editable Page body. Native hero/image/copy blocks; form is a CF7 Shortcode block. | Keep the page structure. Replace shortcode with CF7's `contact-form-7/contact-form-selector` block, name the hero/form groups, verify image uses Media Library, and apply narrow section locks if needed. |
| P2 | **Tuition Plans & Pricing** `/tuition-plans-pricing/` (ID 48) | Slug template renders Post Content. The 18,749-character Page body is already native blocks: four program cards, schedule rows, notes, CTA, and contact details. A theme pattern contains a similar full layout. | Keep the Page body as canonical. Add List View names, content-only locks per class card, and unsynced class/schedule-card patterns for future additions. Diff the registered pattern against the live Page and retire or update the duplicate so staff are not offered stale rates. Keep pricing edits in this Page, with a named annual review owner. Move to the generic Page shell only after confirming the constrained-layout difference is immaterial. |
| P2 | **Get Involved** `/get-involved/` (ID 52) | Generic template + 15,158-character native-block Page body. It contains hero paths, PFEC, Room Parent, Board sections, lists, contact cards, and anchors. A theme pattern duplicates the page. | Keep Page body canonical. Add names/section-level locks, verify internal anchors, email links, and heading order, and turn only useful subcomponents into inserter patterns. Diff/remove the duplicate full-page pattern if stale. No raw-HTML conversion is required. |
| P2 | **Meet The Teachers** `/meet-the-teachers/` (ID 27) | Generic template + large native-block Page body: 14 Details bios, 62 Groups, headings and paragraphs, with only a tiny decorative HTML fragment. A native Teacher Card pattern exists. | Keep native blocks and use the Teacher Card pattern for future staff. Normalize every card to the same named structure; replace decorative HTML with CSS/presentational markup if possible. Test Details keyboard behavior, heading hierarchy, image alt text, and responsive card heights. Do not introduce a Staff CPT unless the same teacher record genuinely needs to drive multiple views. |
| P2 | **Meet Our Director** `/meet-the-director/` (ID 26) | Generic template + editable native Groups, Image, Headings, and Paragraphs. | Keep in Page body. Add block names, verify Media Library image/alt text, and apply a light section lock. No custom template or content type is needed. |
| P2 | **Accessibility Statement** `/accessibility-statement/` (ID 31) | Generic template + editable native standard-page blocks; current body is brief/placeholder-level. | Keep the native Page body. Assign an owner to replace placeholder text with an approved statement and review contact/remediation language. Use the standard-page pattern. |
| P2 | **Privacy Policy** `/privacy-policy/` (ID 3) | Generic template + editable native standard-page blocks; content says to add the policy. | Keep the native Page body. Replace placeholder with the approved policy, including form/event-data practices and retention decisions. Use revisions and record the review date. |
| P2 | **Parent Portal** `/parent-portal/` (ID 47) | Generic template + one editable placeholder paragraph. It is not currently a portal integration. | Confirm whether this page should redirect to a real authenticated provider or become a native landing page. If retained, build a standard hero/CTA body with clear ownership, security expectations, external-link labeling, and support contact. No custom slug template is needed. |
| P2 | **Pay Tuition** `/pay-tuition/` (ID 49) | Generic template + one editable placeholder paragraph. | Confirm the payment provider and approved destination before adding a Button. Keep payment processing off-site unless there is a separately scoped, security-reviewed integration. Create a native explanatory Page body and test the external path; do not hard-code provider credentials in page blocks or theme files. |
| P3 | **Registration/standard future Pages** | New Pages currently fall through to the generic `page.html`, which correctly contains Post Content. | Offer a small set of starter page patterns (standard information page, program page, form/resource page) instead of adding more `page-{slug}.html` files. |

### Related public event surfaces

- Eight `tp_event` posts are published. Editors already manage their title/body/image, and `single-tp_event.html` correctly renders featured image, title, Pie Calendar Event Info, Post Content, and a back action.
- The monthly calendar belongs in the Events Page body; the weekly summary belongs in the Home Page body as a dynamic block. Both must use the same Pie Calendar start/end/all-day values and the WordPress `America/New_York` timezone.
- Current cleanup candidates include missing label/tone data and a likely accidental timed multi-day value for “Summer Fun.” Validate records with the school rather than silently correcting them.

## Specialist plan for the difficult areas

### Database template forks

1. Take a database backup and export the active theme from Appearance > Editor.
2. Capture desktop/tablet/mobile baseline screenshots and current HTML for all affected routes.
3. Diff database Front Page ID 66 against `templates/front-page.html`; diff Header ID 79 against `parts/header.html`.
4. Merge intentional live differences into the new Page content and/or code-canonical files.
5. Migrate the visible Page content **before** resetting or simplifying the template; otherwise the current design will disappear.
6. Reset only the two verified active-theme customizations through WordPress. Do not blindly delete template posts.
7. Recheck IDs 40/41, back up, and remove only if proven orphaned and harmless.
8. Add a release check that flags active-theme `wp_template`/`wp_template_part` forks.

### Forms

- Keep Contact Form 7 in this migration. WordPress 7.0's core Form block remains experimental and is not the safer production replacement. See the official [core Form block reference](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-widgets/core-block-form/).
- Replace Shortcode blocks/template strings with CF7's official form selector block. Page editors control placement; the form owner controls fields, validation, recipients, and mail under Contact. See [CF7 getting started](https://contactform7.com/getting-started-with-contact-form-7/) and [mail setup](https://contactform7.com/setting-up-mail/).
- Current forms are Contact Trinity Preschool (ID 21), Schedule a Tour Request (ID 22), and an apparently unused legacy form (ID 5). Do not encode IDs in theme patterns; select forms in the saved Page instance.
- The current recipient resolves to local `admin@example.com`. Before launch, set a real site/admin recipient, verify authenticated sending, and submit both forms through staging/Mailpit. Keep the submitter in Reply-To rather than From.
- Configure and test CF7's recommended [Cloudflare Turnstile integration](https://contactform7.com/turnstile-integration/) before launch.
- CF7 does not store submissions. Decide explicitly whether to install Flamingo for an inbox/backup, then document retention/deletion, or accept email-only delivery and monitor it. See [CF7 submission storage guidance](https://contactform7.com/faq/can-i-see-the-messages-submitted-through-the-contact-form/). The Tour form collects child information, so privacy/data-minimization review is required.
- The Tour is currently a request, not a confirmed appointment. Keep language accurate unless a real scheduling service is separately implemented.

### Events and weekly block

1. Create a small `wp-content/plugins/trinity-site-functionality/` plugin.
2. Move `tp_event` registration, Pie Calendar filters/integration, weekly-event query/render code, and activation-only rewrite flushing to it. WordPress explicitly recommends registering content types in a plugin so content remains portable across themes: [registering custom post types](https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/).
3. Keep presentation templates and CSS in the theme. Keep `has_archive => false` while the Events Page owns `/events/`.
4. Re-register `trinity-preschool/weekly-events` with `block.json`, Block API v3, title/category/description, an editor preview, and a useful empty state; WordPress recommends server-side registration from block metadata. Alternatively, evaluate WordPress 7.0's PHP-only `supports.autoRegister` path. See [block registration](https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/) and [block metadata](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/).
5. Keep the Home section's eyebrow, heading, explanatory copy, and action as ordinary editable blocks around a dynamic Event Grid block. Only queried event cards should be dynamic.
6. Continue using Pie Calendar as the single date source. Its published capabilities include custom post types with editor/custom fields: [Pie Calendar plugin listing](https://wordpress.org/plugins/pie-calendar/). Add a dependency notice and graceful behavior when Pie Calendar is disabled; never fatal.
7. Replace raw `tp_event_label`/`tp_event_tone` fields with either a controlled Event Type taxonomy and deterministic color mapping or registered REST-visible meta with sanitize/auth callbacks and an inspector control. Migrate existing values.
8. Confirm whether “This week” means Monday-Friday, Monday-Saturday (current code), or a rolling window. Current Sunday behavior jumps to the next Monday. Test all-day, timed, multi-day, overlapping, empty, DST, month/year-boundary, draft, and published cases.

The single-event Details-like content should continue to use native Post Content. For FAQs elsewhere, use the official [Details block](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-details/).

## Phased implementation order

### Phase 0 — Preserve the live truth

- Back up database and uploads.
- Export Site Editor changes; inventory database template/template-part forks.
- Capture route-by-route desktop, tablet, and mobile baselines plus form/calendar behavior.
- Record live page content, links, media IDs, forms, event data, and active plugins.
- Disable/retire `scripts/setup-trinity-site.php` as a routine command before editorial migration begins.

**Gate:** the team can restore the site and can explain which version of Home/Header is canonical.

### Phase 1 — Establish safe primitives

- Make `page.html` the intended shell and create named native-block section/card patterns.
- Define the lock/content-only policy and test it as an Editor and Administrator.
- Ensure frontend/editor CSS parity and move content images/files to Media Library.
- Replace CF7 shortcodes on already-editable pages and complete mail/spam/storage decisions.

**Gate:** a test Page can be safely edited, revised, restored, and rendered with the current visual system.

### Phase 2 — Quick migrations out of templates

- Migrate Parent Corner and Events into their Page bodies.
- Remove their slug templates only after one-to-one visual and functional comparison.
- Curate Contact, Director, Teachers, Tuition, and Get Involved without rebuilding them.

**Gate:** Editors can update every visible word/link on these routes through Pages/Events, with no Appearance > Editor access.

### Phase 3 — High-design Custom HTML conversions

- Convert Lunch Bunch and Drop-off & Pick-up first to prove the card/section patterns.
- Convert Extended Days, Registration Forms, and Schedule a Tour.
- Validate each route before removing the old content-heavy slug template or raw HTML.

**Gate:** no content-bearing page relies on Custom HTML for ordinary copy/cards/FAQ/files, and every download/form works.

### Phase 4 — Home and database override reconciliation

- Migrate the exact active Home design into page ID 23.
- Put the editor-grade weekly block and CF7 selector block into the Page body.
- Simplify Front Page to shell + Post Content, merge Header changes into the file, and reset the two known database forks.

**Gate:** file template changes take effect, Home is fully editable in Pages, and visual baselines remain within approved tolerance.

### Phase 5 — Event portability and hardening

- Move event functionality into the site plugin.
- Ship the Block API v3 weekly block/editor preview and event type UI.
- Clean/migrate existing event metadata after editorial confirmation.
- Add dependency, timezone, boundary, theme-switch, and plugin-disable tests.

**Gate:** event data/admin survives a theme switch; disabling Pie Calendar does not fatal; monthly, weekly, and single-event views agree.

### Phase 6 — Governance and handoff

- Assign owners for annual rates, schedules, forms/files, privacy/accessibility copy, events, form delivery, and external payment/portal links.
- Give Editors a one-page workflow and restrict Site Editor/template access to the people responsible for global structure.
- Add launch/release checks for database template forks, link/file integrity, form delivery, and visual regression.

## Editor workflow after migration

1. **Pages:** choose the Page, use List View to select a named section, edit text/media/link targets, Preview at desktop/tablet/mobile, then Update. Reorder only approved top-level sections. Use Revisions to restore prior copy.
2. **Events:** add/edit an Event, set its controlled type, Pie Calendar date/time/all-day values, featured image, excerpt, and body; Preview, then Publish. Do not edit calendar/weekly markup per event.
3. **Forms:** designated form owners edit fields and mail settings under Contact. Page Editors only place/select the form block. Test delivery after any form/mail change.
4. **Menus:** designated owners update the Navigation entity. Page editors should not edit Header markup to change links.
5. **New Pages:** insert an approved starter pattern, replace its content, and remove unused sections. Do not ask a developer to add another content-heavy slug template.
6. **Global design:** colors, type, spacing, header/footer structure, block styles, and CSS are released through the theme, with an export/diff first if the Site Editor shows customizations.

## Acceptance criteria

### Editorial

- An Editor—not only an Administrator—can change each visible page heading, paragraph, button URL/label, image/alt text, FAQ answer, schedule/rate, and document link from Pages, save, and see it after reload.
- Home, Tour, and Events require neither Appearance > Editor nor code changes for normal copy/layout placement edits.
- List View uses meaningful section/component names. Intended content remains editable; required wrappers/classes cannot be accidentally removed. An Administrator can intentionally modify/detach under the documented process.
- Page revisions restore a prior working version.

### Visual and accessibility

- Before/after screenshots at representative desktop, tablet, and mobile widths preserve current layout, spacing, colors, type, borders, card variants, and responsive order.
- There is exactly one H1 per Page; heading order and landmarks are sensible. Keyboard focus, Details controls, form errors/status, link purpose, image alt text, contrast, and zoom/reflow are checked.
- No editor reports “unsupported” or “invalid block,” and frontend/editor previews are meaningfully consistent.

### Forms

- CF7 selector blocks render in the editor. Required, email, number, and choice validation; keyboard order/focus; success/error announcement; the sibling no-JavaScript path; and duplicate-ID checks pass.
- Both forms submit successfully to Mailpit/staging and the real delivery path, with correct To, From, Reply-To, subject, and body. Turnstile rejects a bot test. The storage/retention choice is documented and exercised.
- All Tour UI says “Request” unless a real slot is reserved and confirmed.

### Events

- Draft, published, all-day, timed, overlapping, and multi-day event tests agree across monthly calendar, Home weekly grid, popover, and single view in `America/New_York`.
- DST and week/month/year boundaries, Sunday behavior, the configured week window, and the empty state are verified.
- Switching themes in staging leaves Events admin/content available; switching back restores presentation and permalinks. Disabling Pie Calendar produces no fatal and shows a useful dependency/fallback state.

### Deployment and data safety

- The intentional DB Front Page/Header customizations were exported, diffed, merged, and reset; a subsequent code template change is visible.
- A release check flags any new active-theme template/template-part forks.
- No migration or bootstrap rerun overwrites editor-authored page/form content. Backups and rollback steps have been tested.
- All internal links, mail/tel links, external payment/portal links, and Media Library downloads return the expected result.

## Dependencies, risks, and decisions needed

| Item | Decision/dependency | Risk control |
| --- | --- | --- |
| Active DB Front Page/Header | Decide which DB-vs-file differences are intentional | Backup, export, diff, migrate first, reset only verified forks |
| Design parity | Baseline screenshots and a reviewer who can approve small rendering differences | One route at a time; retain old template until approval |
| CF7 recipient/delivery | Real recipient, authenticated sender, operational owner | Mailpit/staging tests, Turnstile, delivery monitoring, rollback |
| Form data retention | Email-only vs Flamingo; retention/deletion period | Privacy review, least data, documented access/deletion |
| Tour semantics | Request workflow vs real scheduling/confirmation system | Use “Request” until the latter exists |
| Payment and Parent Portal | Approved external providers/URLs/support owner | Do not simulate integrations or store secrets in page/theme content |
| Events model | Confirm week definition, event types, and suspect event values | Migrate after editorial sign-off; boundary tests |
| Pie Calendar coupling | Custom weekly code reads Pie's private `_piecal_*` metadata | Pin/test upgrades in staging; guard missing dependency; retain event posts |
| Large page block trees | Editors could accidentally restructure heavily styled cards | Named sections, small patterns, scoped content-only locks, revisions |
| Duplicate pattern/page copies | Pattern content can become stale next to live Page content | Make Page body canonical; patterns generic; remove stale full-page duplicates |
| Bootstrap script | Rerun overwrites editorial content/forms | Retire it or convert to guarded, one-time, versioned migrations |

## Files and data expected to change during implementation

Theme:

- `wp-content/themes/trinity-preschool/templates/front-page.html`
- `wp-content/themes/trinity-preschool/templates/page.html`
- Content-heavy `templates/page-*.html` files, removed only after their content is migrated
- `wp-content/themes/trinity-preschool/parts/header.html`
- `wp-content/themes/trinity-preschool/patterns/*.php`
- `wp-content/themes/trinity-preschool/theme.json`
- `wp-content/themes/trinity-preschool/style.css`
- Theme `functions.php`, reduced as event functionality moves to the plugin

Site functionality:

- New `wp-content/plugins/trinity-site-functionality/` plugin, including `block.json` and the weekly-events render/editor registration

Database/editorial content:

- Page bodies for IDs 3, 23–31, 47–53, and 71 as listed above
- Front Page template ID 66 and Header template-part ID 79, after export/diff
- Navigation entity, only if link ownership/corrections require it
- CF7 forms IDs 21 and 22; legacy ID 5 after verification
- Eight published `tp_event` posts and migrated event type/meta values
- Media Library attachments for content images and downloadable forms

Tooling:

- `scripts/setup-trinity-site.php`, retired from routine use or converted to guarded migrations
- Optional release/audit scripts for template forks, links/files, and smoke tests

## Definition of done

This migration is complete when every published Page has one obvious editor-owned source for its visible content, the theme owns only reusable structure/presentation, event functionality survives theme changes, forms and events pass their functional tests, database template forks are understood and controlled, and the current design has been approved at desktop/tablet/mobile sizes. No page should require editing PHP/HTML templates for a normal annual content update.
