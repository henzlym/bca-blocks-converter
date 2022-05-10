
/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';
import { render } from '@wordpress/element';
import { registerCoreBlocks } from '@wordpress/block-library';
import { initializeEditor } from '@wordpress/edit-post';
import apiFetch from '@wordpress/api-fetch';
/**
 * Internal dependencies
 */
import Editor from './editor';
import './converter';

domReady( function () {
    const settings = window.getdaveSbeSettings || {};
    registerCoreBlocks();
    render(
        <Editor settings={ settings } />,
        document.getElementById( 'bcaConvertEditor' )
    );
} );
