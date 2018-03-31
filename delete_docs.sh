#!/bin/bash
SOLR_URL=http://localhost:38983/solr/mycore
curl "$SOLR_URL/update?commit=true" -H "Content-Type: text/xml" --data-binary '<delete><query>*:*</query></delete>'
