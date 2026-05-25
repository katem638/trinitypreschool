/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import ServerSideRender from "@wordpress/server-side-render";
import {
  PanelRow,
  PanelBody,
  TextControl,
  CheckboxControl,
  SelectControl,
} from "@wordpress/components";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
  const FRAGMENT_OPTIONS = [
    { label: __("Start Time", "piecal"), value: "start" },
    { label: __("End Time", "piecal"), value: "end" },
    { label: __("Timezone", "piecal"), value: "timezone" },
    { label: __("All Day", "piecal"), value: "allday" },
  ];

  const FORMAT_OPTIONS = [
    { label: __("Site Date/Time Format", "piecal"), value: piecalGbVars.dateFormat + " \\a\\t " + piecalGbVars.timeFormat },
    { label: __("Default, 24 Hour", "piecal"), value: "F j, Y \\a\\t H:i" },
    { label: __("Numerical Year-Month-Day, 24 Hour", "piecal"), value: "Y-m-d H:i" },
    { label: __("Numerical Year-Month-Day, 12 Hour", "piecal"), value: "Y-m-d g:i a" },
    { label: __("Numerical Month/Day/Year, 24 Hour", "piecal"), value: "m/d/Y H:i" },
    { label: __("Numerical Month/Day/Year, 12 Hour", "piecal"), value: "m/d/Y g:i a" },
    { label: __("Numerical Day/Month/Year, 24 Hour", "piecal"), value: "d/m/Y H:i" },
    { label: __("Numerical Day/Month/Year, 12 Hour", "piecal"), value: "d/m/Y g:i a" },
    { label: __("Verbose, 24 Hour", "piecal"), value: "l, F j, Y \\a\\t H:i" },
    { label: __("Verbose, 12 Hour", "piecal"), value: "l, F j, Y \\a\\t g:i a" }
  ];

  if (piecalGbVars.additionalFragments) {
    Object.entries(piecalGbVars.additionalFragments).forEach(([label, value]) => {
      FRAGMENT_OPTIONS.push({
        label: __(label, "piecal"),
        value: value
      });
    });
  }

  return (
    <div {...useBlockProps()}>
      <InspectorControls>
        <PanelBody title={__("Info Settings", "piecal")} initialOpen={true}>
          <PanelRow>
            <SelectControl
              label={__("Format", "piecal")}
              value={attributes.format}
              options={FORMAT_OPTIONS}
              onChange={(format) => setAttributes({ format })}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__("Format String", "piecal")}
              value={attributes.format}
              onChange={(format) => setAttributes({ format })}
              defaultValue={piecalGbVars.dateFormat + " \\a\\t " + piecalGbVars.timeFormat}
              help={__("Non-formatting characters should be escaped with backslashes like \\t\\h\\i\\s.", "piecal")}
            />
          </PanelRow>
          <hr />
          <PanelRow className="piecal-gb-checkbox-group">
            <h3>{__("Info Options", "piecal")}</h3>
            <fieldset>
              <legend>{__("Select Info To Show (Optional):", "piecal")}</legend>
            {FRAGMENT_OPTIONS.map((option) => (
              <CheckboxControl
                key={option.value}
                label={option.label}
                checked={attributes.fragments?.includes(option.value)}
                onChange={(checked) => {
                  let newFragments = [...(attributes.fragments || [])];
                  if (checked) {
                      newFragments.push(option.value);
                  } else {
                    newFragments = newFragments.filter(f => f !== option.value);
                  }
                  setAttributes({ fragments: newFragments });
                }}
              />
            ))}
            </fieldset>
          </PanelRow>
          <hr />
          <h3>{__("Custom Text", "piecal")}</h3>
          <PanelRow>
            <TextControl
              label={__("Start Text", "piecal")}
              value={attributes.startText}
              onChange={(startText) => setAttributes({ startText })}
              help={__("Leave empty to use default text", "piecal")}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__("End Text", "piecal")}
              value={attributes.endText}
              onChange={(endText) => setAttributes({ endText })}
              help={__("Leave empty to use default text", "piecal")}
            />
          </PanelRow>
          <PanelRow>
            <TextControl
              label={__("All Day Text", "piecal")}
              value={attributes.allDayText}
              onChange={(allDayText) => setAttributes({ allDayText })}
              help={__("Leave empty to use default text", "piecal")}
            />
          </PanelRow>
          <hr />
          <h3>{__("Additional Options", "piecal")}</h3>
          <PanelRow>
            <CheckboxControl
              label={__("Hide Time Zone", "piecal")}
              checked={attributes.hidetimezone}
              onChange={(hidetimezone) => setAttributes({ hidetimezone })}
            />
          </PanelRow>
          <PanelRow>
            <CheckboxControl
              label={__("Hide Prepend Text", "piecal")}
              checked={attributes.hidePrependText}
              onChange={(hidePrependText) => setAttributes({ hidePrependText })}
            />
          </PanelRow>
        </PanelBody>
      </InspectorControls>
      <ServerSideRender block="piecal/event-info" attributes={attributes} />
    </div>
  );
}
