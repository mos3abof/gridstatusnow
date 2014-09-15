update:
	rsync -avz /home/mosab/dev/power-grid-status/ mosab@mos3abof.com:/home/mosab/gridstatusnow.com --exclude "app/cache/*" --exclude "*.tsv*" --exclude "Makefile" --exclude "app/logs/*" --exclude ".git/*"
