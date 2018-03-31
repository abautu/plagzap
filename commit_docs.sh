#!/bin/bash
SOLR_URL=http://localhost:38983/solr/mycore
echo Commiting changes.
curl -s "$SOLR_URL/update?commit=true" | grep status