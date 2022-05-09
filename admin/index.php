<div class="wrap">
<div class="bca-converter-container">
    <input type="hidden" name="post_id" value="104138">
    <input type="hidden" name="action" value="bca_convert_single_post">
    <h1><span class="dashicons dashicons-screenoptions"></span><span><?php echo get_admin_page_title(); ?></span></h1>
    <p>The <em><strong>Classic the Gutenberg Conversion</strong></em> will scan for all posts built in classic editor. You can then convert all or indivdual posts from the classic content to using gutenberg blocks.</p>
    <div class="bca-converter-actions">
        <button class="button button-secondary button-hero" type="submit" id="scan-content">Scan Posts</button>
        <?php $BCA_CONVERTER_LIST = new BCA_CONVERTER_LIST; ?>

        <?php if($BCA_CONVERTER_LIST::count_with_status( 'all' )): ?>
        <button class="button button-primary button-hero" type="submit" id="convert-content">Convert All Posts</button>
        <?php endif; ?>
    </div>

</div>

<div id="bca-converter-modal">

    <div id="bca-converter-status-container">
        <div id="bca-converter-spinner" class="lds-default"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
        <img id="checkmark-complete" src="<?php echo BCA_BLOCK_CONVERTER_URI ?>admin/assets/checkmark-complete.svg" alt="" srcset="">
        <p id="bca-converter-status-message">Scanning... 0%</p>
        <div id="bca-converter-status">
            <div id="bca-converter-precentage"></div>
        </div>
        <button id="bca-converter-cancel" class="button button-remove" type="submit">Cancel</button>
    </div>
</div>

<?php bca_render_table(); ?>
</div>