
/**
 * WordPress dependencies
 */
 import {
	createBlock,
	getBlockContent,
	pasteHandler,
	rawHandler,
	registerBlockType,
	serialize,
} from '@wordpress/blocks';
import { registerCoreBlocks } from '@wordpress/block-library';
import { initializeEditor } from '@wordpress/edit-post';
import apiFetch from '@wordpress/api-fetch';

document.addEventListener("DOMContentLoaded", function(){
    // Handler when the DOM is fully loaded

    
    // $('<div />').attr('id', 'bbconv-editor').attr('style', 'display: none').appendTo('body');
    let btnBulkConvert = document.querySelector('#convert-content');
    let bulkConvertAction = document.querySelector('#doaction');
    let btnScanPosts = document.querySelector('#scan-content');
    let btnCancelAction = document.querySelector('#bca-converter-cancel');
    let btnSingleConvert = document.querySelectorAll('.bca-single-convert');
    let converterStatus = document.querySelector('#bca-converter-modal');
    let converterPercentage = document.querySelector('#bca-converter-precentage');
    let converterStatusProgressMessage = document.querySelector('#bca-converter-status-message');
    let convertQueue = [];
    let cancelAction = false;
    

    btnSingleConvert.forEach(btn => {

        btn.addEventListener('click', function(e){
            console.log(this.id);
            convertSingle(this.id);
        });
    });

    btnScanPosts.addEventListener('click', function(e){
        converterStatus.style.display = 'flex';
        document.querySelector('#bca-converter-list').style.filter = 'blur(2px)';
        document.querySelector('#bca-converter-list').style.pointerEvents = 'none';
        scanPosts();
    });
    if(bulkConvertAction){
        bulkConvertAction.addEventListener('click', function(e){
            e.preventDefault();
            convertBulkAction();
        });
    }
    if(btnBulkConvert){
        btnBulkConvert.addEventListener('click', function(e){
            converterStatus.style.display = 'flex';
            // btnBulkConvert.setAttribute('disabled', true);
            // this.setAttribute('disabled', true);
            // document.querySelector('#bca-converter-list').style.filter = 'blur(2px)';
            // document.querySelector('#bca-converter-list').style.pointerEvents = 'none';
            convertBulk();
        });
    }

    btnCancelAction.addEventListener('click', function(){
        cancelAction = true;
    });

    function scanPosts( offset = 0, total = -1 ) {
        
        if(cancelAction){
            document.location.href = document.location.href;
            return;
        }

        if(converterStatusProgressMessage.innerHTML == ''){
            converterStatusProgressMessage.innerHTML = '<p>Scanning... 0%</p>';
        }

        apiFetch( { path: `bca-blocks-converter/v1/scan/?offset=${offset}&total=${total}` } ).then( data => {

            if ( typeof data !== "object" ) {
                console.log('Failed');
                return;
            }
            
            if ( data.error ) {
				converterStatusProgressMessage.innerHTML = data.message;
				return;
            }
            
            converterPercentage.style.width = data.percentage + '%';

			if ( data.offset >= data.total ) {
                btnScanPosts.setAttribute('disabled', false);
                converterStatusProgressMessage.innerHTML = data.message;
                document.querySelector('#bca-converter-spinner').style.display = 'none';
                document.querySelector('#checkmark-complete').style.display = 'inline-block';
                document.querySelector('#bca-converter-cancel').innerHTML = 'Continue';
                document.querySelector('#bca-converter-cancel').classList.remove('button-remove');
                document.querySelector('#bca-converter-cancel').classList.add('button-success');
                document.querySelector('#bca-converter-cancel').addEventListener('click',function(){
                    document.location.href = document.location.href + "&scan_finished=1";
                });
				
				return;
            }
        
            converterStatusProgressMessage.innerHTML = data.message;
            // updateListTable( data.list );
            scanPosts( data.offset, data.total );
        } ).catch( (err) => {
            converterStatusProgressMessage.innerHTML = bcaConvert.serverErrorMessage;
            document.querySelector('#bca-converter-cancel').addEventListener('click',function(){
                document.location.href = document.location.href;
            });
        });

    }

    // function updateListTable( list ) {
    //     document.querySelector('#bca-converter-list').innerHTML = list;
    // }

    function convertSingle( postID ) {
        document.querySelector('.bca-single-convert-' + postID).innerHTML = 'Converting...';
        apiFetch( { path: `bca-blocks-converter/v1/single/convert/?id=${postID}` } ).then( data => {

            if ( typeof data !== "object" ) {
                console.log('Failed');
                return;
            }

            let content = convertToBlocks( data );
            saveContent( postID, content )
        } );

    }

    function convertBulk( offset = 0, total = -1 ){
        if(cancelAction){
            converterStatus.style.display = 'none';
            btnBulkConvert.removeAttribute('disabled');
            btnScanPosts.removeAttribute('disabled');
            document.querySelector('#bca-converter-list').style.filter = 'none';
            document.querySelector('#bca-converter-list').style.pointerEvents = 'auto';
            return;
        }

        if(converterStatusProgressMessage.innerHTML == ''){
            converterStatusProgressMessage.innerHTML = '<p>Converting... 0%</p>';
        }

        apiFetch( { path: `bca-blocks-converter/v1/bulk/convert/?offset=${offset}&total=${total}` } ).then( data => {

            if ( typeof data !== "object" ) {
                console.log('Failed');
                return;
            }
            
            if ( data.error ) {
				converterStatusProgressMessage.innerHTML = data.message;
				return;
            }
            
            converterPercentage.style.width = data.percentage + '%';

			if ( data.offset >= data.total ) {
				converterStatusProgressMessage.innerHTML = data.message;
				// document.location.href = document.location.href + "&scan_finished=1";
				return;
            }
        
            var convertedData = [];
			var arrayLength = data.postsData.length;
			for (var i = 0; i < arrayLength; i++) {
				var convertedPost = {
					id		: data.postsData[i].id,
					content	: convertToBlocks( data.postsData[i] )
				};
				convertedData.push( convertedPost );
            }

            saveBulkContent( convertedData, data.offset, data.total, data.message );

        } ).catch( (err) => {
            converterStatusProgressMessage.innerHTML = bcaConvert.serverErrorMessage;
            document.querySelector('#bca-converter-cancel').addEventListener('click',function(){
                document.location.href = document.location.href;
            });
        });
    }
    
    function convertBulkAction(){
		document.querySelectorAll('input[name="bulk-convert[]"]').forEach(input => {
            if(input.checked == true){
                convertSingle( input.value )
            }
        });
    }
    
    function convertToBlocks( post ) {

		let blocks = pasteHandler({ HTML: post.content });
        let promises = [];

        blocks.forEach( block => {

            if( block.name == 'core/gallery'){
                
                let images = block.attributes.images;
                if(typeof post.gallery !== 'undefined'){
                    images.forEach( (image, i) => {
                        if(typeof post.gallery[image.id] !== 'undefined'){
                            image.url = post.gallery[image.id].url;
                            image.alt = post.gallery[image.id].alt;
                            image.caption = post.gallery[image.id].caption;
                        }
                    });
                }

                
            }

        });
        console.log(blocks);
        console.log(serialize(blocks));
        return serialize(blocks)
		
    }

    function saveContent( postID, content ) {
        let postData = { post_id: postID, post_content: content };

        apiFetch( { 
            path: `bca-blocks-converter/v1/single/update/`,
            method: 'POST',
            data: postData
        } ).then( data => {

            if ( typeof data !== "object" ) {
                document.querySelector('.bca-single-convert-' + postID).innerHTML = bcaConvert.failedMessage;
                return;
            }

            document.querySelector('#bca-convert-checkbox-' + postID).setAttribute('disabled',true);
            document.querySelector('#bca-convert-checkbox-' + postID).setAttribute('checked', false);
            document.querySelector('#bca-convert-checkbox-' + postID).removeAttribute('checked');
            document.querySelector('#bca-convert-checkbox-' + postID).closest('tr').classList.add('is_gutenberg');
            document.querySelector('.bca-single-convert-' + postID).innerHTML = 'Converted';
        } ).catch( (err) => {
            converterStatusProgressMessage.innerHTML = bcaConvert.failedMessage;
        });
    }

    function saveBulkContent( convertedData, offset, total, message ) {
        let postData = {
			offset : offset,
			total : total,
			postsData : convertedData
		};

        apiFetch( { 
            path: `bca-blocks-converter/v1/bulk/update/`,
            method: 'POST',
            data: postData
        } ).then( data => {

            if ( typeof data !== "object" ) {
                document.querySelector('.bca-single-convert-' + postID).innerHTML = bcaConvert.failedMessage;
                return;
            }

            if ( data.offset >= data.total ) {
                btnScanPosts.setAttribute('disabled', false);
				converterStatusProgressMessage.innerHTML = data.message;
				// document.location.href = document.location.href + "&conversion_finished=1";
				return;
            }
            converterStatusProgressMessage.innerHTML = message;
            convertBulk( data.offset, data.total )
        } );
    }
    
});