# LinkedIn Data Engine 
This is an application that efficiently scrapes LinkedIn participant data via the LinkedIn APIs.

Written by Jason Wijegooneratne for Philip Schneider's Master Thesis. 

## About
The LinkedIn Data Engine (LDE) was originally designed to gather linkedin network data, from willing participants, in order to visualze network graphs in gephi. Some people expressed interest in its capabilites so I decied to release its source. Feel free to use, hack, do what you want with this stuff, it is released under the MIT licesence

## Design
LDE is developed with the CodeIgnighter (CI) PHP framework and is designed to run on a linux HTTP stack backed by a a MYSQL server. The way this app is structured is such that there is a client facing portion of the CI backed website, which handles authenticating participants, getting and storing their oauth tokens, and also presenting a nice show of face. There is also a restricted section of the CI website. This is intended to be called via a cronjob issuing a curl command with a post parameter *passcode* set in order to run the schedule. This way the app fetches data on whatever schedule you define in your crons. The *passcode* paramter is passed with the curl command to restrict the general public (including robots) from trigguring your app. With that lets move onto..

## Requirements
A linux server with HTTP capabilities (I use Apache), PHP 5+, MYSQL 5.5, CURL 7.27.0, and GIT installed. It might work with other versions of the aforementioned software however this is untested.
With a few tweaks and mods you can run this from any sort of web stack that is php enabled...

## Installation (LINUX/GIT) and Usage
After you have setup your server...
* cd to your http docs dir
* clone https://github.com/jdwije/LinkedIn-Data-Engine.git
* tailor the application/config/ config.php & database.php CI files to your environment
* setup your mysql data base. you can import the lde.sql file to mirror the required table structures
* schdule a cronjob like the one in the linux.cron file
* share the link to your webserver and watch the data start flooding in!
After you have some participants you can access php my admin via www.yoururl.com/lde to inspect your data (sorry no management interface yet). It is a smart idea to create a sepereate mysql database user with readonly privileges specifically for inspecting the database in order to avoid messing anything up whilst you're in there. 

## Licensing
This software is lisenced under the MIT lisence by Jason Wijegooneratne & Philip Schneider. See the lde_user_license.txt file for a full description.






