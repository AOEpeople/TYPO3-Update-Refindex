# TYPO3-Update-Refindex

This is a TYPO3-Extension to update the TYPO3-refindex for specified tables via a scheduler-task.

## Download / Installation

You can download and install this extension or use composer.

## Copyright / License

Copyright: (c) 2016 - 2017, AOE GmbH
License: GPLv3, <http://www.gnu.org/licenses/gpl-3.0.en.html>

## Contributing

	1. Fork the repository on Github
	2. Create a named feature / bugfix branch (like `feature_add_something_new` or `bugfix\thing_which_does_not_work`)
	3. Write your change
	4. Write tests for your change (if applicable)
	5. Run the tests, ensuring they all pass
	6. Submit a Pull Request using Github

##  How to use this extension

1. What does this extension provides?

	Normally, you can update the TYPO3-refindex for ALL tables with this shell-command:
	[path-to-your-php-installation] [path-to-typo3-installation]/htdocs/typo3/cli_dispatch.phpsh lowlevel_refindex -e
	e.g.:
	/opt/php5/bin/php /srv/www/typo3/htdocs/typo3/cli_dispatch.phpsh lowlevel_refindex -e

	But maybe, you only want to update the TYPO3-refindex for SPECIFIED tables (because you don't have the need to update the index for ALL tables)
	or you want to update the tables via scheduler-task. If any of these reasons is your intention, then this extension is right for you!

2. How to configure this extension?
    * create BE-user named '_cli_scheduler' (the BE-user doesn't must have any access rights)
	* Go to the BE-module 'Scheduler'
	* Add a new scheduler-task:
	    * Choose the Class 'Update Refindex of TYPO3 [update_refindex]'
		* Select the tables, you want to update
		* select other scheduler-task-staff (e.g. Start- and Stop-time, type and frequency)
	* call the scheduler recurring via a cronjob
		 The cronjob must execute this shell-command:
		 [path-to-your-php-installation] [path-to-typo3-installation]/htdocs/typo3/cli_dispatch.phpsh scheduler
		 e.g.:
		 /opt/php5/bin/php /srv/www/typo3/htdocs/typo3/cli_dispatch.phpsh scheduler