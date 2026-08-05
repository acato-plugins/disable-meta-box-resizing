import { registerPlugin } from '@wordpress/plugins';

import MetaBoxesBar from './meta-boxes-bar';
import MoreMenuItem from './more-menu-item';
import PreferencesSection from './preferences-section';
import './style.scss';

/**
 * Both places the preference can be changed from inside the editor, plus the
 * bar that points at the meta boxes while it is on.
 *
 * @return {Element} The registered fills.
 */
const DisableMetaBoxResizing = () => (
	<>
		<PreferencesSection />
		<MoreMenuItem />
		<MetaBoxesBar />
	</>
);

registerPlugin( 'disable-meta-box-resizing', {
	render: DisableMetaBoxResizing,
} );
