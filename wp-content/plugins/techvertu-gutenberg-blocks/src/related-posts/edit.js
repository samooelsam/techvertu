import { __ } from '@wordpress/i18n';

import ServerSideRender from '@wordpress/server-side-render';

import { PluginBlockSettingsMenuItem } from '@wordpress/editor';

import {
	Disabled,
	TextControl,
	ToggleControl,
	PanelBody,
	PanelRow,
	QueryControls,
	SelectControl 
} from '@wordpress/components';

import metadata from './block.json';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'wz-dynamic-block',
	} );

	const { postsToShow, showHeading, heading, order, orderBy } = attributes;
	const [ size, setSize ] = useState( '50%' );
	const onChangeHeading = ( newHeading ) => {
		setAttributes( { heading: newHeading } );
	};

	const toggleHeading = () => {
		setAttributes( { showHeading: ! showHeading } );
	};

	return (
		<PluginBlockSettingsMenuItem
        allowedBlocks={ [ 'core/paragraph' ] }
        icon="dashicon-name"
        label={ __( 'Menu item text' ) }
        onClick={ doOnClick }
    />
	);
}
