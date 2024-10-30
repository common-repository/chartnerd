<form method="post" action="admin-post.php" enctype="multipart/form-data" id="settings_tm_ns">
    <input type="hidden" name="action" value="save_cnsd_settings">
    <input type="hidden" name="current_tab_id" value="<?php echo $current_tab_id;?>"> 
    <span class="get_license_wrapper">
        <a href="https://saaspnr.io/chartnerd" target="_blank"><?php _e('Get your license key','cnsd-chartnerd'); ?></a>
    </span>
    <table class="form-table">
        <tbody>
             <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_pro_license_key"> <?php _e('Enter your license key','cnsd-chartnerd');?></label>
                </th>
                <td class="">
                    <fieldset>
                        <input class="" type="text"  name="CNSD_pro_license_key" id="CNSD_pro_license_key" autocomplete="off" value="<?php if(isset($options['CNSD_pro_license_key'])) { echo $options['CNSD_pro_license_key']; } ?>" />
                    </fieldset>
                </td>
            </tr>
             <tr valign="top">
                <th scope="row" class="titledesc">
                        <input name="save_post" class="button-primary woocommerce-save-button" type="submit" value="Submit">      
                </th>
                <td>
                    <fieldset></fieldset>
                </td>
            </tr> 
        </tbody>
    </table> 
</form>