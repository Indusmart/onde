#!/bin/bash                                                                  

currentPath=`dirname $0`
hora=`date +"%H"`
logfile=$currentPath/scheduledScript.log
demandasBotOutput=$currentPath/demandasBotOutput.log

data=`date +%c`
echo -n "[" $data "] Script de verificacao do bot de demandas...  " >> $logfile
#echo $hora >> $logfile

pidNumber=`ps auxw | grep demandasBot | grep usr | tr -s " " | cut -d " " -f 2`

if [ -z "$pidNumber" ]
then
  echo -n "Nao rodando, disparando... "  >> $logfile
  cd $currentPath
  echo -n " ("  >> $logfile
local=`pwd`
  echo -n $local >> $logfile
  echo -n ") "  >> $logfile
  ./demandasBot >> $demandasBotOutput 2>&1 /dev/null &
  echo " agora rodando, OK!"  >> $logfile
else
  echo -n $pidNumber >> $logfile
  echo ", rodando [ OK ]"  >> $logfile
fi
