#!/bin/bash

. bin/config.inc

SYMFODIR=$(pwd);
DATA_DIR=$TMP;

cat $DATA_DIR/AHRB\ F_COMPTET.csv | awk -F ";" '{ print ";;" $3 ";" $10 ";" $11 ";" $12 ";" $13 ";" $15 ";" $17 ";" $68 ";;" $69 ";" $71 ";" $70 ";" $1 ";" $24 ";" $23 ";" $22 ";;;;"  }' > $DATA_DIR/societe.csv

echo  -e "\nImport des sociétés"
#echo "0;0;1;ARBH;16 RUE ANTOINE LAURENT LAVOISIER;;77480;BRAY SUR SEINE;1;;;;;;;;1;Jan 12 2012 12:02:10:327PM;1;;;;1;ARBH;;0;;1;;1;;;;Jan 12 2012 12:02:09:843PM;;;;0" >> $DATA_DIR/societes.csv
php app/console importer:csv societe.importer $DATA_DIR/societes.csv -vvv --no-debug