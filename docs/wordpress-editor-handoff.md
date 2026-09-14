# Trinity WordPress editor handoff

Status: local editable-content migration completed on 2026-08-17.

## Where content is edited

- **Pages:** all public page copy, images, links, cards, FAQs, schedules, rates, files, and section order now live in the relevant Page body.
- **Events:** event title, body, excerpt, image, date/time, and Event Type are edited under **Events**. The Events Page calendar and Home weekly grid read those records automatically.
- **Contact forms:** fields, validation, recipients, and email templates are edited under **Contact**. Page editors only position the Contact Form 7 selector block.
- **Navigation:** menu links are managed in the named **Primary Navigation** menu under Appearance → Editor → Navigation. The Header references that menu; do not edit the Header file for routine menu changes.
- **Theme code:** the header/footer shell, colors, Fredoka/Poppins typography, spacing, and component CSS remain developer-owned.

## How the Pages are constructed

The theme templates are intentionally small shells: Header, a main content area,
the Post Content block, and Footer. Each Page owns its visible sections as native
WordPress blocks in the database. Group blocks hold layout and design classes;
Heading, Paragraph, Image, Button, List, Details, and File blocks hold the content
an administrator changes. Theme patterns are starting copies for adding a new
designed card or section; editing a pattern file does not overwrite an existing
Page.

Use one block for one semantic field, not one block for every visual line. A
multi-line postal address can be one Paragraph. Phone and email are independent
Paragraph blocks because they have different link targets and may need separate
editing. Do not use repeated line breaks for spacing; spacing belongs to the
theme styles.

### Text editing versus design editing

- To change words or a link target, click the text in the canvas or select its
  named block in List View.
- To change font size, select the containing Paragraph or Heading block and use
  **Settings → Block → Typography**. Font size is a block setting; selecting only
  the inline link opens link controls, not typography controls.
- Content-only sections deliberately hide structural and design tools while
  leaving their words, links, and media editable. This protects card layouts from
  accidental removal. Administrators can use **Modify** for intentional design
  work; routine editors should not need it.
- Header and Footer contact fields are separate named blocks under **Appearance →
  Editor → Design → Patterns → Manage all template parts**. Menu links remain in
  the Primary Navigation entity.

## Normal Page workflow

1. Open **Pages** and choose the page.
2. Open **Document Overview → List View** and select the named section or card.
3. Edit the intended text, image, alt text, file, or link. High-design wrappers use content-only locking so their required structure remains intact.
4. Preview desktop, tablet, and mobile.
5. Save. Use **Revisions** if a prior version needs to be restored.

Reusable Trinity patterns are available for page heroes, content/family cards, schedule cards, document rows, contact CTAs, FAQs, Tour visit steps, Extended Day options/classes, tuition cards, and teacher cards. New Pages can start from the Standard, Program, or Forms and Resources starter layouts. Patterns are unsynced starting copies; after insertion, that Page owns its copy.

## New Page workflow

1. Open **Pages → Add New** and enter the Page title. The default Page template automatically renders that title in a branded navy title band, so a new Page is never visually blank even if no starter is selected.
2. Choose the Standard, Program, or Forms and Resources starter when WordPress offers the starter-pattern picker. These patterns intentionally begin below the automatic Page title and must not add another H1.
3. Use the **Designed Page (title in content)** template only for a custom landing page whose Page body contains its own designed hero and H1. All migrated public Pages use this template.
4. Add reusable Trinity component patterns for cards, resources, FAQs, teachers, or CTAs as needed.
5. Confirm one H1, meaningful link labels, image alt text, and desktop/mobile previews before publishing.

Get Involved, Meet Our Director, and Meet The Teachers received a second editor-curation pass in migration v4. Their named sections and staff cards are ordinary selectable native blocks, the Teachers title divider is a Separator block, and only the outer Director section has a light removal lock. Insert the **Teacher Card** pattern inside the relevant “Teacher cards” group when adding staff, then update its List View name and optional HTML anchor to match the teacher.

## Event workflow

> Current local exception: the Calendar Page presently contains an editable
> sample 2026–27 date list, and the Home weekly-event block is absent. The Events
> content type still exists with older May/June 2026 records. Before launch, choose
> one source of truth: populate Events and restore the dynamic calendar/weekly
> blocks (recommended), or formally retire the unused event system and update the
> release audit. Do not maintain both the sample Page list and Event records as
> live schedules.

1. Open **Events → Add New Event**.
2. Add the title, description, excerpt, and featured image.
3. Set one controlled **Event Type**. Its color is managed under **Events → Event Types**.
4. Set the Pie Calendar date/time/all-day values in the event editor.
5. Preview and publish. The monthly Events calendar, Home Monday–Saturday grid, and single-event page use the same record.

The event content type and weekly block live in `wp-content/plugins/trinity-site-functionality/`, so event records remain available if the theme changes. If Pie Calendar is unavailable, WordPress continues to load without a fatal error and administrators receive a dependency notice.

## Form workflow

- **Contact Trinity Preschool:** Contact Form 7 form ID 21.
- **Schedule a Tour Request:** Contact Form 7 form ID 22.
- Keep the submitter's address in **Reply-To**, not **From**.
- The Tour is a request, not a confirmed appointment. Keep all public action language as “Request” unless a scheduling service is separately implemented.
- After any field or mail change, test required/email/number/choice validation and submit to Mailpit or staging before release.

The optional sibling fields are visible without JavaScript. When JavaScript loads, they start collapsed and disabled until **Add a sibling** is selected; the control is scoped to its own form and moves focus to the first revealed field.

## Release checks

Run from the project root:

```bash
ddev wp eval-file scripts/audit-trinity-site.php
```

The audit fails if a published Page is missing, a migrated Page loses the Designed Page template, a Page lacks exactly one H1 block or revision history, a Shortcode block returns, a content-bearing slug template returns, the generic/landing/front-page shells regress, a fallback template is missing, starter-pattern styling disappears, the Header loses Primary Navigation, placeholder footer/payment links return, an active-theme database template fork appears, a migration version changes unexpectedly, or the event/form blocks are unavailable.

Also perform the route/link crawl, responsive visual comparison, form-delivery test, and event boundary tests described in `docs/wordpress-editability-plan.md` before production deployment.

## Data safety and reruns

- `scripts/setup-trinity-site.php` now refuses to run after the editable-content migration is recorded.
- `scripts/migrate-editable-content.php` is a guarded, versioned migration. It refuses an ordinary rerun and only contains explicit upgrade paths for its own earlier local versions. Version v4 completes the Get Involved, Director, and Teachers editor curation without replacing their copy.
- `scripts/migrate-admin-editability.php` is the guarded 2026-09-14 consistency pass. It separates contact methods, removes spacing-only line breaks, adds meaningful names to remaining layout groups, and repairs the Director portrait alt text while preserving Page copy and revisions.
- Page and event revisions remain enabled.
- The pre-migration project archive, database dump, checksums, restore instructions, and 51 responsive baseline screenshots are stored outside the repository in `/Users/katemaugeri/Documents/Projects/trinitypreschool-backups/20260817-170540/`.
- The pre-hardening checkpoint is commit `143ab94`; its verified full site-files archive, database dump, Git bundle, checksums, and restore instructions are stored in `/Users/katemaugeri/Documents/Projects/trinitypreschool-backups/20260830-215948/`.

## Production decisions still requiring school-approved values

- Replace local `admin@example.com` form recipients with the real monitored inbox and configure authenticated sending.
- Configure and test Cloudflare Turnstile.
- Decide email-only submissions versus Flamingo, then approve a retention/deletion period—especially for child information in Tour requests.
- Supply the approved Parent Portal and tuition-payment provider URLs. Until then, those Pages intentionally provide office-contact instructions and do not simulate integrations.
- Review and approve the drafted Privacy Policy and Accessibility Statement.
- Confirm Monday–Saturday as the Home week definition, approve the Event Type vocabulary, and review the existing “Summer Fun” multi-day timestamp before changing it.
- Replace the temporary Calendar Page sample list with confirmed Event records and restore the Home weekly grid, or approve retiring the Events system.
- Assign named operational owners for annual rates/schedules, forms/files, events, form delivery, and legal/accessibility copy.
