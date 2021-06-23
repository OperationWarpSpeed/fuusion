#!/bin/sh

###
## This script is meant to configure git hooks by creating a simple symlink between git-hooks and .git/hooks
###

if [ -d ".git/hooks" ]
then
	rm -rfv .git/hooks
	if [ -d "git-hooks" ]
	then
		echo "Setting up new symlink."
		ln -s `pwd`/git-hooks .git/hooks
	else
		echo "No git-hooks dir is found. Please execute this from the git tree root."
		exit 1
	fi
elif [ -l ".git/hooks" ]
then
	echo ".git/hooks is already a symlink"
fi

echo "Done"
exit 0
