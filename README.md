cronboss
========

A PHP utility to read and write "crontab" data for Linux based
systems.

Use Case
========

You are developing a PHP system that needs to either read from the
current crontab or to add jobs to it.

This class then will allow you to read and write the "crontab" file for any
account that your script has permissions to do so on your server.

Using It...
===========

Once loaded, use the `crontab()` method to be given a list of Strings
that contain the file. What you do with this is up to you put remember
to save your modified crontab by calling the method `crontab($foo)`
where foo is the modified Array<String> data.

Here is a short example:

    $cron = new CronBoss();
    $jobs = $cron->crondata();

    //... modify $jobs...

    $jobs[] = '';
    $cron->crontab($jobs)->save();


*NOTES: For me, for now, this is as simple as I need but I have
planned some extensions. If you might find them useful get in touch,
it might just be enough to make me do them!*


Future Enhancements
===================

 - add() / remove() / find() methods
 - implement PHP Array/Iterable etc interfaces for slicker integration
 - use proc_open() to reap stderr when things go wrong!
 
 - implement MARKED SECTION so that existing cron is left as-is and any items added from here are maintained at the end of the file within a tagged comment block.



** DISCLAIMER HERE: If it works, I wrote it. If it breaks then it's
not my fault etc. Usual rules apply about using other peoples code! **
