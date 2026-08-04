import { PluginMoreMenuItem } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';

import { useMetaBoxResizing } from './use-meta-box-resizing';

/**
 * Adds the preference to the editor's Options menu, so it can be flipped while
 * editing without opening the preferences modal.
 *
 * @return {Element} The menu item.
 */
const MoreMenuItem = () => {
	const { isDisabled, isSaving, toggle } = useMetaBoxResizing();

	return (
		<PluginMoreMenuItem
			icon={ isDisabled ? check : undefined }
			isSelected={ isDisabled }
			role="menuitemcheckbox"
			disabled={ isSaving }
			onClick={ () => toggle( ! isDisabled ) }
		>
			{ __( 'Disable meta box resizing', 'disable-meta-box-resizing' ) }
		</PluginMoreMenuItem>
	);
};

export default MoreMenuItem;
