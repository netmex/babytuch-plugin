<?php 
/**
 * @package  BabytuchPlugin
 */
namespace Inc\Pages;


class StockGraphs
{
	public function register() {
        add_shortcode( 'stock_graphs', array($this, 'stock_graphs_form'));
    }

    
    function stock_graphs_form( $attributes ) {
        if (isset($_GET['code'])) {
            $product = $_GET['code'];
               
                $slug = $_GET['code'];
                //echo $product;
                global $wpdb;
                $res = $wpdb->get_results( 
                    $wpdb->prepare("
                        SELECT * FROM babytuch_inventory
                        WHERE item_name = %s", 
                        $slug
                    ));
                if(!empty($res)){
                $res_json = json_decode(json_encode($res), true);
                $average = get_average_decrease( $res_json );
                $current_stock = $res_json[0]["amount"];
                $limit = $res_json[0]["new_order_limit"];
                for($i=1;$i<52;$i++){
                    ${"week_" . $i}= $res_json[0]["stock_$i"];
                }
                $products_next_reorder = get_option('products_next_reorder');
                $products_last_reorder = get_option('products_last_reorder');
                $today = date('Y-m-d',time());
                $num_weeks = (int)datediff('ww', $products_last_reorder, $today, false);
                $num_weeks2 = (int)datediff('ww', $products_next_reorder, $today, false);
       
                $dataPoints = array(
                    array("y" => $week_51, "label" => "-52"),
                    array("y" => $week_51, "label" => "-51"),
                    array("y" => $week_50, "label" => "-50"),
                    array("y" => $week_49, "label" => "-49"),
                    array("y" => $week_48, "label" => "-48"),
                    array("y" => $week_47, "label" => "-47"),
                    array("y" => $week_46, "label" => "-46"),
                    array("y" => $week_45, "label" => "-45"),
                    array("y" => $week_44, "label" => "-44"),
                    array("y" => $week_43, "label" => "-43"),
                    array("y" => $week_42, "label" => "-42"),
                    array("y" => $week_41, "label" => "-41"),
                    array("y" => $week_40, "label" => "-40"),
                    array("y" => $week_39, "label" => "-39"),
                    array("y" => $week_38, "label" => "-38"),
                    array("y" => $week_37, "label" => "-37"),
                    array("y" => $week_36, "label" => "-36"),
                    array("y" => $week_35, "label" => "-35"),
                    array("y" => $week_34, "label" => "-34"),
                    array("y" => $week_33, "label" => "-33"),
                    array("y" => $week_32, "label" => "-32"),
                    array("y" => $week_31, "label" => "-31"),
                    array("y" => $week_30, "label" => "-30"),
                    array("y" => $week_29, "label" => "-29"),
                    array("y" => $week_28, "label" => "-28"),
                    array("y" => $week_27, "label" => "-27"),
                    array("y" => $week_26, "label" => "-26"),
                    array("y" => $week_25, "label" => "-25"),
                    array("y" => $week_24, "label" => "-24"),
                    array("y" => $week_23, "label" => "-23"),
                    array("y" => $week_22, "label" => "-22"),
                    array("y" => $week_21, "label" => "-21"),
                    array("y" => $week_20, "label" => "-20"),
                    array("y" => $week_19, "label" => "-19"),
                    array("y" => $week_18, "label" => "-18"),
                    array("y" => $week_17, "label" => "-17"),
                    array("y" => $week_16, "label" => "-16"),
                    array("y" => $week_15, "label" => "-15"),
                    array("y" => $week_14, "label" => "-14"),
                    array("y" => $week_13, "label" => "-13"),
                    array("y" => $week_12, "label" => "-12"),
                    array("y" => $week_11, "label" => "-11"),
                    array("y" => $week_10, "label" => "-10"),
                    array("y" => $week_9, "label" => "-9"),
                    array("y" => $week_8, "label" => "-8"),
                    array("y" => $week_7, "label" => "-7"),
                    array("y" => $week_6, "label" => "-6"),
                    array("y" => $week_5, "label" => "-5"),
                    array("y" => $week_4, "label" => "-4"),
                    array("y" => $week_3, "label" => "-3"),
                    array("y" => $week_2, "label" => "-2"),
                    array("y" => $week_1, "label" => "-1"),
                    array("y" => $current_stock, "label" => "Aktuell"),
                    array("y" => $current_stock-$average, "label" => "1"),
                    array("y" => $current_stock-2*$average, "label" => "2"),
                    array("y" => $current_stock-3*$average, "label" => "3"),
                    array("y" => $current_stock-4*$average, "label" => "4"),
                    array("y" => $current_stock-5*$average, "label" => "5"),
                    array("y" => $current_stock-6*$average, "label" => "6"),
                    array("y" => $current_stock-7*$average, "label" => "7"),
                    array("y" => $current_stock-8*$average, "label" => "8"),
                    array("y" => $current_stock-9*$average, "label" => "9"),
                    array("y" => $current_stock-10*$average, "label" => "10"),
                    array("y" => $current_stock-11*$average, "label" => "11"),
                    array("y" => $current_stock-12*$average, "label" => "12"),
                    array("y" => $current_stock-13*$average, "label" => "13"),
                    array("y" => $current_stock-14*$average, "label" => "14"),
                    array("y" => $current_stock-15*$average, "label" => "15"),
                    array("y" => $current_stock-16*$average, "label" => "16"),
                    array("y" => $current_stock-17*$average, "label" => "17"),
                    array("y" => $current_stock-18*$average, "label" => "18"),
                    array("y" => $current_stock-19*$average, "label" => "19"),
                    array("y" => $current_stock-20*$average, "label" => "20"),
                    array("y" => $current_stock-21*$average, "label" => "21"),
                    array("y" => $current_stock-22*$average, "label" => "22"),
                    array("y" => $current_stock-23*$average, "label" => "23"),
                    array("y" => $current_stock-24*$average, "label" => "24"),
                    array("y" => $current_stock-25*$average, "label" => "25")
                );    
 
               ?>
               <!DOCTYPE HTML>
                <html>
                <head>
                <script>
                window.onload = function () {
                 
                var chart = new CanvasJS.Chart('chartContainer', {
                    title: {
                        text: 'Bestandesverlauf des Produktes: <?php echo $slug?>'
                    },
                    axisX:{
                        stripLines:[
                        {
                            startValue:51,
                            endValue:52,                
                            color:"#d8d8d8",
                            label : "Aktuell",
                            labelFontColor: "#000000",
                        },{
                            startValue:<?php echo 51-(int)$num_weeks?>,
                            endValue:<?php echo 52-(int)$num_weeks?>,                
                            color:"#434141",
                            label : "Letzte Nachbestellung",
                            labelFontColor: "#000000",
                        },{
                            startValue:<?php echo 51-(int)$num_weeks2?>,
                            endValue:<?php echo 52-(int)$num_weeks2?>,                
                            color:"#434141",
                            label : "Nächste Nachbestellung",
                            labelFontColor: "#000000",
                        }
                        ]
                    },
                    axisY: {
                        title: 'Menge an Lager',
                        stripLines:[
                        {
                            startValue:<?php echo (int)$limit?>,
                            endValue:<?php echo (int)$limit+1?>,                
                            color:"#FB0505",
                            label : "Limite",
                            labelFontColor: "#000000",
                        }
                        ]
                    },
                    data: [{
                        type: 'line',
                        dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
                    }]
                });
                chart.render();
                 
                }
                </script>
                </head>
                <body>
                <div id='chartContainer'style='height: 370px; width: 100%;'></div>
                <script src='https://canvasjs.com/assets/script/canvasjs.min.js'></script>
                </body>
                </html>    
                
               
                <br><br>
                <form method="post" action="">
                <label for="products">Produkt wählen: </label>
                <select style="width:250px;" name="products" id="products"><?php
                $products = wc_get_products( array('numberposts' => -1) );
                foreach($products as $product){
                    if($product->is_type( 'variable' )){
                        $product_single = wc_get_product($product);
                        $name = $product_single->get_name();
                        echo "<optgroup label='$name'>";
                        $children   = $product_single->get_children();
                        foreach($children as $child){
                            $child_product = wc_get_product($child);
                            $child_attr = $child_product->get_attributes();
                            $child_slug = $child_product->get_slug();
                            $child_size = $child_attr["groesse"];
                            echo "<option value=$child_slug>Grösse: $child_size</option>";
                        }
                        echo '</optgroup>';
                    }
                }
                echo '</select>';
           
                ?>
                <br><br>
                <input type="submit" value="Auswählen" name="choose">
                </form><?php
                if(isset($_POST['choose'])){
                    $product_slug = $_POST["products"];
                    $url = get_home_url();
                    header("Location: $url/bestandesverlaeufe/?code=$product_slug");
                }
               
                }
            else{
                echo'Falscher code.';
            }
        
        }else{
            ?>
            <br>
            <form method="post" action="">
            <label for="products">Produkt wählen: </label>
            <select style="width:250px;" name="products" id="products"><?php
            $products = wc_get_products( array('numberposts' => -1) );
            foreach($products as $product){
                if($product->is_type( 'variable' )){
                    $product_single = wc_get_product($product);
                    $name = $product_single->get_name();
                    echo "<optgroup label='$name'>";
                    $children   = $product_single->get_children();
                    foreach($children as $child){
                        $child_product = wc_get_product($child);
                        $child_attr = $child_product->get_attributes();
                        $child_slug = $child_product->get_slug();
                        $child_size = $child_attr["groesse"];
                        echo "<option value=$child_slug>Grösse: $child_size</option>";
                    }
                    echo '</optgroup>';
                }
            }
            echo '</select>';
       
            ?>
            <br><br>
            <input type="submit" value="Auswählen" name="choose">
            </form><?php
            if(isset($_POST['choose'])){
                $product_slug = $_POST["products"];
                $url = get_home_url();
                header("Location: $url/bestandesverlaeufe/?code=$product_slug");
            }
        }
    }

}

function get_average_decrease($res_json){
    $decrease_rates = array();
    for($j=52; $j>1; $j--){
        $stock_old = $res_json[0]["stock_$j"];
        $k=$j-1;
        $stock_new = $res_json[0]["stock_$k"];
        if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
            $decrease_rate = (int)$stock_old - (int)$stock_new;  
            array_push($decrease_rates, $decrease_rate);
        }
        if($j==2){
            $stock_old = $res_json[0]["stock_1"];
            $stock_new = $res_json[0]["amount"];
            if($stock_old != '0' and (int)$stock_new<=(int)$stock_old){
                $decrease_rate = (int)$stock_old - (int)$stock_new;  
                array_push($decrease_rates, $decrease_rate);
            }
        }
    }
    if(count($decrease_rates)!=0){
        $average = array_sum($decrease_rates)/count($decrease_rates);
    }else{
        $average = 0;
    }
    return $average;
}


function datediff($interval, $datefrom, $dateto, $using_timestamps = false)
{
    /*
    $interval can be:
    yyyy - Number of full years
    q    - Number of full quarters
    m    - Number of full months
    y    - Difference between day numbers
           (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
    d    - Number of full days
    w    - Number of full weekdays
    ww   - Number of full weeks
    h    - Number of full hours
    n    - Number of full minutes
    s    - Number of full seconds (default)
    */

    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto   = strtotime($dateto, 0);
    }

    $difference        = $dateto - $datefrom; // Difference in seconds
    $months_difference = 0;

    switch ($interval) {
        case 'yyyy': // Number of full years
            $years_difference = floor($difference / 31536000);
            if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                $years_difference--;
            }

            if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                $years_difference++;
            }

            $datediff = $years_difference;
        break;

        case "q": // Number of full quarters
            $quarters_difference = floor($difference / 8035200);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $quarters_difference--;
            $datediff = $quarters_difference;
        break;

        case "m": // Number of full months
            $months_difference = floor($difference / 2678400);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $months_difference--;

            $datediff = $months_difference;
        break;

        case 'y': // Difference between day numbers
            $datediff = date("z", $dateto) - date("z", $datefrom);
        break;

        case "d": // Number of full days
            $datediff = floor($difference / 86400);
        break;

        case "w": // Number of full weekdays
            $days_difference  = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day        = date("w", $datefrom);
            $days_remainder   = floor($days_difference % 7);
            $odd_days         = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?

            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }

            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }

            $datediff = ($weeks_difference * 5) + $days_remainder;
        break;

        case "ww": // Number of full weeks
            $datediff = floor($difference / 604800);
        break;

        case "h": // Number of full hours
            $datediff = floor($difference / 3600);
        break;

        case "n": // Number of full minutes
            $datediff = floor($difference / 60);
        break;

        default: // Number of full seconds (default)
            $datediff = $difference;
        break;
    }
    return $datediff;
}