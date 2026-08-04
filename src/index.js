import { registerPlugin } from '@wordpress/plugins';

import MoreMenuItem from './more-menu-item';
import PreferencesSection from './preferences-section';
import './style.scss';

/**
 * Both places the preference can be changed from inside the editor.
 *
 * @return {Element} The registered fills.
 */
const DisableMetaBoxResizing = () => (
	<>
		<PreferencesSection />
		<MoreMenuItem />
	</>
);

registerPlugin( 'disable-meta-box-resizing', {
	render: DisableMetaBoxResizing,
} );
