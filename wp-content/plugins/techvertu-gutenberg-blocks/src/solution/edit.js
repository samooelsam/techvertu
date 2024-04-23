import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, PanelBody, PanelRow, TextareaControl  } from '@wordpress/components';
import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const bookTitle = meta[ '_techvertu_solution_title' ];
	const bookAuthor = meta[ '_techvertu_solution_content' ];
	const updateSolutionTitleMetaValue = ( newValue ) => {
		setMeta( { ...meta, _techvertu_solution_title: newValue } );
    };
	const updateSolutionContentMetaValue = ( newValue ) => {
		setMeta( { ...meta, _techvertu_solution_content: newValue } );
	};
	return (
		<>
			<InspectorControls>
				<PanelBody 
					title={ __( 'Solution details' )}
					initialOpen={true}
				>
					<PanelRow>
						<fieldset>
							<TextControl
								label={__( 'Solution title' )}
								value={ bookTitle }
								onChange={ updateSolutionTitleMetaValue }
							/>
						</fieldset>
					</PanelRow>
					<PanelRow>
						<fieldset>
							<TextareaControl
								label={ __( 'Solution content' ) }
								value={ bookAuthor }
								onChange={ updateSolutionContentMetaValue }
							/>
						</fieldset>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText 
					tagName="h3"
					onChange={ updateSolutionTitleMetaValue }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					value={ bookTitle }
					placeholder={ __( 'Write your text...' ) }
				/>
				<TextareaControl
					label="Solution content"
					value={ bookAuthor }
					onChange={ updateSolutionContentMetaValue }
				/>
			</div>
		</>
	);
}
