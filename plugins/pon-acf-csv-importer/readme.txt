Point One – State ACF CSV Importer (v1.2.0)

WHAT IT DOES
- Exports the existing 49 published State pages as a read-only inventory CSV.
- Previews proposed changes to hero_text, location_headline, and up to six ACF FAQ repeater rows.
- Requires the entire CSV to pass validation before applying any changes (no partial imports).
- Saves a rollback backup in WordPress before applying changes.
- Does NOT edit maps, competitors, SEO metadata, page slugs or other post types.

INSTALL / UPDATE
- In WordPress Staging: Plugins > Add New > Upload Plugin. Select this ZIP.
- If an earlier version exists, choose Replace current with uploaded. Do not delete the old plugin first: retained rollback backups may be needed.
- The tool appears in Tools > Point One: CSV ACF.
- The plugin UI is English-only. The browser/OS may still display built-in file picker dialogs in its own locale.

WORKFLOW
1. Download current State inventory. This is a read-only export for reference.
2. Prepare a SEPARATE import CSV with 'slug', optional 'state', optional 'hero_text'/'location_headline', and faq_1_question/faq_1_answer through faq_6_question/faq_6_answer.
3. Any partial FAQ block, invalid slug or pending placeholder prevents all changes.
4. Preview the import. Only confirm if every row and changed field is expected. Type IMPORT to apply.
5. Verify the public State page. Use a stored backup if rollback is needed.

IMPORTANT
- Empty simple-field cells are ignored. __CLEAR__ clears a simple text field.
- For FAQ updates, provide all six question-and-answer pairs for each updated State page. This replaces the existing repeater.
- All FAQ3 free-network answers must be individually verified before import.
- An inventory CSV and an import CSV have different headers and must not be confused.
- The plugin has been syntax checked locally, but must still be functionally tested on WordPress Staging before any bulk import.

Version 1.2.0: Accept five or six consecutive fully populated FAQ pairs. Leave faq_6_question/answer blank for five-FAQ states. Always preview before applying.
