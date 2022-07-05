#!/bin/bash

# move to root folder
cd ..

# remove examples
rm -rf vendor/tecnickcom/tcpdf/examples

# Delete un-used fonts
rm -rf vendor/tecnickcom/tcpdf/fonts/ae_fonts_2.0
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.33
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-2.34
rm -rf vendor/tecnickcom/tcpdf/fonts/freefont-20100919
rm -rf vendor/tecnickcom/tcpdf/fonts/freefont-20120503
rm -rf vendor/tecnickcom/tcpdf/fonts/freemon*
rm -rf vendor/tecnickcom/tcpdf/fonts/cid*
rm -rf vendor/tecnickcom/tcpdf/fonts/courier*
rm -rf vendor/tecnickcom/tcpdf/fonts/aefurat*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusans*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusansb*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusansi*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusansmono*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusanscondensed*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavusansextralight*
rm -rf vendor/tecnickcom/tcpdf/fonts/dejavuserif*
rm -rf vendor/tecnickcom/tcpdf/fonts/freesans*
rm -rf vendor/tecnickcom/tcpdf/fonts/freesansb*
rm -rf vendor/tecnickcom/tcpdf/fonts/freeserifb*
rm -rf vendor/tecnickcom/tcpdf/fonts/freeserifi*
rm -rf vendor/tecnickcom/tcpdf/fonts/pdf*
rm -rf vendor/tecnickcom/tcpdf/fonts/times*
rm -rf vendor/tecnickcom/tcpdf/fonts/uni2cid*
rm -rf vendor/tecnickcom/tcpdf/fonts/aealarabiya*
rm -rf vendor/tecnickcom/tcpdf/fonts/hysmyeongjostdmedium*
rm -rf vendor/tecnickcom/tcpdf/fonts/kozgopromedium*
rm -rf vendor/tecnickcom/tcpdf/fonts/kozminproregular*

# Install nunitosans
php vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php -i assets/fonts/NunitoSans-Bold.ttf,assets/fonts/NunitoSans-BoldItalic.ttf,assets/fonts/NunitoSans-Regular.ttf

