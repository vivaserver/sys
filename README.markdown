VivaServer's PHP Framework: System folder
=========================================

This is the `sys/` folder my revamped PHP framework used for all the internal development for VivaServer's clients. It won't make very much sense outside an already working application, but suffice to say it's PHP5 compatible (won't work on PHP4) and the current folder layout is as follows:

* **bin/**  
All the common modules go here, for example: user logging, permissions settings, etc. (todo)  
* **core/**  
The framework core classes for user, sessions, authentication, site configuration, etc.
* **etc/**  
The framework initialization & configuration. Here all the application's models get loaded too (if any).
* **lang/**  
The common language strings are stored in one file per language. The application may also add more strings.
* **lib/**  
All third party classes & common libraries are stored here. The application may have it's own versions of this very same files (such as PHP4 versions).
* **model/**  
Some models may be the same for all applications, such as User, Permission, etc. The Base class for all models is also here.

As said, the framework is PHP5 compatible, but each application has this same layout to allow the overriding of all the files in the system. This is how a legacy PHP4 application may be created using the same framework.

Have fun,

Cristian R. Arroyo  
cristian.arroyo@vivaserver.com.ar