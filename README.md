(My own fork, just to be able to submit pull requests — no, I'm not going to take the project over 😂)

# Project-Pier

**ProjectPier** is a *Free, Open-Source, PHP application* for managing tasks, projects and teams through an intuitive web interface. ([http://www.projectpier.org](http://www.projectpier.org)) _(currently broken, as of April 2021)_

If you are interested in contributing, bugfixing, ... to ProjectPier please visit the [wiki](https://github.com/Project-Pier/ProjectPier-Core/wiki)

## Reporting Bugs

It is absolutely critical for you to report any bugs you find with this software.
If you don't, they can not be fixed. If you find a bug please
check the bug tracker to make sure it's not already known.  If you are certain
you have discovered a NEW bug, please log it into the issue tracker here at GitHub.

> If you have **NOT** found any bugs, we need to hear that too!
> Please let us know what type of system you are using and the extent of your
> testing.  Please include the OS, Apache, PHP and MySQL versions and/or the name of
> the web hosting provider the testing was performed on.
> A new forum has been created specifically to gather and discuss this format, it is located at
> [http://www.projectpier.org/forum/development/088] _(currently broken, as of April 2021)_

## System requirements

ProjectPier requires a *PHP web server* and *MySQL*. The recommended web
server is *Apache*, but IIS 5 and above have been reported to work also.

ProjectPier is **not** PHP4 compatible.

### Recommended configuration:

- PHP 5.2 or greater
- MySQL 4.1 or greater with InnoDB support (see notes below)
- Apache 2.0 or greater

If you do not have these installed on a server or your personal computer,
you can visit the sites below to learn more about how to download and install
them.  They are all licensed under various compatible Open Source licenses.

- PHP    : [http://www.php.net/](http://www.php.net/)
- MySQL  : [http://www.mysql.com/](http://www.mysql.com/)
- Apache : [http://www.apache.org/](http://www.apache.org/)

## Upgrading

If you are upgrading an existing ProjectPier installation,
see [UPGRADE.txt](../master/UPGRADE.txt) for an upgrade procedure.

## Installation

See [INSTALL.txt](../master/INSTALL.txt)

### Enabling InnoDB Support

Some installations of MySQL don't support *InnoDB* by default.  The ProjectPier installer
will tell you if your server is not configured to support *InnoDB*. This is easy to fix:

1. Open your MySQL options file, the file name is
   ```my.cnf``` (Linux) - usually at ```/etc/my.cnf```
   or
   ```my.ini``` (Windows) - usually at ```c:/windows/my.ini```
   If you are using the Uniform Server on Windows, the file will be named ```my-small```
   and will need to be edited with a unix compatible editor such as *SublimeText, Atom, Vim, ...*
2. Comment the ```skip-innodb``` line by adding ```#``` in front of it (like ```#skip-innodb``` ).
3. It would also be good to increase ```max_allowed_packet``` to ensure that
   you'll be able to upload files larger than 1MB.
   Just add this line below the ```#skip-innodb``` line:

   ```php
   set-variable = max_allowed_packet=64M
   ```

> Alternatively, just install without *InnoDB* support. The installer will allow you.

### Changing the Language

ProjectPier installation screens are in English and English is the default language
for the program. After installation is complete, the language can be changed.

The following base languages are available:
- nl_nl = Dutch
- en_us = English (US)
- de_de = German
- es_es = Spanish
- fr_fr = French

> Other languages packs may be available for download at the site.

## About ProjectPier

ProjectPier is an Open Source project management and collaboration tool that you can install on your own server.
It is released under the terms of the Gnu Affero General Public License (AGPL)
(see [LICENSE.txt](../master/LICENSE.txt) for details).

[http://www.projectpier.org](http://www.projectpier.org) _(currently broken, as of April 2021)_

It is based on the last free and open-source version of activeCollab, [0.7.1](https://sourceforge.net/projects/freecollab/).

## My own meagre attempts...

I have always loved activeCollab, and ProjectPier once activeCollab stopped being open source. I'm an old-time, pre-Agile IT consultant and project manager, and still like the well-organised conceptual framework of pre-Agile project management...

ProjectPier, unfortunately, was abandoned when PHP 5.4 was the currently most popular PHP version. Most pre-PHP 5.6 projects died once it became clear that PHP 5.6 would break _everything_ — deliberately so! — to 'prepare' programmers for the new reality presented by PHP 7.X. The very few 'old' projects that made the effort to fully implement compatibility with 5.6 _without having any deprecation warnings_ survived the 'gap' separating PHP 5 from 7. PHP 8.X went a step further, turning most (if not all) PHP 7.X warnings into errors. As a consequence, all the 'old' source code which had been ignoring those deprecation warnings over a decade or so will simply not work.

In my line of work, I'm in dire need of having a good, old-fashioned project management system. I did experiment with at least some twenty contemporary solutions, most of which SaaS, a few which could be locally installed with some limitations. _All_ of them are little more than a glorified mix of Slack and Discourse, with _lots_ of 'connectors' to hook up with whatever you fancy (from GitHub to Zoom, from OneDrive to CRMs...) and I wonder why anyone bothers to pay anything for that, when open-source [Mattermost](https://mattermost.com/) does all of it for free and can be installed anywhere; it interfaces with _everything_ but does very little on its own...

So, back to ProjectPier, and I'll see what I can do to bring its codebase to PHP of the 2020s. Maybe that way old-timer project managers might feel encouraged to support ProjectPier's continued development once again...

   - Gwyn, April 2021
