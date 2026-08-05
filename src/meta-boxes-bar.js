import {
	createPortal,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp, Icon } from '@wordpress/icons';

import { useMetaBoxResizing } from './use-meta-box-resizing';

/**
 * The editor's scroll container, and the meta box pane core renders inside it.
 *
 * The pane is a direct child of the scroller, so its parent doubles as the
 * element to portal into and the element to scroll.
 */
const SCROLLER_SELECTOR = '.interface-interface-skeleton__content';
const PANE_SELECTOR = '.edit-post-meta-boxes-main';

/**
 * Scrolling behaviour that honours the visitor's motion preference.
 *
 * @return {string} Either `smooth` or `auto`.
 */
const scrollBehavior = () =>
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		? 'auto'
		: 'smooth';

/**
 * The meta box pane, for as long as core renders one.
 *
 * Core drops the pane entirely when the last meta box is hidden from the
 * Options menu and brings it back when one is shown again, so the pane is
 * tracked rather than looked up once. Returns null whenever there is nothing to
 * point at, which keeps the plugin harmless if core changes this markup.
 *
 * @param {boolean} isActive Whether the preference is currently enabled.
 *
 * @return {HTMLElement|null} The meta box pane.
 */
const useMetaBoxPane = ( isActive ) => {
	const [ pane, setPane ] = useState( null );

	useEffect( () => {
		if ( ! isActive ) {
			setPane( null );
			return;
		}

		const scroller = document.querySelector( SCROLLER_SELECTOR );

		if ( ! scroller ) {
			return;
		}

		const sync = () => setPane( scroller.querySelector( PANE_SELECTOR ) );

		sync();

		const observer = new window.MutationObserver( sync );
		observer.observe( scroller, { childList: true } );

		return () => observer.disconnect();
	}, [ isActive ] );

	return pane;
};

/**
 * Whether the editor has been scrolled away from the canvas.
 *
 * With the preference on, the canvas is exactly one screen minus the bar, so
 * any scroll offset at all means the meta boxes are on their way in.
 *
 * @param {HTMLElement|null} scroller The editor's scroll container.
 *
 * @return {boolean} True once the container is scrolled.
 */
const useIsScrolled = ( scroller ) => {
	const [ isScrolled, setIsScrolled ] = useState( false );

	useEffect( () => {
		if ( ! scroller ) {
			setIsScrolled( false );
			return;
		}

		const sync = () => setIsScrolled( scroller.scrollTop > 0 );

		sync();

		scroller.addEventListener( 'scroll', sync, { passive: true } );

		return () => scroller.removeEventListener( 'scroll', sync );
	}, [ scroller ] );

	return isScrolled;
};

/**
 * A bar at the bottom of the editor that leads to the meta boxes.
 *
 * With resizing disabled the meta boxes sit below a full height canvas, which
 * leaves them off screen behind a scrollbar of their own and easy to miss. This
 * bar sticks to the bottom of the screen for as long as they are out of view,
 * says they are there and jumps to them, and only scrolls away once the reader
 * has passed it.
 *
 * @return {Element|null} The portalled bar, or null when the preference is off.
 */
const MetaBoxesBar = () => {
	const { isDisabled } = useMetaBoxResizing();
	const pane = useMetaBoxPane( isDisabled );
	const scroller = pane?.parentElement ?? null;
	const isRevealed = useIsScrolled( scroller );

	const scrollToMetaBoxes = useCallback( () => {
		const behavior = scrollBehavior();

		if ( isRevealed ) {
			scroller.scrollTo( { top: 0, behavior } );
			return;
		}

		// Focus before scrolling: it makes the meta boxes the next stop for the
		// keyboard and lets a screen reader announce where the button led, and
		// `preventScroll` leaves the smooth scroll below to do the moving.
		pane.focus( { preventScroll: true } );
		pane.scrollIntoView( { behavior, block: 'start' } );
	}, [ isRevealed, pane, scroller ] );

	if ( ! pane ) {
		return null;
	}

	return createPortal(
		<button
			type="button"
			className="dmbr-meta-boxes-bar"
			onClick={ scrollToMetaBoxes }
		>
			{ isRevealed
				? __( 'Back to the content', 'disable-meta-box-resizing' )
				: __( 'Meta boxes', 'disable-meta-box-resizing' ) }
			<Icon icon={ isRevealed ? chevronUp : chevronDown } />
		</button>,
		scroller
	);
};

export default MetaBoxesBar;
