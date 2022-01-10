#!/bin/bash

. bin/config.inc

SYMFODIR=$(pwd);
DATA_DIR=$TMP;

echo "AHRB;;;AHRB;;16 RUE ANTOINE LAURENT LAVOISIER;;77480;BRAY SUR SEINE;FRANCE;01 47 90 76 23;;;;;AHRB;44393649700025;FR85443936497;8129A" > $DATA_DIR/societes.csv
cat $DATA_DIR/F_COMPTET.csv | awk -F ";" '{ print $1 ";;;" $3 ";" $10 ";" $11 ";" $12 ";" $13 ";" $15 ";" $17 ";" $68 ";;" $69 ";" $71 ";" $70 ";" $1 ";" $24 ";" $23 ";" $22 ";;;;"  }' >> $DATA_DIR/societes.csv

cat $DATA_DIR/F_DOCLIGNE.csv > $DATA_DIR/factures.csv
cat $DATA_DIR/F_CREGLEMENT.csv | sed 's|6986 A 6988|6986/6987/6988|' | sed 's|7868 A 7871|7868/7869/7870/7871|' > $DATA_DIR/reglements.csv

cat $DATA_DIR/Factures.txt | sed 's/AHRB/@/' | tr "\n" "#" | tr "@" "\n" | sed -r 's/^.+N° intracommunautaire :FR85443936497##([^#]+)#/\1/' | sed -r 's/#.+NUMERO//' | sed 's/##DATE##/;/' | grep "contrat n" | sed -r 's/#.+contrat n[°\ ?a-z#]*([0-9A-Z\/]+).+/;\1/' > $DATA_DIR/factures_contrats.csv

cat /tmp/ahrb/factures_contrats.csv | awk -F ';' '{ print "s|(^[^;]*);([^;]*);" $3 ";|" $1 ";\\1;" $3 ";|" }' | sed 's/&/\\\&/' | sort | uniq > /tmp/ahrb/societes_contrats.sed

cat /tmp/ahrb/societes_contrats_ajustement.sed >> /tmp/ahrb/societes_contrats.sed

xlsx2csv -d ";" /tmp/ahrb/GESTION\ DES\ CONTRATS\ AHRB\ 2021.xlsx | grep -v "^;" | sed -r 's/^([^;]*);[^;]*;/\1;\1;/' | sed -r -f /tmp/ahrb/societes_contrats.sed > /tmp/ahrb/contrats.csv

echo "Import des sociétés"
php5 app/console importer:csv societe.importer $DATA_DIR/societes.csv -vvv --no-debug

echo "Import des factures"
php5 app/console importer:csv facture.importer $DATA_DIR/factures.csv -vvv --no-debug

echo "Import des paiements"
php5 app/console importer:csv paiements.importer $DATA_DIR/reglements.csv -vvv --no-debug

echo "Import des contrats"
php5 app/console importer:csv contrat.importer $DATA_DIR/contrats.csv -vvv --no-debug