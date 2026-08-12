( function ( wp, config ) {
	'use strict';

	if ( ! wp || ! config ) {
		return;
	}

	const el = wp.element.createElement;
	const registerPlugin = wp.plugins.registerPlugin;
	const useSelect = wp.data.useSelect;
	const useDispatch = wp.data.useDispatch;
	const TextControl = wp.components.TextControl;
	const TextareaControl = wp.components.TextareaControl;
	const ToggleControl = wp.components.ToggleControl;
	const Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
	const sprintf = wp.i18n.sprintf;
	const __ = wp.i18n.__;

	if ( ! Panel ) {
		return;
	}

	function SEOContentPanel() {
		const meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		const editPost = useDispatch( 'core/editor' ).editPost;
		const searchTitle = meta[ config.titleMeta ] || '';
		const description = meta[ config.descriptionMeta ] || '';
		const noindex = Boolean( meta[ config.noindexMeta ] );

		function updateMeta( key, value ) {
			editPost( { meta: Object.assign( {}, meta, { [ key ]: value } ) } );
		}

		return el(
			Panel,
			{
				name: 'seogp-content-controls',
				title: __( 'SEO', 'seo-for-generatepress' ),
				className: 'seogp-editor-panel'
			},
			el( TextControl, {
				label: __( 'Search title', 'seo-for-generatepress' ),
				value: searchTitle,
				help: __( 'Use a different title in search results, browser tabs, and social shares.', 'seo-for-generatepress' ),
				onChange: function ( value ) { updateMeta( config.titleMeta, value ); }
			} ),
			el( TextareaControl, {
				label: __( 'Meta description', 'seo-for-generatepress' ),
				value: description,
				help: __( 'Summarize this content for search results and social shares. Aim for 120–160 characters.', 'seo-for-generatepress' ),
				rows: 5,
				onChange: function ( value ) { updateMeta( config.descriptionMeta, value ); }
			} ),
			el( 'p', { className: 'seogp-character-count', 'aria-live': 'polite' }, sprintf( config.characters, Array.from( description ).length ) ),
			el( ToggleControl, {
				label: __( 'Hide from search results', 'seo-for-generatepress' ),
				checked: noindex,
				help: __( 'Keep this content out of search results and supported sitemaps. Anyone with the URL can still view it.', 'seo-for-generatepress' ),
				onChange: function ( value ) { updateMeta( config.noindexMeta, value ); }
			} )
		);
	}

	registerPlugin( 'seo-for-generatepress-content-controls', {
		render: SEOContentPanel,
		icon: 'search'
	} );
}( window.wp, window.seogpEditor ) );
