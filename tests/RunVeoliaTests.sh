rm -rf veolia_sudest_data
mkdir veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_3March.htm veolia_sudest_data
cp ./veolia_sudest_data_src/veolia_html_4March.htm veolia_sudest_data

php testVeoliaSudEst.php
