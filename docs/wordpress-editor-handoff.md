# Trinity WordPress editor handoff

Status: local editable-content migration completed on 2026-08-17.

## Where content is edited

- **Pages:** all public page copy, images, links, cards, FAQs, schedules, rates, files, and section order now live in the relevant Page body.
- **Events:** event title, body, excerpt, image, date/time, and Event Type are edited under **Events**. The Events Page calendar and Home weekly grid read those records automatically.
- **Contact forms:** fields, validation, recipients, and email templates are edited under **Contact**. Page editors only position the Contact Form 7 selector block.
- **Navigation:** menu links are managed in WordPress Navigation. Do not edit the Header file for routine menu changes.
- **Theme code:** the header/footer shell, colors, Fredoka/Poppins typography, spacing, and component CSS remain developer-owned.

## Normal Page workflow

1. Open **Pages** and choose the page.
2. Open **Document Overview → List View** and select the named section or card.
3. Edit the intended text, image, alt text, file, or link. High-design wrappers use content-only locking so their required structure remains intact.
4. Preview desktop, tablet, and mobile.
5. Save. Use **Revisions** if a prior version needs to be restored.

Reusable Trinity patterns are available for page heroes, content/family cards, schedule cards, document rows, contact CTAs, FAQs, Tour visit steps, Extended Day options/classes, tuition cards, and teacher cards. New Pages can start from the Standard, Program, or Forms and Resources starter layouts. Patterns are unsynced starting copies; after insertion, that Page owns its copy.

Get Involved, Meet Our Director, and Meet The Teachers received a second editor-curation pass in migration v4. Their named sections and staff cards are ordinary selectable native blocks, the Teachers title divider is a Separator block, and only the outer Director section has a light removal lock. Insert the **Teacher Card** pattern inside the relevant “Teacher cards” group when adding staff, then update its List View name and optional HTML anchor to match the teacher.

## Event workflow

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

The audit fails if a published Page is missing, a Page lacks exactly one H1 block or revision history, a Shortcode block returns, a content-bearing slug template returns, the generic/front-page shell loses Post Content, an active-theme database template fork appears, the migration version changes unexpectedly, or the event/form blocks are unavailable.

Also perform the route/link crawl, responsive visual comparison, form-delivery test, and event boundary tests described in `docs/wordpress-editability-plan.md` before production deployment.

## Data safety and reruns

- `scripts/setup-trinity-site.php` now refuses to run after the editable-content migration is recorded.
- `scripts/migrate-editable-content.php` is a guarded, versioned migration. It refuses an ordinary rerun and only contains explicit upgrade paths for its own earlier local versions. Version v4 completes the Get Involved, Director, and Teachers editor curation without replacing their copy.
- Page and event revisions remain enabled.
- The pre-migration project archive, database dump, checksums, restore instructions, and 51 responsive baseline screenshots are stored outside the repository in `/Users/katemaugeri/Documents/Projects/trinitypreschool-backups/20260817-170540/`.

## Production decisions still requiring school-approved values

- Replace local `admin@example.com` form recipients with the real monitored inbox and configure authenticated sending.
- Configure and test Cloudflare Turnstile.
- Decide email-only submissions versus Flamingo, then approve a retention/deletion period—especially for child information in Tour requests.
- Supply the approved Parent Portal and tuition-payment provider URLs. Until then, those Pages intentionally provide office-contact instructions and do not simulate integrations.
- Review and approve the drafted Privacy Policy and Accessibility Statement.
- Confirm Monday–Saturday as the Home week definition, approve the Event Type vocabulary, and review the existing “Summer Fun” multi-day timestamp before changing it.
- Assign named operational owners for annual rates/schedules, forms/files, events, form delivery, and legal/accessibility copy.
