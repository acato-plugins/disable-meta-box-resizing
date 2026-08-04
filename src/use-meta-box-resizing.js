import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

const BODY_CLASS = 'dmbr-meta-box-resizing-disabled';
const META_KEY = 'disable_meta_box_resizing';

/**
 * Whether resizing is currently disabled, according to the body class.
 *
 * @return {boolean} True when the meta box panel is not resizable.
 */
const isResizingDisabled = () => document.body.classList.contains( BODY_CLASS );

/**
 * Read and write the current user's meta box resizing preference.
 *
 * The body class is the single source of truth: the stylesheet keys off it, PHP
 * sets it on page load, and every consumer of this hook observes it. That keeps
 * the preferences section and the Options menu item in step without a store of
 * their own.
 *
 * @return {{isDisabled: boolean, isSaving: boolean, toggle: Function}} Current state and setter.
 */
export const useMetaBoxResizing = () => {
	const [ isDisabled, setIsDisabled ] = useState( isResizingDisabled );
	const [ isSaving, setIsSaving ] = useState( false );
	const { createErrorNotice } = useDispatch( noticesStore );

	useEffect( () => {
		const sync = () => setIsDisabled( isResizingDisabled() );

		const observer = new window.MutationObserver( sync );
		observer.observe( document.body, {
			attributeFilter: [ 'class' ],
			attributes: true,
		} );

		return () => observer.disconnect();
	}, [] );

	const toggle = useCallback(
		async ( value ) => {
			document.body.classList.toggle( BODY_CLASS, value );
			setIsSaving( true );

			try {
				await apiFetch( {
					path: '/wp/v2/users/me',
					method: 'POST',
					data: { meta: { [ META_KEY ]: value } },
				} );
			} catch ( error ) {
				document.body.classList.toggle( BODY_CLASS, ! value );
				createErrorNotice(
					error?.message ||
						__(
							'Could not save the meta box preference.',
							'disable-meta-box-resizing'
						),
					{ type: 'snackbar' }
				);
			} finally {
				setIsSaving( false );
			}
		},
		[ createErrorNotice ]
	);

	return { isDisabled, isSaving, toggle };
};
