HERE=`dirname $0`
for file in `find "$HERE/languages" -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done