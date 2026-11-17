import { speak } from '@wordpress/a11y';
import { _x, sprintf } from '@wordpress/i18n';

import './settings.scss';

/**
 * The walkthrough of the settings screen, on WordPress's own admin pointers.
 *
 * The steps come from the Tour class. It never starts on its own: it starts
 * from the "Start walkthrough" link on the Plugins screen, which opens this
 * screen with a query argument, or from the link at the bottom of the screen.
 * From the Plugins screen, somebody who took an older walkthrough gets only
 * the steps added since; the link at the bottom always gives all of it.
 */
const data = window.dmbrTour;
const $ = window.jQuery;

/**
 * The step's target, or null when it is not on this screen.
 *
 * @param {Object} step A step of the walkthrough.
 *
 * @return {Element|null} The element the step points at.
 */
const targetOf = ( step ) => document.querySelector( step.target );

/**
 * Steps that have something to point at here.
 *
 * @param {Object[]} steps Steps of the walkthrough.
 *
 * @return {Object[]} The steps whose target is on the screen.
 */
const present = ( steps ) => steps.filter( targetOf );

/**
 * Create an element with an optional class and text, never from an HTML
 * string, so nothing in a step can ever be read as markup.
 *
 * @param {string} tag       Tag name.
 * @param {string} className Class name, or an empty string.
 * @param {string} text      Text content, or an empty string.
 *
 * @return {HTMLElement} The element.
 */
const node = ( tag, className = '', text = '' ) => {
	const element = document.createElement( tag );

	if ( className ) {
		element.className = className;
	}

	if ( text ) {
		element.textContent = text;
	}

	return element;
};

/**
 * Store that this user completed or skipped the walkthrough. When that fails
 * nothing is lost: it simply shows again.
 *
 * @return {Promise} The request.
 */
const markSeen = () => {
	const body = new URLSearchParams( {
		action: data.action,
		_ajax_nonce: data.nonce,
	} );

	data.pending = [];

	return window
		.fetch( data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body,
		} )
		.catch( () => {} );
};

let running = false;

/**
 * Run the walkthrough. Escape or Skip ends it, and focus goes back where it was.
 *
 * @param {Object[]} steps Steps to show.
 *
 * @return {boolean} Whether it started.
 */
const run = ( steps ) => {
	if ( running || ! steps.length ) {
		return false;
	}

	running = true;

	const returnFocus = targetOf( steps[ 0 ] ).ownerDocument.activeElement;
	const reduced = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;
	let current = null;
	let ended = false;

	const reposition = () => current?.element.pointer( 'reposition' );

	const end = () => {
		if ( ended ) {
			return;
		}

		ended = true;
		running = false;
		current?.element.pointer( 'close' );
		document.removeEventListener( 'keydown', onKey, true );
		window.removeEventListener( 'resize', reposition );
		returnFocus?.focus?.();
		markSeen();
	};

	const onKey = ( event ) => {
		if ( event.key === 'Escape' ) {
			event.stopPropagation();
			end();
		}
	};

	const open = ( index ) => {
		const step = steps[ index ];
		const last = index + 1 === steps.length;
		const target = targetOf( step );

		// The pointer opens below its target, so the target goes to the upper
		// third of the window, leaving room for the pointer under it.
		window.scrollTo( {
			top: Math.max(
				0,
				target.getBoundingClientRect().top +
					window.scrollY -
					window.innerHeight / 3
			),
			behavior: reduced ? 'auto' : 'smooth',
		} );

		const eyebrow = sprintf(
			/* translators: 1: number of this step, 2: number of steps. */
			_x(
				'Step %1$d of %2$d',
				'walkthrough progress',
				'disable-meta-box-resizing'
			),
			index + 1,
			steps.length
		);
		const progress = Math.round( ( ( index + 1 ) / steps.length ) * 100 );

		const content = () => {
			const head = node( 'div', 'dmbr-pointer-head' );
			const bar = node( 'span', 'dmbr-pointer-progress' );
			const fill = node( 'span' );

			bar.setAttribute( 'aria-hidden', 'true' );
			fill.style.setProperty( '--dmbr-progress', `${ progress }%` );
			bar.append( fill );
			head.append(
				node( 'p', 'dmbr-pointer-eyebrow', eyebrow ),
				node( 'h3', '', step.title ),
				bar
			);

			return $( [ head, node( 'p', '', step.text ) ] );
		};

		$( target )
			.pointer( {
				pointerClass: 'wp-pointer dmbr-pointer',
				content,
				position: { edge: 'top', align: 'left' },
				pointerWidth: 340,
				buttons: ( event, pointer ) => {
					current = pointer;

					const wrap = node( 'div', 'dmbr-pointer-buttons' );

					if ( ! last ) {
						const skip = node(
							'button',
							'button-link',
							_x(
								'Skip walkthrough',
								'walkthrough button',
								'disable-meta-box-resizing'
							)
						);
						skip.type = 'button';
						skip.addEventListener( 'click', end );
						wrap.append( skip );
					}

					const next = node(
						'button',
						'button button-primary dmbr-pointer-next',
						last
							? _x(
									'Done',
									'walkthrough button',
									'disable-meta-box-resizing'
								)
							: _x(
									'Next',
									'walkthrough button',
									'disable-meta-box-resizing'
								)
					);
					next.type = 'button';
					next.addEventListener( 'click', () => {
						if ( last ) {
							end();
							return;
						}

						pointer.element.pointer( 'close' );
						open( index + 1 );
					} );
					wrap.append( next );

					// Core wraps what this returns with jQuery's .wrap().
					return $( wrap );
				},
			} )
			.pointer( 'open' );

		// The pointer is appended to <body>, far from its target in the
		// keyboard order, so focus is taken to it and its text read out.
		window.requestAnimationFrame( () => {
			[
				...document.querySelectorAll(
					'.dmbr-pointer .dmbr-pointer-next'
				),
			]
				.pop()
				?.focus();
			speak( `${ eyebrow }. ${ step.title }. ${ step.text }`, 'polite' );
		} );
	};

	document.addEventListener( 'keydown', onKey, true );
	window.addEventListener( 'resize', reposition );
	open( 0 );

	return true;
};

if ( data && $?.fn?.pointer ) {
	// The way back in, for somebody who skipped it or wants it again.
	const wrap = document.querySelector( '.dmbr-settings' );

	if ( wrap ) {
		const replay = node( 'p', 'dmbr-tour-replay' );
		const button = node(
			'button',
			'button-link',
			_x(
				'Take the walkthrough',
				'walkthrough button',
				'disable-meta-box-resizing'
			)
		);

		button.type = 'button';
		button.addEventListener( 'click', () => run( present( data.steps ) ) );
		replay.append( button );
		wrap.append( replay );
	}

	// Asked for from the Plugins screen. The argument comes off the address
	// first, so a reload or a saved form does not start it again.
	const url = new URL( window.location.href );

	if ( url.searchParams.has( data.start ) ) {
		url.searchParams.delete( data.start );
		window.history.replaceState( window.history.state, '', url );

		// Saving on a single site returns to the address the form was
		// rendered at, which still has the argument; take it off there too.
		const referer = document.querySelector(
			'.dmbr-settings input[name="_wp_http_referer"]'
		);

		if ( referer ) {
			const back = new URL( referer.value, window.location.origin );

			back.searchParams.delete( data.start );
			referer.value = back.pathname + back.search;
		}

		// What this user has not seen, or all of it when they saw everything.
		const steps = present( data.pending ).length
			? present( data.pending )
			: present( data.steps );

		// Once the screen has settled, so the first step points at its place.
		window.requestAnimationFrame( () => run( steps ) );
	}
}
