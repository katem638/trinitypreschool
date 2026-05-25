const { __, getLocaleData } = wp.i18n;
const { PluginDocumentSettingPanel } = wp.editPost;
const { compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { ToggleControl, DateTimePicker, PanelRow, Button, Dropdown, TextControl } = wp.components;
const localeCode = getLocaleData()[""].lang ?? 'en-us';
 
const Piecal_Gutenberg_Sidebar_Plugin = ( { postType, postMeta, setPostMeta } ) => {

	// EDD downloads & WooCo products are not supported by default
	if( ( piecalGbVars.isWooActive || piecalGbVars.isEddActive ) && ( postType == "product" || postType == "download" ) ) return null;
	if( piecalGbVars.explicitAllowedPostTypes && piecalGbVars.explicitAllowedPostTypes.length > 0 && !piecalGbVars.explicitAllowedPostTypes.includes( postType ) ) return null;
	if( piecalGbVars.hidePiecalControls ) return null;

	// Get strings from piecalGbVars
	const strings = piecalGbVars.strings || {};

	return(
		<PluginDocumentSettingPanel title={ strings.Calendar || 'Calendar' } initialOpen="true">
			<PanelRow>
				<ToggleControl
					label={ strings.Show_On_Calendar || 'Show On Calendar' }
					onChange={ ( value ) => setPostMeta( { _piecal_is_event: value } ) }
					checked={ postMeta._piecal_is_event }
				/>
			</PanelRow>
			<PanelRow>
				{
					postMeta._piecal_is_event &&
						<ToggleControl
						label={ strings.All_Day_Event || 'All Day Event' }
						onChange={ ( value ) => setPostMeta( { _piecal_is_allday: value } ) }
						checked={ postMeta._piecal_is_allday }
						/>
				}
			</PanelRow>
			{
				postMeta._piecal_is_event &&
				<PanelRow>
					<Dropdown
					className="piecal-gb-dropdown-container"
					contentClassName="piecal-gb-dropdown-content"
					position="bottom right"
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							variant="primary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
						>
							{ ( postMeta._piecal_start_date == '' || postMeta._piecal_start_date == null ) ? 
								<span>
									<span class="dashicons dashicons-calendar"></span>
									&nbsp; { strings.Start_Date || 'Start Date' }
								</span>
							   : <span>
									<span class="dashicons dashicons-yes"></span>
									&nbsp; { strings.Start_Date || 'Start Date' }
								</span>
							}
						</Button>
					) }
					renderContent={ () => 
						<div>
							<DateTimePicker
								currentDate={ postMeta._piecal_start_date }
								label={ strings.Start_Date || 'Start Date' }
								value={ postMeta._piecal_start_date }
								onChange={ ( value ) => setPostMeta( { _piecal_start_date: value } ) }
								is12Hour={ wp.date.getSettings().formats.time.toLowerCase().indexOf( 'a' ) !== -1 }
							/>
							<PanelRow>
								<Button
								variant="link"
								className="piecal-clear-date-button"
								isDestructive="true"
								onClick={ ( value ) => setPostMeta( { _piecal_start_date: null } ) }
								>
								{ strings.Clear || 'Clear' }
								</Button>
							</PanelRow>
						</div>
					}
					/>
					<Dropdown
					className="piecal-gb-dropdown-container"
					contentClassName="piecal-gb-dropdown-content"
					position="bottom right"
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							variant="primary"
							onClick={ onToggle }
							aria-expanded={ isOpen }
						>
							{ ( postMeta._piecal_end_date == '' || postMeta._piecal_end_date == null ) ? 
								<span>
									<span class="dashicons dashicons-calendar"></span>
									&nbsp; { strings.End_Date || 'End Date' }
								</span>
							   : <span>
									<span class="dashicons dashicons-yes"></span>
									&nbsp; { strings.End_Date || 'End Date' }
								</span>
							}
						</Button>
					) }
					renderContent={ () => 
						<div>
							<DateTimePicker
								currentDate={ postMeta._piecal_end_date != '' ? postMeta._piecal_end_date : postMeta._piecal_start_date }
								label={ strings.End_Date || 'End Date' }
								value={ postMeta._piecal_end_date }
								onChange={ ( value ) => setPostMeta( { _piecal_end_date: value } ) }
								is12Hour={ wp.date.getSettings().formats.time.toLowerCase().indexOf( 'a' ) !== -1 }
							/>
							<PanelRow>
								<Button
									variant="link"
									className="piecal-clear-date-button"
									isDestructive="true"
									onClick={ ( value ) => setPostMeta( { _piecal_end_date: null } ) }
								>
							    { strings.Clear || 'Clear' }
								</Button>
							</PanelRow>
						</div>
					}
					/>
				</PanelRow>
			}
			{
				( postMeta._piecal_start_date != '' && postMeta._piecal_start_date != null ) &&
				<PanelRow>
					<p>{ (strings.Starts_on_ || 'Starts on ') + new Date(postMeta._piecal_start_date).toLocaleDateString(localeCode.replace('_', '-'), { weekday:"long", year:"numeric", month:"short", day:"numeric"}) }</p>
				</PanelRow>
			}
			{
				( postMeta._piecal_end_date != '' && postMeta._piecal_end_date != null ) &&
				<PanelRow>
					<p>{ (strings.Ends_on_ || 'Ends on ') + new Date(postMeta._piecal_end_date).toLocaleDateString(localeCode.replace('_', '-'), { weekday:"long", year:"numeric", month:"short", day:"numeric"}) }</p>
				</PanelRow>
			}
		</PluginDocumentSettingPanel>
	);
}
 
export default compose( [
	withSelect( ( select ) => {		
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			}
		};
	} )
] )( Piecal_Gutenberg_Sidebar_Plugin );