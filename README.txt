How to use this extension
-------------------------

1) What does this extension provides?
	With this extension, you can update the TYPO3-refindex for specified tables via a scheduler-task.

	Normally, you can update the TYPO3-refindex for ALL tables with this shell-command:
	[path-to-your-php-installation] [path-to-typo3-installation]/htdocs/typo3/cli_dispatch.phpsh lowlevel_refindex -e
	e.g.:
	/opt/php5/bin/php /srv/www/typo3/htdocs/typo3/cli_dispatch.phpsh lowlevel_refindex -e

	But maybe, you only want to update the TYPO3-refindex for SPECIFIED tables (because you don't have the need to update the index for ALL tables)
	or you want to update the tables via scheduler-task. If any of these reasons is your intention, then this extension is right for you!

2) How to configure this extension?
	2.1) create BE-user named '_cli_scheduler' (the BE-user doesn't must have any access rights)
	2.2) Go to the BE-module 'Scheduler'
	2.3) Add a new scheduler-task:
		  - Choose the Class 'Update Refindex of TYPO3 [update_refindex]'
		  - Select the tables, you want to update
		  - select other scheduler-task-staff (e.g. Start- and Stop-time, type and frequency)
	2.4) call the scheduler recurring via a cronjob
		 The cronjob must execute this shell-command:
		 [path-to-your-php-installation] [path-to-typo3-installation]/htdocs/typo3/cli_dispatch.phpsh scheduler
		 e.g.:
		 /opt/php5/bin/php /srv/www/typo3/htdocs/typo3/cli_dispatch.phpsh scheduler