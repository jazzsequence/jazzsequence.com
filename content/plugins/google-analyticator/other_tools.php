 <?php
add_thickbox();
wp_enqueue_script('google-analyticator-admin',plugin_dir_url( __FILE__ ). 'scripts/google-analyticator-scripts.js',array('jquery'));
wp_enqueue_style('google-analyticator-admin-style',plugin_dir_url( __FILE__ ).'styles/google-analyticator-style-common.css', array(), '3.1.1');
?>
 <div class="wrap">
	<h2>Other Tools</h2>
    <p>
        <div class="google-analyticator-plugin-box">
                <div class="google-analyticator-plugin-box-logo-container">
                        <img src="<?php echo plugin_dir_url(__FILE__) . 'images/products/appsumo-logo.png'; ?>">
                </div>
                <a href="https://appsumo.com/tools/wordpress/?utm_source=sumo&utm_medium=wp-widget&utm_campaign=google-analyticator" target="_blank">AppSumo</a> Promotes great products to help you in your career and life.
        </div>

        <div class="google-analyticator-plugin-box">
                <div class="google-analyticator-plugin-box-logo-container">
                        <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/products/sendfox-logo.svg'; ?>">
                </div>
                <a href="http://sendfox.com" target="_blank">SendFox</a> Email marketing for content creators.
        </div>

        <div class="google-analyticator-plugin-box">
                <div class="google-analyticator-plugin-box-logo-container">
                        <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/products/sumo-logo.png'; ?>">
                </div>
                <a href="<?php echo admin_url( 'plugin-install.php?tab=plugin-information&plugin=sumome&TB_iframe=true&width=743&height=500'); ?>" class="thickbox">Sumo</a> Tools to grow your Email List, Social Sharing and Analytics.
        </div>

        <div class="google-analyticator-plugin-box">
                <div class="google-analyticator-plugin-box-logo-container">
                        <img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/products/kingsumo-logo.svg'; ?>">
                </div>
                <a href="https://kingsumo.com/" target="_blank">KingSumo</a> Grow your email list through Viral Giveaways for WordPress
        </div>                
    </p>

    <div class="google-analyticator-plugin-box-form">
           <?php include plugin_dir_path( __FILE__ ).'appsumo-capture-form.php'; ?>
    </div>
</div>
