update:
	rsync -avz ./ mosab@mos3abof.com:/home/mosab/gridstatusnow.com --exclude "app/cache/*" --exclude "*.tsv*" --exclude "Makefile" --exclude "app/logs/*" --exclude ".git/*" --exclude "app/config/parameters.yml"

perms:
	sudo rm app/cache/* -rf
	sudo chmod 777 app/cache -R
	sudo chmod 777 app/logs -R
