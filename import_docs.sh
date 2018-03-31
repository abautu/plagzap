#!/bin/bash
SOLR_URL=http://localhost:38983/solr/mycore
for f in "$@"
do
  echo Importing $f
  id=`sha1sum "$f" | cut -f1 -d\ `
#  curl "$SOLR_URL/update/extract?literal.id=doc1&commit=true&uprefix=attr_&fmap.content=attr_content&extractOnly=true&extractFormat=text&resource.name=ion_ana.doc" -F "myfile=@ion_ana.doc"
  name=`basename "$f"` #  | iconv -f utf-8 -t us-ascii//TRANSLIT
  curl -s "$SOLR_URL/update/extract"  \
    -F "commitWithin=5000" \
    -F "softCommit=true" \
    -F "literal.id=$id" \
    -F "literal.description=$f" \
    -F "resource.name=$name" \
    -F "myfile=@$f"
done
#echo Commiting changes.
#curl -s "$SOLR_URL/update?commit=true" | grep status