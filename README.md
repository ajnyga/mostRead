Most Read Articles block plugin
===========
Plug-in for version 3.2+ of OJS.
v. 3.0.0.8
------------

Plugin for the creation of a “most read articles” section in the OJS3 frontend sidebar.

Functionality
-------------
1. Default: adds a block to the sidebar with the title "BLOCK TITLE" followed by the 5 most viewed articles in the last week; for each article are shown title (linking to article page) and fa fa eye icon followed by view count.
2. The title of the block and the number of days to be included in the statistics ("Most read block settings" field) can be modified by Settings. Default values for empty settings are "Block title" and "7"

System requirements
--------------------
1. OJS version 3.2 or higher

Known Bugs
---------------
None

Installation
-------------
Upload the files to the server using the appropriate OJS module dedicated to installing the plugins (the plugin must be in compressed format .tar.gz).

tar zcvf mostRead_OJS_3.2.tar.gz --exclude .git --exclude .vscode --exclude .DS_Store mostRead

License
-------
The plugin is developed by The Federation of Finnish Learned Societies and is released under [GNU General Public License, version 2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

Contributions
-------
@zielaq
