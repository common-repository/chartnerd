<div id="wrap" > 
    <h1 id="taps_title"> ChartNerd API </h1>
    <h2 class="nav-tab-wrapper">
        <?php
            foreach (CNSD_globalSettings::$tabs as $tab_id => $tab_heading) {
                ?>
                <a class="nav-tab <?php
                if ($current_tab_id == $tab_id) {
                    echo "nav-tab-active";
                }
                ?>" href="?page=<?php echo CNSD_globalSettings::$page_id; ?>&tab=<?php echo $tab_id; ?>"><?php echo $tab_heading; ?></a>
       <?php } ?>  
    </h2>
    <div id="tab-<?php echo $current_tab_id; ?>">
        <?php if (strpos($current_tab_id, 'removefornow')) { ?>
            <div class="notice notice-warning">
                <h4>If you made any changed, please make sure to save settings before moving to another tab :) </h4>
            </div>
        <?php } ?>
        
        <div class="tm-netsuite-container container-fluid woocommerce">
            <?php do_action('cnsd_get_tab_content', $current_tab_id);?>
        </div>
    </div>
</div>