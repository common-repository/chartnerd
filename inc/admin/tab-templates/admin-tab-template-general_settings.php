<form method="post" action="admin-post.php" enctype="multipart/form-data" id="settings_tm_ns">
    <input type="hidden" name="action" value="save_cnsd_settings">
    <input type="hidden" name="current_tab_id" value="<?php echo $current_tab_id;?>"> 
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_default_userid_notloggedin">Default admin</label>
                </th>
                <td class="">
                    <fieldset>
                        <select name="CNSD_default_userid_notloggedin" id="CNSD_default_userid_notloggedin">
                            <option value="">--Select--</option>
                            <?php
                            if(is_array($administratorCredentials) && !empty($administratorCredentials)){
                                foreach($administratorCredentials as $administratorCredential){
                                    if(isset($options['CNSD_default_userid_notloggedin']) && $options['CNSD_default_userid_notloggedin']!='' && $options['CNSD_default_userid_notloggedin'] == $administratorCredential->id){
                                                $selected = 'selected="selected"';
                                        }else{
                                                $selected = '';
                                        }
                                    echo '<option value="'.$administratorCredential->id.'" '.$selected.'>'.$administratorCredential->display_name.'</option>';
                                }
                            }
                            ?>
                        </select>
                    </fieldset>
                </td>
            </tr>
             <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_fontsize">Font Size</label>
                </th>
                <td class="">
                    <fieldset>
                        <input class="" type="number" min="1" name="CNSD_fontsize" id="CNSD_fontsize" autocomplete="off" value="<?php if(isset($options['CNSD_fontsize'])) { echo $options['CNSD_fontsize']; }else{ echo 18;} ?>" />(px)
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_color">Color</label>
                    
                </th>
                <td class="forminp CNSD_color_wrapper">
                    <fieldset>
                        <input class="input-text regular-input" type="text" name="CNSD_color" id="CNSD_color" autocomplete="off" value="<?php if(isset($options['CNSD_color'])) { echo $options['CNSD_color']; }else{ echo '#00ff00'; } ?>">
                        <span> <img src="<?php echo CNSD_URL."/assets/images/color-picker.png"; ?>"></span>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_googlefont">Google font</label>
                    
                </th>
                <td class="forminp">
                    <fieldset>
                        <select name="CNSD_googlefont" id="CNSD_googlefont">
                             <?php if( CNSD_globalSettings::$googlefonts !='' ){  

                                    echo '<option value="">--Select--</option>'; 
                                    $google_fonts = explode(',',CNSD_globalSettings::$googlefonts);
                                    foreach($google_fonts as $font){


                                        $option_value = str_replace(' ','+',$font);
                                        if(isset($options['CNSD_googlefont']) && $options['CNSD_googlefont']!='' && $options['CNSD_googlefont'] == $option_value){
                                                $selected = 'selected="selected"';
                                        }else{
                                                $selected = '';
                                        }
                                        
                                        echo '<option value="'.$option_value.'" '.$selected.'>'.$font.'</option>';
                                    }
                            } ?>
                        </select>   

                    </fieldset>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_shortcodes">Available Shortcodes</label>
                    
                </th>
                <td class="forminp">
                    <fieldset>
                        <select name="CNSD_shortcodes" id="CNSD_shortcodes">
                             <?php if( !empty(CNSD_globalSettings::$available_shortcodes) ){ 

                                    echo '<option value="">--Select--</option>'; 

                                    foreach(CNSD_globalSettings::$available_shortcodes as $shortcodes){
                                            $code = str_replace('"','~',$shortcodes);
                                        
                                        if(isset($options['CNSD_shortcodes']) && $options['CNSD_shortcodes']!='' && $options['CNSD_shortcodes'] == $code){
                                                $selected = 'selected="selected"';
                                        }else{
                                                $selected = '';
                                        }
                                        
                                        echo '<option value="'. $code.'" '.$selected.'>'.$shortcodes.'</option>';
                                    }
                            } ?>
                        </select>   

                    </fieldset>
                </td>
            </tr>
           
            <tr valign="top">
                <th scope="row" class="titledesc">
                        <input name="save_post" class="button-primary woocommerce-save-button" type="submit" value="Save changes">      
                </th>
                <td>
                    <fieldset></fieldset>
                </td>
            </tr> 
             <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="CNSD_preview">Preview</label> 
                </th>
                <td class="forminp">
                    <fieldset>
                        <?php 
                       
                         
                        if(isset($options['CNSD_shortcodes']) && $options['CNSD_shortcodes']!='' ){ 
                                $shortcode  =  str_replace('~','"',$options['CNSD_shortcodes']); ?>

                                <span id="CNSD_preview" ><?php echo do_shortcode($shortcode); ?></span>
                       
                       <?php }else{ 
                            $style = '';
                            if(isset($options['CNSD_color']) && $options['CNSD_color'] !='' ) { 
                                $style .= 'color:'.$options['CNSD_color'].';';  
                            }
                            if(isset($options['CNSD_fontsize']) && $options['CNSD_fontsize']!='' ) { 
                                $style .= 'font-size:'.$options['CNSD_fontsize'].'px;';  
                            }
                            if(isset($options['CNSD_googlefont']) && $options['CNSD_googlefont']!='' ) { 
                                $style .= 'font-family:'.str_replace('+',' ',$options['CNSD_googlefont']).';';  
                            }
                            ?>
                                <span id="CNSD_preview" style="<?= $style ?>"><?php echo CNSD_globalSettings::$demo_preview_text . CNSD_SettingsPage::CNSD_chartnerd_watermark(); ?></span>

                       <?php } ?>
                        
                        
                    </fieldset>
                </td>
            </tr>         
        </tbody>
    </table>        
    
</form>