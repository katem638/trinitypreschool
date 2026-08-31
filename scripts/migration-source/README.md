# Frozen pre-migration template sources

These files are immutable input fixtures for `scripts/migrate-editable-content.php`. They preserve the visible content that originally lived in content-heavy slug templates, allowing the versioned migration to run after those live theme templates have been removed.

WordPress never loads files from this directory as templates. Do not update these snapshots for ordinary content changes; migrated content belongs in the Page editor.
