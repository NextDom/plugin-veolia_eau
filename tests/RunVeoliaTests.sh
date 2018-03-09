#!/bin/bash
rm -rf veolia_sudest_data
mkdir veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_3March.htm veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_4March.htm veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_nodata.htm veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_Feb18.htm veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_Feb18_Non_Mesure.htm veolia_sudest_data
php testVeoliaSudEst.php |grep -v "It is not safe to rely on the system"  > veolia_sudest_data/testVeoliaSudEst.txt
# cp veolia_sudest_data/testVeoliaSudEst.txt veolia_sudest_data_src
diff veolia_sudest_data/testVeoliaSudEst.txt veolia_sudest_data_src/testVeoliaSudEst.txt
grep "displayConfig" veolia_sudest_data/testVeoliaSudEst.txt
# grep "displayConfig" veolia_sudest_data_src/testVeoliaSudEst.txt
