<div class="wrap wpil-report-page wpil_styles">
    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline">Domains Report</h1>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <input id="wpil-object-cache-flush-nonce" type="hidden" value="<?php echo wp_create_nonce('wpil-flush-object-cache'); ?>" />
                <?php include_once 'report_tabs.php'; ?>
                <div id="report_domains">
                    <form>
                        <input type="hidden" name="page" value="link_whisper" />
                        <input type="hidden" name="type" value="domains" />
                        <?php $table->search_box('Search', 'search'); ?>
                    </form>
                    <?php $table->display(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
