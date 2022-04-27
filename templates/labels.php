<div class="wrap">
<h1>Babytuch Plugin</h1>
<?php settings_errors();?>
<form method="post" action="">Plugin ist aktiv.
<br><br>
    <input type="submit" value="Create PDF" name="submit"> Create PDF
</form>
</div>

<?php
if(isset($_POST['submit'])){
    /*
     * Used for Generating the fonts for TCPDF
     *
	require_once(ABSPATH.'/wp-content/plugins/babytuch-plugin/assets/TCPDF-master/tcpdf.php');
    $fonts = [];
    $fonts[] = TCPDF_FONTS::addTTFfont(BABYTUCH_PLUGIN_PATH.'/assets/fonts/NunitoSans-Bold.ttf', 'TrueTypeUnicode');
	$fonts[] = TCPDF_FONTS::addTTFfont(BABYTUCH_PLUGIN_PATH.'/assets/fonts/NunitoSans-BoldItalic.ttf', 'TrueTypeUnicode');
	$fonts[] = TCPDF_FONTS::addTTFfont(BABYTUCH_PLUGIN_PATH.'/assets/fonts/NunitoSans-Regular.ttf', 'TrueTypeUnicode');
    print_r($fonts);*/
	create_shipping_label(612);
    header("Refresh:0");
}
?>