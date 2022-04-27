<?php


namespace Inc\Api;


use Exception;
use setasign\Fpdi\Tcpdf\Fpdi;
use TCPDF_FONTS;
use WC_Order;
use WC_Product;

class BT_PDF {
	public static array $fontPaths = [
		'assets/fonts/NunitoSans-Bold.ttf',
		'assets/fonts/NunitoSans-BoldItalic.ttf',
		'assets/fonts/NunitoSans-Regular.ttf',
	];

    /**
     * @throws Exception
     */
    public static function generateFontFiles($pluginDir) {
		$errors = [];
        foreach(self::$fontPaths as $ttfFile){
            $path = $pluginDir."/".$ttfFile;
			$fontname = TCPDF_FONTS::addTTFfont($path, 'TrueTypeUnicode', '', 96);
		    if(!$fontname) {
                $errors[] = "Schriftart wurde nicht gefunden unter '$path'.";
            }
        }
        if($errors) {
            $message = 'Einige Schriftarten wurden nicht gefunden';
            foreach($errors as $error) {
                $message .= "\n $error";
            }
            throw new Exception($message);
        }
	}


    /**
     * @throws Exception
     */
    public static function createPDF($title, $author): Fpdi {
		// create new PDF document
		$pdf = new Fpdi(PDF_PAGE_ORIENTATION, 'mm', 'A4', true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor($author);
		$pdf->SetTitle($title);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->setTopMargin(13.0);
		$pdf->SetRightMargin(6.0);

		$pdf->setHeaderMargin(13);
		$pdf->SetFooterMargin(13.0); //13mm

		$pdf->SetPrintHeader(false);
		$pdf->SetPrintFooter(false);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
        try {
            $pdf->SetFont('nunitosans', '', 14 / 1.5);
        } catch (Exception $exception) {
            throw new Exception("There was an error while creating the PDF: " . $exception->getMessage());
        }
		$pdf->setCellHeightRatio(1.25);

		$pdf->AddPage();

		return $pdf;
	}

	/**
	 * @param $pdf
	 * @param WC_Product[] $products
	 */
	public static function printProductInformationGrid(&$pdf, array $products) {
		$k=15;
		$r=147;

		foreach($products as $product){
			$name = $product->get_name();
			$size = $product->get_attributes();
			$size_str = $size["groesse"];

			$img_ids = $product->get_gallery_image_ids();

			if(!$img_ids) {
				// product images can also be attached to parent
				// if child ids are empty, load parent and get its gallery images
				$parent_id = $product->get_parent_id();
				$parent_product = wc_get_product($parent_id);
				$img_ids = $parent_product ? $parent_product->get_gallery_image_ids() : false;
			}

			$img_url = $img_ids ? wp_get_attachment_url($img_ids[0]) : '';

			$pdf->SetXY($k, $r);
			$html = '
						<div style=" float: left; width: 33.33%; padding: 5px;">
							<img src="'.$img_url.'" width="120px" height="80px"/>
							<p>
								<b style="font-size:12px;color: black;">'.$name.'</b><br />
								<span style="font-size:12px;color: black;">Grösse: '.$size_str.'</span><br />
							</p>
						</div>
					';

			$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
			$k=$k+45;
			if($k>150){
				$k=15;
				$r=$r+40;
			}
		}
	}

	public static function printOrderInformation(&$pdf, WC_Order $order) {
		$order_id = $order->get_id();
		$order_date = $order->get_date_created()->date("d.m.y - H:i");

		$customer_address = Helpers::getShippingAddressFromOrder($order);
		$html ="
				<table>
				    <tr><td>Empfänger:</td></tr>
				    <tr><td><strong>".$customer_address->getFullName()."</strong></td></tr>
				    <tr><td><strong>".$customer_address->getStreet()."</strong></td></tr>
				    <tr><td><strong>".$customer_address->getZipAndCity(true)."</strong></td></tr>
				</table>
	    ";

		$pdf->writeHTMLCell(60, 0, 15, 40, $html, 0, 0, 0, true, '', false);

		$html ="<table>
                <tr><td>Bestellnummer: $order_id</td></tr>
                <tr><td>Bestelldatum: $order_date</td></tr>
            </table>                    
     	";

		$pdf->writeHTMLCell(60, 0, '', '', $html, 0, 2, 0, true, '', false);


		$lineStyle = array('width' => 0.05, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
		$pdf->Line(15, 64, 195, 64, $lineStyle);
	}

}