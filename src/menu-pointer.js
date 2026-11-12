import './menu-pointer.scss';

/**
 * The card beside the Settings menu that leads to the walkthrough.
 *
 * Plain JavaScript without jQuery, as it loads on every admin screen until the
 * walkthrough is taken. The data comes from Tour::menu_pointer() in
 * window.dmbrMenuPointer.
 */
const data = window.dmbrMenuPointer;
const target = document.getElementById( 'menu-settings' );

/**
 * Create an element with a class and optional text.
 *
 * @param {string} tag       Tag name.
 * @param {string} className Class name.
 * @param {string} text      Text content, or an empty string.
 *
 * @return {HTMLElement} The element.
 */
const el = ( tag, className, text = '' ) => {
	const node = document.createElement( tag );

	node.className = className;

	if ( text ) {
		node.textContent = text;
	}

	return node;
};

if ( data && target ) {
	const rtl = document.documentElement.dir === 'rtl';
	const box = el( 'div', 'dmbr-menu-pointer' );
	const body = el( 'div', 'dmbr-menu-pointer-body' );
	const head = el( 'div', 'dmbr-menu-pointer-head' );
	const wapuu = el( 'img', 'dmbr-menu-pointer-wapuu' );
	const title = el( 'h2', 'dmbr-menu-pointer-title', data.title );
	const text = el( 'p', 'dmbr-menu-pointer-text', data.text );
	const actions = el( 'div', 'dmbr-menu-pointer-actions' );
	const go = el( 'a', 'dmbr-menu-pointer-go', data.linkText );
	const dismiss = el( 'button', 'dmbr-menu-pointer-dismiss', data.dismiss );

	title.id = 'dmbr-menu-pointer-title';
	text.id = 'dmbr-menu-pointer-text';
	box.setAttribute( 'role', 'dialog' );
	box.setAttribute( 'aria-labelledby', title.id );
	box.setAttribute( 'aria-describedby', text.id );
	wapuu.src = data.wapuu;
	wapuu.alt = '';
	wapuu.width = 44;
	wapuu.height = 44;
	go.href = data.link;
	dismiss.type = 'button';

	head.append( wapuu, title );
	actions.append( go, dismiss );
	body.append( head, text, actions );
	box.append( body );

	// The admin menu is pinned when it fits the window and scrolls with the
	// page when it does not. The card does what the menu does, so it moves
	// with the Settings item by itself: fixed beside a pinned menu, in page
	// coordinates beside one that scrolls. It opens whole inside the window,
	// under the admin bar; the arrow slides along its edge to keep pointing
	// at the item.
	const menu = document.getElementById( 'adminmenuwrap' );

	const place = () => {
		const pinned =
			!! menu && window.getComputedStyle( menu ).position === 'fixed';
		const offset = pinned ? 0 : window.scrollY;
		const rect = target.getBoundingClientRect();
		const middle = rect.top + rect.height / 2;
		const height = box.offsetHeight;
		const lowest = Math.max( 40, window.innerHeight - height - 16 );
		const top = Math.min( Math.max( 40, middle - 39 ), lowest );

		box.style.position = pinned ? 'fixed' : 'absolute';
		box.style.top = `${ top + offset }px`;
		box.style.setProperty(
			'--dmbr-arrow',
			`${ Math.min( Math.max( middle - top - 7, 12 ), height - 26 ) }px`
		);

		if ( rtl ) {
			box.style.right = `${
				document.documentElement.clientWidth - rect.left + 12
			}px`;
		} else {
			box.style.left = `${
				rect.right + ( pinned ? 0 : window.scrollX ) + 12
			}px`;
		}
	};

	// "Not now" is core's own dismissal of a pointer, per user and per
	// walkthrough version, so a newer walkthrough brings the card back.
	const close = () => {
		box.remove();
		window.removeEventListener( 'resize', place );
		document.removeEventListener( 'keydown', onKey );

		const form = new window.FormData();

		form.append( 'action', 'dismiss-wp-pointer' );
		form.append( 'pointer', data.name );
		window
			.fetch( data.ajaxUrl, {
				method: 'POST',
				body: form,
				credentials: 'same-origin',
			} )
			.catch( () => {} );
	};

	const onKey = ( event ) => {
		if (
			event.key === 'Escape' &&
			box.contains( event.target.ownerDocument.activeElement )
		) {
			close();
		}
	};

	dismiss.addEventListener( 'click', close );

	// Other plugins point at the admin menu too: wait until theirs are
	// closed, rather than stack on top of them.
	const open = () => {
		const others = [ ...document.querySelectorAll( '.wp-pointer' ) ].some(
			( pointer ) => pointer.getClientRects().length > 0
		);

		if ( others ) {
			window.setTimeout( open, 1000 );
			return;
		}

		document.body.append( box );
		place();

		// WordPress pins and unpins the menu as the window is resized.
		window.addEventListener( 'resize', place );
		document.addEventListener( 'keydown', onKey );
	};

	window.setTimeout( open, 500 );
}
