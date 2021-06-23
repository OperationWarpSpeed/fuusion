#/bin/sh
#JSOBFUS="/home/rbraddy/bin/js-obfus"
#JSOBFUSCMD="/home/rbraddy/bin/js-obfus-command"
for i in $*
do
mv $i $i.tmp
cat $i.tmp | sed '/\/\*/,/*\//d' > $i.src
uglifyjs -o $i $i.src
rm -f $i.tmp $i.src
#echo "Processing $i..."
#$JSOBFUS `cat $JSOBFUSCMD` < $i.src  > $i
echo -n "."
done

