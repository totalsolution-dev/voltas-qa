#!/bin/bash

echo "***************************************************"
echo "*                                                 *"
echo "*     DEPLOYMENT SCRIPT FOR VOLTAS WEBSITE        *"
echo "*     This script will:                           *"
echo "*     - delete untracked files                    *"
echo "*     - discard all changes on local branch       *"
echo "*     - fetch latest version from origin          *"
echo "*     - forcefull merge the local branch          *"
echo "*     - replace the MySQL DB with fetched data    *"
echo "*                                                 *"
echo "***************************************************"
     
# Prompt for the environment
echo ""
echo "Please select the current environment:"
PS3='Answer: '
envs=("DEV" "QA" "PROD" "Cancel")
select env in "${envs[@]}"; 
# echo $env
do
    case $env in
        "DEV")
            echo "You shouldn't be running this script here!"
            # optionally call a function or run some code here
            # exit
            break
            ;;
        "QA")
            # echo "$env is a Vietnamese soup that is commonly mispronounced like go, instead of duh."
	        # optionally call a function or run some code here
            break
            ;;
        "PROD")
            # echo "According to NationalTacoDay.com, Americans are eating 4.5 billion $env each year."
	        # optionally call a function or run some code here
	        break
            ;;
	    "Cancel")
            echo "User requested exit"
            exit
            ;;
        *) echo "invalid option $REPLY";;
    esac
done

# Display the environment
echo ""
echo "selected Environment:" $env
echo ""

# Prompt to make sure user wants to continue
read -p "This cannot be undone. Are you sure? (y/n): " -n 1 -r
echo    # (optional) move to a new line
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

# Delete any untracked files from the project folder
echo ""
echo "Deleting untracked files..."
git clean -f -d -i

# Discard all changes (primarily for CMS cache files)
echo ""
echo "Discarding all changes..."
git restore .

echo ""
echo "Git Password: "
read gitPas
git remote set-url origin https://phantasmagoriadigital:$gitPas@github.com/phantasmagoriadigital/voltas.com.git

# Fetch the updated branch from Origin
echo ""
echo "Fetching latest version from origin/"$env"..."
git fetch origin $env

# Forcefully merge the local branch with the fetched branch
echo ""
echo "Merging local branch with fetched branch..."
git reset --hard origin/$env

#Update DB
echo ""
echo "MySQL Password: "
read sqlPas
mysql -u voltas_web_user -p $sqlPas -D voltas_web -e "SOURCE /var/www/voltas.com/db-migration/latest.sql";