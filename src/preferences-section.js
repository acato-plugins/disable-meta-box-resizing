import { ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createPortal, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { useMetaBoxResizing } from './use-meta-box-resizing';

const MODAL_NAME = 'editor/preferences';

/**
 * The Appearance tab panel of the preferences modal.
 *
 * Core builds its tab panel ids as `<instance>-<tabName>-view` and only renders
 * the panel of the tab that is currently selected.
 */
const APPEARANCE_PANEL_SELECTOR = '.preferences-modal [id$="-appearance-view"]';

/**
 * Provide a host element at the end of the Appearance tab panel.
 *
 * Core offers no slot to extend the modal, so the section is portalled into a
 * host element of our own. Core renders its sections into the same panel and
 * decides when, so the host is moved back to the end whenever that happens,
 * keeping our section below core's rather than above it.
 *
 * Returns null whenever there is nothing to portal into, which keeps the plugin
 * harmless if core ever changes this markup.
 *
 * @return {HTMLElement|null} The element to render into.
 */
const useAppearanceTabHost = () => {
	const isModalOpen = useSelect(
		( select ) =>
			!! select( 'core/interface' )?.isModalActive( MODAL_NAME ),
		[]
	);
	const [ host, setHost ] = useState( null );

	useEffect( () => {
		if ( ! isModalOpen ) {
			setHost( null );
			return;
		}

		const element = document.createElement( 'div' );

		const attach = () => {
			const panel = document.querySelector( APPEARANCE_PANEL_SELECTOR );

			if ( ! panel ) {
				element.remove();
				setHost( null );
				return;
			}

			// Appending an element that is already last is a no-op, so the
			// observer this triggers settles on the next pass.
			if ( panel.lastElementChild !== element ) {
				panel.append( element );
				setHost( element );
			}
		};

		attach();

		const observer = new window.MutationObserver( attach );
		observer.observe( document.body, { childList: true, subtree: true } );

		return () => {
			observer.disconnect();
			element.remove();
		};
	}, [ isModalOpen ] );

	return host;
};

/**
 * Adds a "Meta boxes" section with the resizing preference to the editor
 * preferences modal.
 *
 * @return {Element|null} The portalled section, or null when the modal is closed.
 */
const PreferencesSection = () => {
	const host = useAppearanceTabHost();
	const { isDisabled, isSaving, toggle } = useMetaBoxResizing();

	if ( ! host ) {
		return null;
	}

	return createPortal(
		<fieldset className="preferences-modal__section">
			<legend className="preferences-modal__section-legend">
				<h2 className="preferences-modal__section-title">
					{ __( 'Meta boxes', 'disable-meta-box-resizing' ) }
				</h2>
			</legend>
			<div className="preferences-modal__section-content">
				<ToggleControl
					__nextHasNoMarginBottom
					checked={ isDisabled }
					disabled={ isSaving }
					label={ __(
						'Disable meta box resizing',
						'disable-meta-box-resizing'
					) }
					help={ __(
						'Hides the drag handle at the bottom of the editor and keeps the meta box panel expanded.',
						'disable-meta-box-resizing'
					) }
					onChange={ toggle }
				/>
			</div>
		</fieldset>,
		host
	);
};

export default PreferencesSection;
