## This is a starter kit for CodeIgniter LarCity Projects

Love CodeIgniter? You could use it too. Keep with you like. Junk what you don't. 

Licensed via [MIT](LICENSE.md) so - at your own risk peeps ;). 

### Now even more streamlined!

Did some work shortly after the first publication to:

* Reduce the size of the overall kit
* merge the "soup to nuts" example with the library itself, while providing utilities for extracting the library files if you need to integrate with your existing CodeIgniter project

### Integrating? Start here 

With an existing project, simply run `./prepare-for-integration` in the root directory of your pull. This will:

* Create an `archive/` directory with a zip file called `integration-kit_<timestamp>.zip`. 
* Unzip this file to get the files you need to drop into your project. 
* Follow the instructions in `/dist/application/db_changes` to create the needed tables within your project database
* Lookout for README files with nuggets of information to help in some directories of note:
    * /dist/application/db_changes

That's it!

### Starting from scratch with CodeIgniter? 

Think you would like to use our shell setup? All you need to do is run `./prepare-baseline` in the root directory of your pull. This will:

* Create an `archive/` directory with a zip file called `baseline-kit_<timestamp>.zip`. 
* Unzip this file and copy it <em>en sum</em> as your CodeIgniter baseline to get started.
* Follow the instructions in `/dist/application/db_changes` to create the needed tables within your project database
* Lookout for README files with nuggets of information to help in some directories of note:
    * /dist/application/db_changes

<em>Fini!</em>

More documentation to come as we develop this toolkit. 