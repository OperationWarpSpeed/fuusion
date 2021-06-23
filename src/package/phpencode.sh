#/bin/bash
IONENCODER="/usr/local/ioncube_encoder/ioncube_encoder53"
IONFLAGS="--obfuscation-exclusion-file exclusion_list --obfuscate all --obfuscation-key"
# Key:  BASE64 of "Hope is NOT a strategy! Results = SMART Goals + Execution and Learning."
IONKEY="SG9wZSBpcyBOT1QgYSBzdHJhdGVneSEgUmVzdWx0cyA9IFNNQVJUIEdvYWxzICsgRXhlY3V0aW9uIGFuZCBMZWFybmluZy4="
COPYRIGHT="Copyright (c) SoftNAS Inc. All Rights Reserved."

for path in $*
do
i=`echo $path | sed 's/.//;s/\///'`
echo "ioncube Encoding $i..."
mv $i src-$i
$IONENCODER $IONFLAGS "$IONKEY" --add-comment "$COPYRIGHT"  src-$i -o $i
rm -f src-$i
done

