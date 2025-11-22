/* global wp, eewpBlockEditor */
( () => {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { CheckboxControl, Button } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { __ } = wp.i18n;
	const { createElement, Fragment } = wp.element;

	const settings = window.eewpBlockEditor || {};
	const postTypes = settings.postTypes || [];
	const enabledKey = settings.enabledKey || 'eewp_enabled';
	const rowsKey = settings.rowsKey || 'eewp_rows';
	const metaBoxId = settings.metaBoxId || 'eewp-meta-box';
	const strings = settings.strings || {};

	const Panel = () => {
		const { enabled, rows, postType } = useSelect(
			( select ) => {
				const meta = select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
				return {
					enabled: 'yes' === ( meta[ enabledKey ] || '' ),
					rows: Array.isArray( meta[ rowsKey ] ) ? meta[ rowsKey ] : [],
					postType: select( 'core/editor' ).getCurrentPostType(),
				};
			},
			[ enabledKey, rowsKey ]
		);

		const { editPost } = useDispatch( 'core/editor' );

		if ( ! postTypes.includes( postType ) ) {
			return null;
		}

		const toggleEnabled = ( value ) => {
			editPost( { meta: { [ enabledKey ]: value ? 'yes' : '' } } );
		};

		const scrollToMetaBox = () => {
			const metaBox = document.getElementById( metaBoxId );
			if ( metaBox && metaBox.scrollIntoView ) {
				metaBox.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}
		};

		return createElement(
			PluginDocumentSettingPanel,
			{
				name: 'eewp-lite-panel',
				title: strings.panelTitle || __( 'Easy English – Lite', 'easy-english-wp' ),
				className: 'eewp-panel',
			},
			createElement( CheckboxControl, {
				label: strings.enableLabel || __( 'Enable Easy English for this post/page', 'easy-english-wp' ),
				checked: !! enabled,
				onChange: toggleEnabled,
			} ),
			createElement(
				'p',
				{ className: 'eewp-panel__meta' },
				( strings.rowsLabel || __( 'Rows', 'easy-english-wp' ) ) + ': ' + ( rows.length || 0 )
			),
			createElement(
				'p',
				{ className: 'eewp-panel__meta eewp-panel__limit' },
				strings.limitNotice || __( 'Free version limit applies.', 'easy-english-wp' )
			),
			createElement(
				Button,
				{
					isSecondary: true,
					onClick: scrollToMetaBox,
				},
				strings.editRowsButton || __( 'Edit Easy English rows in meta box below', 'easy-english-wp' )
			)
		);
	};

	registerPlugin( 'eewp-lite-panel', {
		render: Panel,
		icon: null,
	} );
} )();
