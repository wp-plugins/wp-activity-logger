<div class="wrap">
    <?php screen_icon(); ?>
    <h2>WP Activity Logger Settings</h2> 
    <form method="post" action="options.php">
        <?php settings_fields( self::PLUGIN_SLUG ); ?>
        <?php do_settings_sections( self::PLUGIN_SLUG ); ?>
        <?php submit_button(); ?>
    </form>
</div>
