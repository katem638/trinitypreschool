=== Pie Calendar - Events Calendar Made Simple ===
Contributors: apexws, spellhammer
Tags: events, calendar, event
Donate link: https://piecalendar.com
Requires at least: 5.9
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Events calendar - the simple way. Easily display any WordPress post as an event on a flexible front-end calendar. Supports any post type.

== Description ==
**Create an event calendar in less than 4 minutes. Simple, flexible, and light-weight.**

Pie Calendar lets you effortlessly turn any post on your WordPress site into an event, making it visible on a user-friendly front-end calendar. It doesn't lock you into any post type - use the default WordPress posts or pages, or create your own Custom Post Type (CPT).

This plugin is crafted with careful thought, ensuring an event management system that’s lean, powerful, and incredibly flexible. Whether it's a page, post, or CPT, any post on your site can be turned into an event and featured on your calendar in a matter of minutes. 

==Watch our 4-minute Quick Start Guide: ==

https://www.youtube.com/watch?v=ncdab1v_B1M

==Create Events in Record Time==
Work directly in the WordPress Editor with existing posts and interfaces you’re already familiar with. 

There's no external editor or custom interface to learn. In a matter of minutes, you can turn any post into an event on your calendar.

1. Install the plugin.
2. Edit any page or post and in the sidebar (Gutenberg) or below your post content (Classic Editor / non-Gutenberg), enable the “Show on Calendar” toggle.
3. Set a start date and time, optionally set an end date, and optionally enable the "all day" event if you’d like.
4. Add the [piecal] shortcode anywhere you want your calendar to appear.

== Features ==
1. **Turn Any Post Into an Event:** Within minutes, convert any post on your site into an event that appears on your front-end calendar.
2. **Works With Any Post Type or CPT:** Use default WordPress posts or pages, or create your own CPT. Pie Calendar offers you total flexibility.
3. **Single, Multi-day, All Day Events:** Set your events for a single day, span them across multiple days, or enable all-day events.
4. **Multiple Views**: Pick from regular monthly calendar view, week views, list views, or even a single day view.
5. **Custom Field Support**: Already have your own custom fields for Date and Time via Advanced Custom Fields or Meta Box? No problem! Pie Calendar can use custom fields for event date and time.
6. **Recurring Events (Pro):** With flexible recurrence options, you can set your events to repeat as needed - every few days, weeks, or on a specific day every month.
7. **Color-Coded Events (Pro):** Give your events a unique color to make them stand out on the front-end or for enhanced organization.
8. **WooCommerce & EDD Support (Pro):** Transform any WooCommerce product or EDD Download into an event to create custom booking and event ticketing systems.

= More Info =
Once you’ve installed the plugin, go to any post or page and enable the “Show on Calendar” toggle.
Then, configure the options to your liking. Set a start date and time, optionally set an end date, and configure the all day event if you’d like.

You can do this to as many or as few posts as you’d like. Any post type will be supported, so long as your custom post types have **supports: editor** and **supports: custom fields** turned on. For clarity on this, watch the video embedded above.

Then, add the [piecal] shortcode anywhere you want your calendar to appear.

The shortcode accepts a few different parameters: 

* **type="your-post-type"**
Accepts a single value of any post type name to limit your calendar to only show that post type.
* **view="dayGridMonth"** is the default view you want selected when the calendar loads. 
The possible views are ‘dayGridMonth’, ‘listMonth’, ‘timeGridWeek’, ‘listWeek’, ‘dayGridWeek’. 
* **locale="es"** - accepts any two letter ISO 639-1 language code 

The shortcode defaults to show all post types, dayGridMonth, and locale of en.

== Frequently Asked Questions ==
=Will this work with my theme?=
Yes, this plugin should work with almost any theme. We have tested it with the top 10+ themes in the WP theme repo and they all work with Pie Calendar. 
Builders such as Bricks and Oxygen work with Pie Calendar, although the shortcode doesn't render inside the builder itself.  

=Does it work with Custom Post Types?=
Yes, they just need to have supports: editor and supports: custom-fields enabled.

=What are the view options available with Pie Calendar?=
Your visitors can pick from a dropdown of views on the frontend including dayGridMonth, listMonth, timeGridWeek, listWeek, or dayGridWeek. 

You can also use a shortcode parameter to choose what the default is upon page load.

=Can I show only one post type on the front-end?=
Yes! If you've turned multiple different types of posts into events and you only want to show one on a specific calendar, you can use the shortcode parameter: [piecal type="events"]. 

Simply replace "events" with the appropriate name of your post type. 

=Does it work with RTL languages?=
Yes, the calendar plugin pickups if your WordPress language is RTL via the is_rtl() function and will adapt accordingly. Consider also using the "locale" parameter in your [piecal] shortcode. 

=I have another question=
Feel free to read our documentation for more detailed info: [docs.piecalendar.com](https://docs.piecalendar.com/ "Pie Calendar Docs Website") or open a support thread here on our plugin page. 

== Screenshots ==
1. default

== Changelog ==

= 1.3.1 =
* Tweak: Show "Back to Full Month" button even when Widget Mode isn't enabled.
* Tweaks: Dates are now shown in day headers for some views: 'timeGridWeek', 'listWeek', 'dayGridWeek'. Turn this off with new filter: add_filter('piecal_day_header_did_mount_showdates', function() { return false; }, 10, 1);
* i18n: Added Icelandic to locale dropdown for calendar block.
* i18n: Added Icelandic day names (including shortened versions) for when Icelandic is chosen as language or locale.

= 1.3.0.5 = 
* i18n: Made Pie Calendar Info block controls translatable via Loco Translate or other methods.
* Tweak: Added white-space handling for popover details to handle \n in descriptions.
* Fix: Corrected error caused by switching to Week - Time Grid or Week - Day Grid while hidePastEvents="true" attribute is in use.

= 1.3.0.4 =
* Tweak: Addressed some more minor issues in the code to better comply with .org requirements.

= 1.3.0.3 =
* Tweak: Addressed a few minor issues turned up during a security review.
* Tweak: Changed readme to comply with .org requirements.

= 1.3.0.2 =
* Tweak: Added hooks for additional event source support.

= 1.3.0.1 =
* Fix: Use multibyte safe function for limiting excerpt length to avoid breakages when multibyte characters are present.

= 1.3.0 =
* New: Added more robust method for allowing HTML in popover details. See docs for more info.
* Tweak: End date picker now defaults to same day as start date if defined.
* Security: Fixed XSS vulnerability in output of $wrapperViewAttribute,
* Tweak: Swapped to date_i18n() for date output in piecal-info.

= 1.2.9 =
* New: Custom views API. See our documentation for more information.
* New: List - Upcoming view with duration attribute, useful for sites that have fewer events spread out over multiple months to prevent showing an empty calendar.
* Security: Fixed Authenticated (Contributor+) Stored-XSS vulnerability related to theme attribute reported to us on August 21st.
* Fix: Renamed alpine dependency to indicate it's minified. This may resolve issues with some optimization plugins.
* Tweak: Adjusted view chooser dropdown styles.

= 1.2.8 =
* i18n: Calendar controls in Gutenberg sidebar are now translatable via tools like Loco Translate
* Tweak: Added new filter to hide Pie Calendar controls: add_filter( 'piecal_hide_controls', '__return_true', 10, 1 );
* Tweak: Adjusted width of classic metabox input for better appearance on narrow screens
* Fix: piecalGbVars not defined error when using blocks in a block theme template

= 1.2.7 =
* New: Added calendar blocks for the Block Editor
* Fix: Metabox logic has been improved to accommodate post types that are available in REST but use classic editor
* Fix: Datetime pickers should now adapt to the 12/24 hour time format used by the site

= 1.2.6 =
* Security Fix: Prevented execution of JavaScript in the locale attribute of the [piecal] shortcode.

= 1.2.5 =
* Fix: Corrected issue causing sticky posts to show up on calendar even when not designated as events.
* Fix: Fixed incorrect date in single day view when using widget mode.
* Fix: Increased z-index of popover.
* Tweak: Refactored logic responsible for displaying classic Pie Calendar metaboxes.
* Tweak: Refactored the way scripts and styles are loaded to prevent undefined variable errors on some sites.

= 1.2.4 =
* Fix: Corrected issue that caused events on sites with UTC+0 timezone to not show up on calendar sometimes.
* Fix: Cleaned up and improved piecal-info display logic.
* Fix: Corrected issue where events were showing at 12:00 when using ACF start/end fields
* Tweak: Added more calendar JS hooks.
* Tweak: Added super-minimal onboarding admin notice wth some basic instructions.
* Tweak: Added translation functions for classic metabox strings.

= 1.2.3 =
* New: Add custom field data and images to popover.
* Tweak: Added piecal_popover_details filter.
* Tweak: Added piecal_popover_before_title hook.
* Tweak: Added piecal_popover_after_title hook.
* Tweak: Added piecal_popover_before_meta hook.
* Tweak: Added piecal_popover_before_details hook.
* Tweak: Added piecal_popover_after_details hook.
* Tweak: Added piecal_popover_before_view_link hook.
* Tweak: All day event start times are now forced to 12:00 PM to avoid timezone bleed.
* Fix: Corrected a bug that caused day names to be duplicated sometimes.
* Fix: Added versions to our CSS on the front-end to prevent issues with caching & future updates.

= 1.2.2 =
* A11y: Fixed an issue causing double date announcement when switching views (#266)
* A11y: Improved the announcement of changes when the calendar view changes (#248)
* A11y: AM/PM are no longer shortened to A/P (#251)
* A11y: Fixed missing role on view title/date range text at top of calendar (#317)
* A11y: Improved default all-day event color contrast (#317)
* A11y: Clickable event elements now announce clickability on non-Grid views (#263)
* A11y: Added labels and removed toggle logic from next/prev buttons for better accessibility (#245)
* A11y: Improved announcement of all day, multi-day events to indicate date range (#250)
* A11y: Made sure abbreviated day names are announced with full names (#249)
* I18n: Added special day name mapping for shortening day names in Hebrew and Arabic (#249)
* Tweak: Implemented a piecal_popover_link_url filter for altering the view event link URL in the popover (#318)
* Fix: Corrected bug causing widget mode event indicator to display as a flat line on Safari (#284)
* Fix: Corrected issue that caused all day, multi-day events to span the wrong number of days in some timezones (#319)

= 1.2.1 =
* A11y: Removed # from aria-labelledby attribute on td elements (#286)
* Fix: Corrected issue that caused some events not to be output if the timezone was set to UTC (#282)
* Fix: view="listDay" now works as expected in the shortcode attributes (#300)
* Fix: Corrected some code dealing with the calendar footer output (#297)
* Fix: Added $atts argument to piecal_calendar_object_properties filter (#291)
* Fix: Corrected circle indicator style in widget mode when suing theme="dark" or adaptive dark mode (#273)

= 1.2.0 =
* New: Added new "widget" parameter for [piecal] shortcode, usage examples: [piecal widget=true] or [piecal widget=responsive]. Widget mode is an improved layout for the calendar grid on small devices or sidebars. Learn more at docs.piecalendar.com
* Fix: Corrected issue that caused json_decode() to fail in some environments, impacting recurring event display
* Fix: piecal-info now works with custom field filters (e.g. using ACF fields as start/end dates for your events)
* Tweak: Added the piecal_calendar_object_properties filter to allow the addition of multiple calendar properties
* A11y: Event links now announce that they're clickable (via role="button")
* A11y: Added role="dialog" to the calendar popover to ensure focus gets trapped for screen readers
* Tweak: Added .piecal-popover__view-link class to the "view" link wrapper in the popover
* Fix: Corrected issue that caused multi-day all-day events to span the wrong number of days
* Tweak: Events with no explicit end date now get an end date 1 hour from their start date by default. You can disable this with the automaticEndDates=false shortcode parameter
* Fix: Standardize start/end dates using format('Y-m-d\TH:i:s) before use in the calendar
* Tweak: [piecal-info] now outputs with .piecal-info class on the wrapper div, to make it easier to target with CSS
* Fix: Corrected issue that caused invalid time error for all day events that have no end time
* Fix: Corrected errors that occurred in the Block Editor when upgrading from Pie Calendar free to pro

= 1.1.1 =
* New: Remove prepend text in [piecal-info] shortcode. More info: https://docs.piecalendar.com/article/26-hide-prepend-text-from-piecal-info
* New: Allow event titles to wrap inside the day cell. Configurable with a shortcode parameter: [piecal wraptitles='true']
* New: Added filters to control what post types should show Pie Calendar controls. More info: https://docs.piecalendar.com/article/25-show-pie-calendar-meta-box-only-on-specific-post-types
* New: Added a new filter "piecal_after_popover_link" to insert content after the "View Post" link in popover
* Update: Reverted minimum PHP version to 7.4. 
* Fix: Added better logic to detect when certain post types use 'supports: editor' but not Gutenberg.
* Fix: Adjusted utm parameters in Pro link on plugins page

= 1.1 =
* New: It is now possible to use custom meta fields as Pie Calendar's date and time source. Learn more in our developer docs.
* Fix: Properly reset post data, which could cause issues with dynamic data element rendering properly. 
* Fix: Front-end calendar now properly reflects the date format selected in WordPress settings.
* Fix: Resolved an undefined array key issue related to custom meta fields. 

= 1.0.3 =
* New: Numerous new filters and hooks (see documentation for more info).
* New: It is now possible to alter the date format in the event popover via hooks and filters. 
* Update: Bumped minimum PHP requirement to 8.0
* Update: [piecal-info] shortcode now has better localization support via WordPress settings or via locale="" shortcode attribute
* Fix: All day events now span the correct number of days on the front end calendar view, rather than ending 1 day short.
* Fix: Strings inside popover are now translatable.
* Fix: Events query now returns all events, instead of relying on WordPress "posts to show at most" setting.
* Fix: Events can no longer be dragged and dropped on front-end calendar.
* Fix: Under the hood tweaks for bug fixes and compatibility.

= 1.0.2 =
* Better support for LocoTranslate
* Better support for adapting to WordPress locale based on chosen language
* Added additional shortcode attribute for dark mode: [piecal theme="dark"]
* Added additional shortcode attribute for auto-dark mode support via prefers-color-scheme: [piecal theme="adaptive"]
* Added new shortcode to display Pie Calendar times on a single post: [piecal-info]

= 1.0.1 =
* Added RTL support via is_rtl() function
* Added support for locale in shortcode (sets first day and translate portions of calendar text)
* Added Classic Editor support
* Prevent background scroll on popover
* Fixed scrollbar appearing inside calendar

= 1.0 =
* Initial release
