let piecalJS = (function() {
    const { __ } = wp.i18n;
    
    function init( options ) {
        if( typeof options !== 'object' ) {
            throw new Error('Options must be an object');
        }

        if( !options.mode || !getAllowedModes().includes(options.mode) ) {
            throw new Error('Mode must be one of: ' + getAllowedModes().join(', '));
        }

        if( !options.container ) {
            throw new Error('Container must be provided');
        }

        window.calendarInstances = window.calendarInstances || [];

        let containers = document.querySelectorAll('[data-piecal-instance]');
        let instance = -1;

        for( let container of containers ) {
            instance++;
            container.dataset.piecalInstance = instance;

        let calendar = new FullCalendar.Calendar(container, {
            headerToolbar: false,
            initialView: getModeInitialView(options.mode),
            editable: false,
            locale: options.locale,
            contentHeight: 'auto',
            events: options.events,
            views: {
                listMonth: {
                    duration: {
                        days: options.lookahead || 30
                    }
                }
            },
            eventClick: options.eventClick
        });

        calendar.render();

        window.calendarInstances.push(calendar);
        }
    }

    function getAllowedModes() {
        return ['recurrence-list', 'full'];
    }

    function getModeInitialView( mode ) {
        let modeViewMap = {
            'recurrence-list': 'listMonth',
            'full': 'dayGridMonth'
        };

        return modeViewMap[mode];
    }

    function validateOptions( options ) {
        let validOptions = {
            calendarIndex: 'int',
            appendOffset: 'boolean',
            useAdaptiveTimezones: 'boolean'
        };
    }

    function eventClick( info, options = {} ) {
        Alpine.store("calendarEngine").eventTitle = info.event._def.title;
        Alpine.store("calendarEngine").eventStart = info.event.start;
        Alpine.store("calendarEngine").eventEnd = info.event.end;
        Alpine.store("calendarEngine").eventDetails = info.event._def.extendedProps.details ?? '';
        Alpine.store("calendarEngine").eventUrl = info.event._def.extendedProps.permalink;
        Alpine.store("calendarEngine").eventAllDay = info.event.allDay;
        Alpine.store("calendarEngine").eventType = info.event._def.extendedProps.postType;
        Alpine.store('calendarEngine').showPopover = true;
        Alpine.store('calendarEngine').eventActualEnd = info.event._def.extendedProps.actualEnd;
        Alpine.store('calendarEngine').appendOffset = options.appendOffset;

        // Always pass through event data via the URL if it's a recurring instance, or if adaptive timezones are enabled.
        // Do not pass through event data via the URL if it's a non-recurring instance and adaptive timezones are disabled.
        if( info.event._def.extendedProps.isRecurringInstance || 
            ( !info.event._def.extendedProps.isRecurringInstance && piecalVars.useAdaptiveTimezones && Alpine.store('calendarEngine').appendOffset ) &&
            info.event._def.extendedProps.permalink ) {

            // Construct the URL with parameters
            const baseUrl    = info.event._def.extendedProps.permalink;
            const eventStart = new Date( info.event.start );
            const eventEnd   = new Date( info.event.end );
            const viewerTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;


            const url        = new URL( baseUrl );
            url.searchParams.append( 'eventstart', Math.floor( eventStart.getTime() / 1000 ) );
            url.searchParams.append( 'eventend', Math.floor( eventEnd.getTime() / 1000 ) );
            url.searchParams.append( 'timezone', viewerTimezone );

            // Assign the constructed URL to the store
            Alpine.store("calendarEngine").eventUrl = url.toString();
        }

        if( info.jsEvent.type == "keydown" ) {
            setTimeout( () => {
                document.querySelector('.piecal-popover__inner > button').focus();
            }, 100);
        }

        return info;
    }

    function eventDidMount( info, options = {} ) {
        let link = info.el;

        const locale = info.view.dateEnv.locale.codeArg;

        const formattedTime = new Intl.DateTimeFormat(locale, {
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });

        const formattedDate = new Intl.DateTimeFormat(locale, {
            day: 'numeric',
            month: 'numeric',
            year: 'numeric'
        });

        if( link.tagName == 'TR' ) {
            link = info.el.querySelector('a');
        }

        if( !link || link.tagName != "A" ) return;

        link.setAttribute('role', 'button');
        link.setAttribute('href', 'javascript:void(0)');

        if( info.event.allDay ) {
            const allDayDescriptionText = __('All-day event', 'piecal');

            link.setAttribute('aria-label', `${allDayDescriptionText} - ${info.event.title}`);
        }

        // Handle multi-day event aria label to let screen readers know the event spans multiple days
        if( info.event.end && (info.event.end - info.event.start) > (24 * 60 * 60 * 1000) ) {
            const startDate = formattedDate.format(info.event.start);
            const startTime = info.event.allDay ? '' : formattedTime.format(info.event.start);

            const endDate = formattedDate.format(info.event.end);
            const endTime = info.event.allDay ? '' :formattedTime.format(info.event.end);

            /* Translators: Text describing span of multi-day event. */
            const spanText = __('to', 'piecal');

            /* Translators: Text for multi-day event description. */
            const multiDayDescriptionText = __('Multi-day event running from', 'piecal'); // <?php _e("'Multi-day event running from'", 'piecal'); ?>;

            /* Translators: Text for multi-day all-day event description. */
            const multiDayAllDayDescriptionText = __('Multi-day, all-day event running from', 'piecal'); // <?php _e("'Multi-day, all-day event running from'", 'piecal'); ?>;

            const descriptionText = info.event.allDay ? multiDayAllDayDescriptionText : multiDayDescriptionText;

            /* Translators: Text describing span of multi-day event. */
            link.setAttribute('aria-label', `${descriptionText} ${startDate} ${startTime} ${spanText} ${endDate} ${endTime} - ${info.event.title}`);
        }

        return info;
    }

    function dayCellDidMount( info, options = {} ) {
        let dayLink = info.el.querySelector('.fc-daygrid-day-top a');

        if( !dayLink ) return;

        dayLink.setAttribute('role', 'button');
        dayLink.setAttribute('href', 'javascript:void(0)');

        // Prevent double read out of button label
        dayLink.closest('td').removeAttribute('aria-labelledby');
        
        setTimeout( () => {
            if( info.el.querySelector('.fc-daygrid-day-events .fc-daygrid-event-harness') ) {
                dayLink.setAttribute('aria-label', dayLink.getAttribute('aria-label') + ', has events.');
            }
        }, 100);

        dayLink.addEventListener('keydown', (event) => {
            if( event.key == "Enter" || event.key == ' ' ) {
                event.preventDefault();
                window.calendar.gotoDate(info.date);
                piecalChangeView('listDay');

                setTimeout( () => {
                    let focusTarget = document.querySelector('.fc-list-day-text');
                    focusTarget?.setAttribute('tabindex', '0');
                    focusTarget?.focus();
                }, 100);
            }
        })

        return info;
    }

    function eventDataTransform( event, options = {} ) {
         // Safely decode encoded HTML entities for output as titles
         let scrubber = document.createElement('textarea');
         scrubber.innerHTML = event.title;
         event.title = scrubber.value;

         // Extend end date for all day events that span multiple days
         let { actualEnd, end } = piecalUtils.getAlldayMultidayEventEnd( event ) ?? {};

         if( actualEnd && end ) {    
             event.actualEnd = actualEnd;
             event.end = end;
         }

         return event;
    }

    function dateClick( info, options = {} ) {
        if( info.jsEvent.target.tagName != 'A' ) return;

        if( !options.calendarIndex ) {
            window.calendar.gotoDate(info.dateStr);
        } else {
            window.calendarInstances[options.calendarIndex].gotoDate(info.dateStr);
        }

        piecalChangeView('listDay');

        return info;
    }

    function dayHeaderContent( info, options = {} ) {
        let overriddenDayHeaderViews = ['dayGridMonth', 'timeGridWeek', 'dayGridWeek'];

        if( overriddenDayHeaderViews.includes(info.view.type) ) {
            return '';
        }

        return info;
    }

    function dayHeaderDidMount( info, options = {} ) {
        let dayHeaderLink = info.el.querySelector('a');

        if( !dayHeaderLink ) return;

        let fullDayName = piecalUtils.getLocalizedDayNames(info.text, 'full', options.locale );
        let shortDayName = piecalUtils.getLocalizedDayNames(info.text, 'short', options.locale );
        let singleLetterDayName = piecalUtils.getLocalizedDayNames(info.text, 'single', options.locale );

        let shortenableViews = ['dayGridMonth', 'timeGridWeek', 'dayGridWeek', 'listWeek'];
        let viewsWithDates = ['timeGridWeek', 'listWeek', 'dayGridWeek'];

        if( shortenableViews.includes(info.view.type) ) {
            if( viewsWithDates.includes(info.view.type) && options.showDates != false ) {
                // Format the date from info.date
                const dateFormatter = new Intl.DateTimeFormat(info.view.dateEnv.locale.codeArg, {
                    month: 'numeric',
                    day: 'numeric'
                });
                const formattedDate = dateFormatter.format(info.date);

                dayHeaderLink.innerHTML = `<span class="piecal-grid-day-header-text piecal-grid-day-header-text--full">${fullDayName} ${formattedDate}</span>
                                           <span class="piecal-grid-day-header-text piecal-grid-day-header-text--short">${shortDayName} ${formattedDate}</span>
                                           <span class="piecal-grid-day-header-text piecal-grid-day-header-text--single-letter">${singleLetterDayName} ${formattedDate}</span>`;
            } else {
                dayHeaderLink.innerHTML = `<span class="piecal-grid-day-header-text piecal-grid-day-header-text--full">${fullDayName}</span>
                                           <span class="piecal-grid-day-header-text piecal-grid-day-header-text--short">${shortDayName}</span>
                                           <span class="piecal-grid-day-header-text piecal-grid-day-header-text--single-letter">${singleLetterDayName}</span>`;
            }
        }

        return info;
    }

    // Return methods that should be accessible when invoking piecalJS.
    return {init, eventDidMount, dayCellDidMount, eventClick, eventDataTransform, dateClick, dayHeaderContent, dayHeaderDidMount};
})();