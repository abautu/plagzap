# PlagZap

PlagZap is a cost-efficient, high-volume, and high-speed plagiarism detection 
system built using open-source software and designed to be used on textual 
student assignments (essays, essays, theses, homework).

## How to use

- Checkout the repository
- Install Docker and Docker-compose
- Run docker-compose up
- Open http://localhost:30080/plagzap/library_update.php in your browser to 
add existing documents to the document library (optional step) or use the 
import_docs.sh script to bulk import local files (e.g. all files from a 
specific folder).
- Open http://localhost:30080/plagzap/library_search.php in your browser to 
check new new documents for plagiarism (against existing documents)
